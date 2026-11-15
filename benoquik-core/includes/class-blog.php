<?php
/**
 * Learn (blog): article fields, reading time, table of contents, article
 * blocks, author profile fields and Rank Math schema additions.
 *
 * Blocks (used by the theme's blog templates):
 *   benoquik/blog-intro        – Learn page content (if any) above the post list
 *   benoquik/category-pills    – All + category filter links
 *   benoquik/featured-post     – latest sticky post as a large card (Learn page 1)
 *   benoquik/post-card-meta    – "6 min read · 15 Nov 2026" in post cards
 *   benoquik/article-byline    – author, medical reviewer, dates, reading time
 *   benoquik/toc               – table of contents from H2/H3
 *   benoquik/references        – numbered references (per-post field)
 *   benoquik/article-disclaimer– medical disclaimer box
 *   benoquik/product-cta       – soft product card (can be hidden per post)
 *   benoquik/author-box        – author photo, credentials, bio
 *   benoquik/related-posts     – 3 articles from the same category
 *   benoquik/share             – copy link / WhatsApp / X / Facebook / LinkedIn / email
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Blog {

	const DISCLAIMER = 'This article is for general information only and is not medical advice. Monobenzone is a prescription medicine — always follow your doctor’s advice.';

	/** IDs given to headings during this request (shared by TOC and heading filter). */
	private static $heading_ids = array();

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'front_assets' ) );
		add_action( 'save_post_post', array( __CLASS__, 'save_reading_time' ), 10, 2 );
		add_filter( 'render_block_core/heading', array( __CLASS__, 'heading_ids' ), 10, 2 );
		// Each rendering of the article starts numbering afresh, so IDs always match the TOC.
		add_filter( 'the_content', static function ( $c ) { self::$heading_ids = array(); return $c; }, 1 );
		add_action( 'pre_get_posts', array( __CLASS__, 'blog_query' ) );

		// Author profile: photo + credentials.
		add_action( 'show_user_profile', array( __CLASS__, 'profile_fields' ), 5 );
		add_action( 'edit_user_profile', array( __CLASS__, 'profile_fields' ), 5 );
		add_action( 'personal_options_update', array( __CLASS__, 'save_profile' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'profile_assets' ) );
		add_filter( 'pre_get_avatar_data', array( __CLASS__, 'avatar' ), 10, 2 );

		// Rank Math: medical reviewer + last reviewed on the WebPage entity; Article image fallback.
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'rank_math_schema' ), 99, 2 );
	}

	/* =============================================================== fields */

	public static function register_meta() {
		$auth   = static fn( $allowed, $meta_key, $post_id ) => current_user_can( 'edit_post', $post_id );
		$string = array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'default'       => '',
			'auth_callback' => $auth,
		);
		register_post_meta( 'post', 'bq_reviewer_name', $string + array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_post_meta( 'post', 'bq_reviewer_credentials', $string + array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_post_meta( 'post', 'bq_reviewer_url', $string + array( 'sanitize_callback' => 'esc_url_raw' ) );
		register_post_meta( 'post', 'bq_last_reviewed', $string + array( 'sanitize_callback' => array( __CLASS__, 'sanitize_date' ) ) );
		register_post_meta(
			'post',
			'bq_hide_cta',
			array(
				'type'          => 'boolean',
				'single'        => true,
				'show_in_rest'  => true,
				'default'       => false,
				'auth_callback' => $auth,
			)
		);
		register_post_meta(
			'post',
			'bq_references',
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_references' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'title' => array( 'type' => 'string' ),
								'url'   => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);
		register_post_meta(
			'post',
			'bq_reading_time',
			array(
				'type'         => 'integer',
				'single'       => true,
				'show_in_rest' => array( 'schema' => array( 'readonly' => true ) ),
				'default'      => 0,
				'auth_callback' => $auth,
			)
		);
	}

	public static function sanitize_date( $v ) {
		$v = sanitize_text_field( (string) $v );
		return preg_match( '/^\d{4}-\d{2}-\d{2}/', $v ) ? substr( $v, 0, 10 ) : '';
	}

	public static function sanitize_references( $refs ) {
		$out = array();
		foreach ( (array) $refs as $r ) {
			$title = sanitize_text_field( $r['title'] ?? '' );
			$url   = esc_url_raw( $r['url'] ?? '' );
			if ( '' !== $title || '' !== $url ) {
				$out[] = array(
					'title' => $title,
					'url'   => $url,
				);
			}
		}
		return $out;
	}

	/** Reading time in minutes (≈ 200 words per minute, at least 1). */
	public static function reading_time( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return 1;
		}
		$saved = (int) get_post_meta( $post->ID, 'bq_reading_time', true );
		if ( $saved ) {
			return $saved;
		}
		return self::count_minutes( $post->post_content );
	}

	private static function count_minutes( $content ) {
		$words = str_word_count( wp_strip_all_tags( strip_shortcodes( (string) $content ) ) );
		return max( 1, (int) ceil( $words / 200 ) );
	}

	public static function save_reading_time( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, 'bq_reading_time', self::count_minutes( $post->post_content ) );
	}

	public static function editor_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'post' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_script(
			'benoquik-article-panel',
			BENOQUIK_CORE_URL . 'assets/article-panel.js',
			array( 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n' ),
			BENOQUIK_CORE_VERSION,
			true
		);
	}

	public static function front_assets() {
		if ( is_singular( 'post' ) ) {
			wp_enqueue_script(
				'benoquik-article',
				BENOQUIK_CORE_URL . 'assets/article.js',
				array(),
				BENOQUIK_CORE_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}

	/** Blog lists: 9 per page; the featured (sticky) post isn't repeated in the grid. */
	public static function blog_query( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( $q->is_home() || $q->is_category() || $q->is_tag() || $q->is_author() ) {
			$q->set( 'posts_per_page', 9 );
		}
		if ( $q->is_home() && ! $q->is_paged() ) {
			$featured = self::featured_post_id();
			if ( $featured ) {
				$q->set( 'post__not_in', array( $featured ) );
				$q->set( 'ignore_sticky_posts', true );
			}
		}
	}

	/** Latest published sticky post, or 0. */
	public static function featured_post_id() {
		$sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ) );
		if ( ! $sticky ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post__in'            => $sticky,
				'post_status'         => 'publish',
				'numberposts'         => 1,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/* ======================================================= table of contents */

	private static function make_id( $text, &$used ) {
		$base = sanitize_title( wp_strip_all_tags( $text ) );
		$base = $base ? $base : 'section';
		$id   = $base;
		$n    = 2;
		while ( isset( $used[ $id ] ) ) {
			$id = $base . '-' . $n++;
		}
		$used[ $id ] = true;
		return $id;
	}

	/** H2/H3 headings of a post: [ [level, text, id], … ] (same IDs as rendered). */
	public static function headings( $post ) {
		$post  = get_post( $post );
		$used  = array();
		$out   = array();
		$walk  = static function ( $blocks ) use ( &$walk, &$used, &$out ) {
			foreach ( $blocks as $b ) {
				if ( 'core/heading' === $b['blockName'] ) {
					$level = (int) ( $b['attrs']['level'] ?? 2 );
					if ( in_array( $level, array( 2, 3 ), true ) ) {
						$text  = trim( wp_strip_all_tags( $b['innerHTML'] ) );
						$id    = ! empty( $b['attrs']['anchor'] ) ? $b['attrs']['anchor'] : self::make_id( $text, $used );
						$out[] = array( $level, $text, $id );
					}
				}
				if ( ! empty( $b['innerBlocks'] ) ) {
					$walk( $b['innerBlocks'] );
				}
			}
		};
		if ( $post ) {
			$walk( parse_blocks( $post->post_content ) );
		}
		return $out;
	}

	/** Give article H2/H3 headings stable IDs so the TOC can link to them. */
	public static function heading_ids( $html, $block ) {
		if ( ! is_singular( 'post' ) || ! doing_filter( 'the_content' ) ) {
			return $html;
		}
		$level = (int) ( $block['attrs']['level'] ?? 2 );
		if ( ! in_array( $level, array( 2, 3 ), true ) || preg_match( '/<h[23][^>]*\sid=/', $html ) ) {
			return $html;
		}
		$id = self::make_id( wp_strip_all_tags( $html ), self::$heading_ids );
		return preg_replace( '/<h([23])/', '<h$1 id="' . esc_attr( $id ) . '"', $html, 1 );
	}

	/* ================================================================ blocks */

	public static function register_blocks() {
		$common = array(
			'editor_script_handles' => array( 'benoquik-blocks-editor' ),
			'style_handles'         => array( 'benoquik-blocks' ),
			'uses_context'          => array( 'postId', 'postType' ),
		);
		$blocks = array(
			'blog-intro'         => 'blog_intro',
			'category-pills'     => 'category_pills',
			'featured-post'      => 'featured_post',
			'post-card-meta'     => 'post_card_meta',
			'article-byline'     => 'byline',
			'toc'                => 'toc',
			'references'         => 'references',
			'article-disclaimer' => 'disclaimer',
			'product-cta'        => 'product_cta',
			'related-posts'      => 'related_posts',
			'share'              => 'share',
		);
		foreach ( $blocks as $name => $cb ) {
			register_block_type( 'benoquik/' . $name, $common + array( 'render_callback' => array( __CLASS__, $cb ) ) );
		}
		register_block_type(
			'benoquik/author-box',
			$common + array(
				'render_callback' => array( __CLASS__, 'author_box' ),
				'attributes'      => array(
					'variant' => array(
						'type'    => 'string',
						'default' => 'post',
					),
				),
			)
		);
	}

	private static function post_id( $block ) {
		if ( $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
			return (int) $block->context['postId'];
		}
		return (int) get_the_ID();
	}

	private static function time_tag( $datetime_iso, $label = '' ) {
		$ts = strtotime( $datetime_iso );
		if ( ! $ts ) {
			return '';
		}
		return '<time datetime="' . esc_attr( gmdate( 'c', $ts ) ) . '">' . esc_html( $label . wp_date( get_option( 'date_format' ), $ts ) ) . '</time>';
	}

	public static function blog_intro() {
		$page_id = (int) get_option( 'page_for_posts' );
		if ( ! $page_id || ! is_home() || is_paged() ) {
			return '';
		}
		$content = trim( (string) get_post_field( 'post_content', $page_id ) );
		return $content ? '<div class="wp-block-benoquik-blog-intro bq-blog-intro">' . apply_filters( 'the_content', $content ) . '</div>' : ''; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	}

	public static function category_pills() {
		$cats    = get_categories( array( 'hide_empty' => false, 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
		$current = is_category() ? get_queried_object_id() : 0;
		$all_url = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/learn/' );
		$out     = '<nav class="wp-block-benoquik-category-pills bq-cat-pills" aria-label="' . esc_attr__( 'Article categories', 'benoquik-core' ) . '"><ul>';
		$out    .= '<li><a href="' . esc_url( $all_url ) . '"' . ( ! $current && ( is_home() ) ? ' aria-current="page"' : '' ) . '>' . esc_html__( 'All', 'benoquik-core' ) . '</a></li>';
		foreach ( $cats as $c ) {
			$out .= '<li><a href="' . esc_url( get_category_link( $c ) ) . '"' . ( $current === $c->term_id ? ' aria-current="page"' : '' ) . '>' . esc_html( $c->name ) . '</a></li>';
		}
		return $out . '</ul></nav>';
	}

	/** Card HTML shared by the featured post and related posts. */
	public static function card( $post_id, $size = 'medium_large', $featured = false ) {
		$cats  = get_the_category( $post_id );
		$cat   = $cats ? $cats[0] : null;
		$url   = get_permalink( $post_id );
		$title = get_the_title( $post_id );
		$img   = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, $featured ? 'large' : $size, array( 'loading' => $featured ? 'eager' : 'lazy', 'sizes' => $featured ? '(max-width: 781px) 100vw, 60vw' : '(max-width: 599px) 100vw, (max-width: 1023px) 50vw, 33vw' ) ) : '<span class="bq-card__noimg" aria-hidden="true"></span>';
		$tag   = $featured ? 'h2' : 'h3';
		return '<article class="bq-post-card' . ( $featured ? ' bq-post-card--featured' : '' ) . '">'
			. '<a class="bq-post-card__media" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . $img . '</a>'
			. '<div class="bq-post-card__body">'
			. ( $cat ? '<p class="bq-post-card__cat"><a href="' . esc_url( get_category_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a></p>' : '' )
			. '<' . $tag . ' class="bq-post-card__title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></' . $tag . '>'
			. '<p class="bq-post-card__excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $post_id ), $featured ? 40 : 24 ) ) . '</p>'
			. self::post_card_meta( array(), '', null, $post_id )
			. '<p class="bq-post-card__more"><a href="' . esc_url( $url ) . '">' . esc_html__( 'Read article', 'benoquik-core' ) . '<span class="screen-reader-text">: ' . esc_html( $title ) . '</span></a></p>'
			. '</div></article>';
	}

	public static function featured_post() {
		if ( ! is_home() || is_paged() ) {
			return '';
		}
		$id = self::featured_post_id();
		return $id ? '<div class="wp-block-benoquik-featured-post bq-featured">' . self::card( $id, 'large', true ) . '</div>' : '';
	}

	public static function post_card_meta( $attrs = array(), $content = '', $block = null, $post_id = 0 ) {
		$post_id = $post_id ? $post_id : self::post_id( $block );
		if ( ! $post_id ) {
			return '';
		}
		$mins = self::reading_time( $post_id );
		/* translators: %d minutes */
		return '<p class="wp-block-benoquik-post-card-meta bq-card-meta"><span>' . esc_html( sprintf( _n( '%d min read', '%d min read', $mins, 'benoquik-core' ), $mins ) ) . '</span><span aria-hidden="true"> · </span>' . self::time_tag( get_post_time( 'c', true, $post_id ) ) . '</p>';
	}

	public static function byline( $attrs, $content, $block ) {
		$post_id  = self::post_id( $block );
		$post     = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		$author   = (int) $post->post_author;
		$name     = get_the_author_meta( 'display_name', $author );
		$cred     = get_user_meta( $author, 'bq_credentials', true );
		$reviewer = get_post_meta( $post_id, 'bq_reviewer_name', true );
		$rcred    = get_post_meta( $post_id, 'bq_reviewer_credentials', true );
		$rurl     = get_post_meta( $post_id, 'bq_reviewer_url', true );
		$reviewed = get_post_meta( $post_id, 'bq_last_reviewed', true );
		$mins     = self::reading_time( $post_id );

		$out  = '<div class="wp-block-benoquik-article-byline bq-byline">';
		$out .= '<div class="bq-byline__author">' . get_avatar( $author, 48, '', '', array( 'class' => 'bq-byline__avatar', 'loading' => 'eager' ) );
		$out .= '<div><p class="bq-byline__name">' . esc_html__( 'By', 'benoquik-core' ) . ' <a href="' . esc_url( get_author_posts_url( $author ) ) . '" rel="author">' . esc_html( $name ) . '</a>' . ( $cred ? '<span class="bq-byline__cred">, ' . esc_html( $cred ) . '</span>' : '' ) . '</p>';
		if ( $reviewer ) {
			$r    = esc_html( $reviewer ) . ( $rcred ? ', ' . esc_html( $rcred ) : '' );
			$out .= '<p class="bq-byline__reviewer"><svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg> ' . esc_html__( 'Medically reviewed by', 'benoquik-core' ) . ' ' . ( $rurl ? '<a href="' . esc_url( $rurl ) . '">' . $r . '</a>' : $r ) . '</p>';
		}
		$out .= '</div></div><ul class="bq-byline__facts">';
		$out .= '<li>' . esc_html__( 'Published', 'benoquik-core' ) . ' ' . self::time_tag( get_post_time( 'c', true, $post_id ) ) . '</li>';
		if ( $reviewed ) {
			$out .= '<li>' . esc_html__( 'Last reviewed', 'benoquik-core' ) . ' ' . self::time_tag( $reviewed . 'T12:00:00Z' ) . '</li>';
		}
		/* translators: %d minutes */
		$out .= '<li>' . esc_html( sprintf( _n( '%d min read', '%d min read', $mins, 'benoquik-core' ), $mins ) ) . '</li>';
		return $out . '</ul></div>';
	}

	public static function toc( $attrs, $content, $block ) {
		$items = self::headings( self::post_id( $block ) );
		if ( count( $items ) < 2 ) {
			return '';
		}
		$list = '<ol>';
		foreach ( $items as $h ) {
			$list .= '<li class="bq-toc__l' . (int) $h[0] . '"><a href="#' . esc_attr( $h[2] ) . '">' . esc_html( $h[1] ) . '</a></li>';
		}
		$list .= '</ol>';
		return '<nav class="wp-block-benoquik-toc bq-toc" aria-label="' . esc_attr__( 'In this article', 'benoquik-core' ) . '"><details class="bq-toc__details"><summary>' . esc_html__( 'In this article', 'benoquik-core' ) . '</summary>' . $list . '</details></nav>';
	}

	public static function references( $attrs, $content, $block ) {
		$refs = (array) get_post_meta( self::post_id( $block ), 'bq_references', true );
		$refs = array_filter( $refs, static fn( $r ) => ! empty( $r['title'] ) || ! empty( $r['url'] ) );
		if ( ! $refs ) {
			return '';
		}
		$out = '<section class="wp-block-benoquik-references bq-references" aria-labelledby="bq-references-title"><h2 id="bq-references-title">' . esc_html__( 'References', 'benoquik-core' ) . '</h2><ol>';
		foreach ( $refs as $r ) {
			$title = $r['title'] ? $r['title'] : $r['url'];
			$out  .= '<li>' . ( ! empty( $r['url'] ) ? '<a href="' . esc_url( $r['url'] ) . '" rel="noopener" target="_blank">' . esc_html( $title ) . '</a>' : esc_html( $title ) ) . '</li>';
		}
		return $out . '</ol></section>';
	}

	public static function disclaimer() {
		$text = Settings::get( 'blog_disclaimer' );
		return '<aside class="wp-block-benoquik-article-disclaimer bq-article-disclaimer" role="note"><p><strong>' . esc_html__( 'Medical disclaimer:', 'benoquik-core' ) . '</strong> ' . esc_html( $text ? $text : self::DISCLAIMER ) . '</p></aside>';
	}

	public static function product_cta( $attrs, $content, $block ) {
		$post_id = self::post_id( $block );
		if ( get_post_meta( $post_id, 'bq_hide_cta', true ) || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}
		$page    = get_page_by_path( 'benoquik-cream-20g', OBJECT, 'product' );
		$product = $page ? wc_get_product( $page->ID ) : null;
		if ( ! $product || 'publish' !== $product->get_status() ) {
			return '';
		}
		$img = $product->get_image_id() ? wp_get_attachment_image( $product->get_image_id(), 'woocommerce_thumbnail', false, array( 'loading' => 'lazy', 'class' => 'bq-cta-card__img' ) ) : '';
		return '<aside class="wp-block-benoquik-product-cta bq-cta-card" aria-label="' . esc_attr__( 'Benoquik product', 'benoquik-core' ) . '">' . $img
			. '<div class="bq-cta-card__body"><p class="bq-cta-card__eyebrow">℞ ' . esc_html__( 'Prescription only', 'benoquik-core' ) . '</p>'
			. '<p class="bq-cta-card__title">' . esc_html( $product->get_name() ) . '</p>'
			. '<p class="bq-cta-card__text">' . esc_html__( 'Prescription monobenzone 20% cream for extensive vitiligo', 'benoquik-core' ) . '</p>'
			. '<p class="bq-cta-card__btn"><a class="wp-element-button" href="' . esc_url( $product->get_permalink() ) . '">' . esc_html__( 'View product', 'benoquik-core' ) . '</a></p></div></aside>';
	}

	public static function author_box( $attrs, $content, $block ) {
		$archive = ( $attrs['variant'] ?? 'post' ) === 'archive';
		if ( $archive ) {
			$user_id = is_author() ? get_queried_object_id() : 0;
		} else {
			$post    = get_post( self::post_id( $block ) );
			$user_id = $post ? (int) $post->post_author : 0;
		}
		if ( ! $user_id ) {
			return '';
		}
		$name = get_the_author_meta( 'display_name', $user_id );
		$bio  = get_the_author_meta( 'description', $user_id );
		$cred = get_user_meta( $user_id, 'bq_credentials', true );
		$out  = '<div class="wp-block-benoquik-author-box bq-author-box' . ( $archive ? ' bq-author-box--archive' : '' ) . '">' . get_avatar( $user_id, $archive ? 120 : 80, '', '', array( 'class' => 'bq-author-box__photo', 'loading' => $archive ? 'eager' : 'lazy' ) ) . '<div>';
		if ( $archive ) {
			$out .= '<p class="bq-author-box__eyebrow">' . esc_html__( 'Author', 'benoquik-core' ) . '</p><h1 class="bq-author-box__name">' . esc_html( $name ) . '</h1>';
		} else {
			$out .= '<p class="bq-author-box__eyebrow">' . esc_html__( 'About the author', 'benoquik-core' ) . '</p><p class="bq-author-box__name"><a href="' . esc_url( get_author_posts_url( $user_id ) ) . '">' . esc_html( $name ) . '</a></p>';
		}
		$out .= $cred ? '<p class="bq-author-box__cred">' . esc_html( $cred ) . '</p>' : '';
		$out .= $bio ? '<p class="bq-author-box__bio">' . esc_html( $bio ) . '</p>' : '';
		if ( ! $archive ) {
			$out .= '<p class="bq-author-box__link"><a href="' . esc_url( get_author_posts_url( $user_id ) ) . '">' . esc_html__( 'More articles', 'benoquik-core' ) . '</a></p>';
		}
		return $out . '</div></div>';
	}

	public static function related_posts( $attrs, $content, $block ) {
		$post_id = self::post_id( $block );
		$cats    = wp_get_post_categories( $post_id );
		$ids     = $cats ? get_posts(
			array(
				'category__in'        => $cats,
				'post__not_in'        => array( $post_id ),
				'numberposts'         => 3,
				'post_status'         => 'publish',
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
			)
		) : array();
		if ( count( $ids ) < 3 ) {
			$ids = array_merge(
				$ids,
				get_posts(
					array(
						'post__not_in'        => array_merge( array( $post_id ), $ids ),
						'numberposts'         => 3 - count( $ids ),
						'post_status'         => 'publish',
						'fields'              => 'ids',
						'ignore_sticky_posts' => true,
					)
				)
			);
		}
		if ( ! $ids ) {
			return '';
		}
		$out = '<section class="wp-block-benoquik-related-posts bq-related-posts" aria-labelledby="bq-related-title"><h2 id="bq-related-title">' . esc_html__( 'Related articles', 'benoquik-core' ) . '</h2><div class="bq-post-grid-cards">';
		foreach ( $ids as $id ) {
			$out .= self::card( $id );
		}
		return $out . '</div></section>';
	}

	public static function share( $attrs, $content, $block ) {
		$post_id = self::post_id( $block );
		$url     = rawurlencode( get_permalink( $post_id ) );
		$title   = rawurlencode( html_entity_decode( get_the_title( $post_id ), ENT_QUOTES ) );
		$links   = array(
			'whatsapp' => array( 'WhatsApp', 'https://wa.me/?text=' . $title . '%20' . $url ),
			'x'        => array( 'X', 'https://x.com/intent/post?url=' . $url . '&text=' . $title ),
			'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url ),
			'linkedin' => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url ),
			'email'    => array( __( 'Email', 'benoquik-core' ), 'mailto:?subject=' . $title . '&body=' . $url ),
		);
		$out = '<div class="wp-block-benoquik-share bq-share"><p class="bq-share__label">' . esc_html__( 'Share this article', 'benoquik-core' ) . '</p><ul>';
		$out .= '<li><button type="button" class="bq-share__copy" data-url="' . esc_attr( get_permalink( $post_id ) ) . '">' . esc_html__( 'Copy link', 'benoquik-core' ) . '</button></li>';
		foreach ( $links as $key => $l ) {
			$out .= '<li><a class="bq-share__' . esc_attr( $key ) . '" href="' . esc_url( $l[1] ) . '"' . ( 'email' === $key ? '' : ' target="_blank" rel="noopener noreferrer"' ) . '>' . esc_html( $l[0] ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'benoquik-core' ) . '</span></a></li>';
		}
		return $out . '</ul><p class="bq-share__status" role="status" aria-live="polite"></p></div>';
	}

	/* ========================================================== author profile */

	public static function profile_assets( $hook ) {
		if ( in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'benoquik-profile', BENOQUIK_CORE_URL . 'assets/profile.js', array( 'jquery' ), BENOQUIK_CORE_VERSION, true );
		}
	}

	public static function profile_fields( $user ) {
		$photo = (int) get_user_meta( $user->ID, 'bq_avatar_id', true );
		$img   = $photo ? wp_get_attachment_image( $photo, array( 96, 96 ), false, array( 'style' => 'border-radius:50%' ) ) : '';
		wp_nonce_field( 'bq_profile', 'bq_profile_nonce' );
		?>
		<h2><?php esc_html_e( 'Benoquik author profile', 'benoquik-core' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="bq_credentials"><?php esc_html_e( 'Credentials', 'benoquik-core' ); ?></label></th>
				<td><input type="text" class="regular-text" name="bq_credentials" id="bq_credentials" value="<?php echo esc_attr( get_user_meta( $user->ID, 'bq_credentials', true ) ); ?>" placeholder="MD, Dermatology">
				<p class="description"><?php esc_html_e( 'Shown next to the name on articles and the author page. Use "Biographical Info" above for the short bio.', 'benoquik-core' ); ?></p></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Profile photo', 'benoquik-core' ); ?></th>
				<td>
					<div class="bq-avatar-preview"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput -- core image markup. ?></div>
					<input type="hidden" name="bq_avatar_id" id="bq_avatar_id" value="<?php echo esc_attr( $photo ); ?>">
					<button type="button" class="button bq-avatar-choose"><?php esc_html_e( 'Choose photo', 'benoquik-core' ); ?></button>
					<button type="button" class="button-link bq-avatar-remove"<?php echo $photo ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Remove', 'benoquik-core' ); ?></button>
					<p class="description"><?php esc_html_e( 'Square photo, at least 240 × 240 px. Replaces the Gravatar on the website.', 'benoquik-core' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function save_profile( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['bq_profile_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bq_profile_nonce'] ), 'bq_profile' ) ) {
			return;
		}
		update_user_meta( $user_id, 'bq_credentials', sanitize_text_field( wp_unslash( $_POST['bq_credentials'] ?? '' ) ) );
		update_user_meta( $user_id, 'bq_avatar_id', absint( $_POST['bq_avatar_id'] ?? 0 ) );
	}

	public static function avatar( $args, $id_or_email ) {
		$user_id = 0;
		if ( is_numeric( $id_or_email ) ) {
			$user_id = (int) $id_or_email;
		} elseif ( $id_or_email instanceof \WP_User ) {
			$user_id = $id_or_email->ID;
		} elseif ( $id_or_email instanceof \WP_Post ) {
			$user_id = (int) $id_or_email->post_author;
		} elseif ( $id_or_email instanceof \WP_Comment ) {
			$user_id = (int) $id_or_email->user_id;
		}
		$photo = $user_id ? (int) get_user_meta( $user_id, 'bq_avatar_id', true ) : 0;
		if ( $photo ) {
			$src = wp_get_attachment_image_src( $photo, array( (int) $args['size'] * 2, (int) $args['size'] * 2 ) );
			if ( $src ) {
				$args['url']          = $src[0];
				$args['found_avatar'] = true;
			}
		}
		return $args;
	}

	/* ============================================================== Rank Math */

	/**
	 * Rank Math builds the schema (Article, author, dates, image). We only add
	 * the medical reviewer and the last-reviewed date to its WebPage entity —
	 * reviewedBy / lastReviewed are WebPage properties on schema.org.
	 */
	public static function rank_math_schema( $data, $jsonld = null ) {
		if ( ! is_singular( 'post' ) || ! is_array( $data ) ) {
			return $data;
		}
		$post_id  = get_queried_object_id();
		$reviewer = get_post_meta( $post_id, 'bq_reviewer_name', true );
		$reviewed = get_post_meta( $post_id, 'bq_last_reviewed', true );
		if ( ! $reviewer && ! $reviewed ) {
			return $data;
		}
		foreach ( $data as $key => $entity ) {
			if ( ! is_array( $entity ) || empty( $entity['@type'] ) ) {
				continue;
			}
			$types = (array) $entity['@type'];
			if ( array_intersect( $types, array( 'WebPage', 'MedicalWebPage', 'ItemPage' ) ) ) {
				if ( $reviewer ) {
					$person = array(
						'@type' => 'Person',
						'name'  => $reviewer,
					);
					$cred = get_post_meta( $post_id, 'bq_reviewer_credentials', true );
					$url  = get_post_meta( $post_id, 'bq_reviewer_url', true );
					if ( $cred ) {
						$person['jobTitle'] = $cred;
					}
					if ( $url ) {
						$person['url'] = $url;
					}
					$data[ $key ]['reviewedBy'] = $person;
				}
				if ( $reviewed ) {
					$data[ $key ]['lastReviewed'] = $reviewed;
				}
			}
		}
		return $data;
	}
}
