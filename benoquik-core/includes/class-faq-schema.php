<?php
/**
 * FAQPage schema from visible accordions, added to Rank Math's JSON-LD graph.
 *
 * Sources (only questions actually shown on the page):
 *  - pages & posts: core/details blocks in the content (summary = question,
 *    inner blocks = answer). Blocks hidden with "Hide" (metadata.blockVisibility
 *    = false) — or inside a hidden parent — are skipped.
 *  - products: the "FAQs" accordion of the Medicine information fields, as
 *    rendered by the benoquik/product-info block (question in <strong>, then
 *    the answer). The other accordions ("Overview", "How to use"…) are section
 *    headings, not questions, so they are not marked up as FAQ.
 *
 * Output: ONE FAQPage entity per page, merged into Rank Math's @graph through the
 * `rank_math/json_ld` filter (if Rank Math already has a FAQPage, the questions
 * are merged into it). Nothing is printed when Rank Math is not active.
 *
 * Per page/post/product checkbox "Output FAQ schema from accordions":
 * default ON for the FAQ page, the homepage and products; OFF elsewhere.
 *
 * Also: the homepage and FAQ page use WebPage schema instead of Article.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class FAQ_Schema {

	const META = 'bq_faq_schema'; // '' = default, 'on', 'off'.

	/** Whether the product-info block (with the FAQs accordion) was rendered on this request. */
	private static $product_info_rendered = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_assets' ) );
		add_filter( 'render_block_benoquik/product-info', array( __CLASS__, 'mark_product_info' ) );

		// Products (classic product editor): checkbox in Product data → General.
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'product_field' ), 20 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_field' ) );

		// Rank Math integration.
		add_filter( 'rank_math/schema/default_type', array( __CLASS__, 'default_type' ), 20, 3 );
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'json_ld' ), 120, 2 );
	}

	/* ------------------------------------------------------------- settings */

	public static function register_meta() {
		foreach ( array( 'post', 'page', 'product' ) as $type ) {
			register_post_meta(
				$type,
				self::META,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => static fn( $v ) => in_array( $v, array( 'on', 'off' ), true ) ? $v : '',
					'auth_callback'     => static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', $post_id ),
				)
			);
		}
	}

	/** Is this the FAQ page / homepage? */
	private static function is_faq_page( $post_id ) {
		return 'faq' === get_post_field( 'post_name', $post_id ) && 'page' === get_post_type( $post_id );
	}

	private static function is_home_page( $post_id ) {
		return 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === (int) $post_id;
	}

	/** Default: on for the FAQ page, the homepage and products; off elsewhere. */
	public static function default_enabled( $post_id ) {
		return self::is_faq_page( $post_id ) || self::is_home_page( $post_id ) || 'product' === get_post_type( $post_id );
	}

	public static function enabled( $post_id ) {
		$v = get_post_meta( $post_id, self::META, true );
		if ( 'on' === $v ) {
			return true;
		}
		if ( 'off' === $v ) {
			return false;
		}
		return self::default_enabled( $post_id );
	}

	public static function editor_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}
		wp_enqueue_script(
			'benoquik-faq-panel',
			BENOQUIK_CORE_URL . 'assets/faq-panel.js',
			array( 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' ),
			BENOQUIK_CORE_VERSION,
			true
		);
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		wp_add_inline_script( 'benoquik-faq-panel', 'window.bqFaqDefault = ' . ( $post_id && self::default_enabled( $post_id ) ? 'true' : 'false' ) . ';', 'before' );
	}

	public static function product_field() {
		global $post;
		$on = $post ? self::enabled( $post->ID ) : true;
		echo '<div class="options_group">';
		woocommerce_wp_checkbox(
			array(
				'id'          => '_bq_faq_schema_box',
				'value'       => $on ? 'yes' : 'no',
				'label'       => __( 'Output FAQ schema from accordions', 'benoquik-core' ),
				'description' => __( 'Marks up the questions in the "FAQs" accordion (Medicine information tab) as FAQPage schema via Rank Math.', 'benoquik-core' ),
			)
		);
		echo '</div>';
	}

	public static function save_product_field( $product ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce verifies the product save nonce.
		$product->update_meta_data( self::META, isset( $_POST['_bq_faq_schema_box'] ) ? 'on' : 'off' );
	}

	/* ----------------------------------------------------------- collecting */

	public static function mark_product_info( $html ) {
		if ( '' !== trim( (string) $html ) ) {
			self::$product_info_rendered = true;
		}
		return $html;
	}

	private static function clean( $html ) {
		$text = wp_strip_all_tags( html_entity_decode( (string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	private static function is_hidden( array $block ) {
		return isset( $block['attrs']['metadata']['blockVisibility'] ) && false === $block['attrs']['metadata']['blockVisibility'];
	}

	/** Q&A pairs from visible core/details blocks in block content. */
	public static function from_blocks( $content ) {
		$out  = array();
		$walk = static function ( array $blocks ) use ( &$walk, &$out ) {
			foreach ( $blocks as $b ) {
				if ( empty( $b['blockName'] ) || self::is_hidden( $b ) ) {
					continue; // Hidden blocks (and everything inside them) are not on the page.
				}
				if ( 'core/details' === $b['blockName'] ) {
					$q = preg_match( '#<summary[^>]*>(.*?)</summary>#is', $b['innerHTML'], $m ) ? self::clean( $m[1] ) : '';
					$a = '';
					foreach ( $b['innerBlocks'] as $inner ) {
						if ( ! self::is_hidden( $inner ) ) {
							$a .= ' ' . render_block( $inner );
						}
					}
					$a = self::clean( $a );
					if ( '' !== $q && '' !== $a ) {
						$out[] = array( $q, $a );
					}
					continue;
				}
				if ( ! empty( $b['innerBlocks'] ) ) {
					$walk( $b['innerBlocks'] );
				}
			}
		};
		$walk( parse_blocks( (string) $content ) );
		return $out;
	}

	/**
	 * Q&A pairs from the product's "FAQs" accordion. Each question is the text in
	 * <strong>/<b>/<h3>/<h4> at the start of a paragraph or heading; the answer is
	 * the text that follows, up to the next question.
	 */
	public static function from_product( $product_id ) {
		$html = (string) get_post_meta( $product_id, '_bq_info_faqs', true );
		if ( '' === trim( $html ) ) {
			return array();
		}
		$html  = wpautop( wp_kses_post( $html ) );
		$parts = preg_split( '#(<(?:strong|b|h3|h4)\b[^>]*>.*?</(?:strong|b|h3|h4)>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out   = array();
		$q     = '';
		$a     = '';
		foreach ( $parts as $part ) {
			if ( preg_match( '#^<(?:strong|b|h3|h4)\b#i', $part ) ) {
				$text = self::clean( $part );
				if ( str_ends_with( $text, '?' ) ) {
					if ( '' !== $q && '' !== self::clean( $a ) ) {
						$out[] = array( $q, self::clean( $a ) );
					}
					$q = $text;
					$a = '';
					continue;
				}
			}
			$a .= ' ' . $part;
		}
		if ( '' !== $q && '' !== self::clean( $a ) ) {
			$out[] = array( $q, self::clean( $a ) );
		}
		return $out;
	}

	/** All visible Q&A pairs for the current singular page (deduplicated by question). */
	public static function questions_for( $post_id ) {
		$type  = get_post_type( $post_id );
		$pairs = 'product' === $type
			? ( self::$product_info_rendered ? self::from_product( $post_id ) : array() ) // Only if the accordions are on the page.
			: self::from_blocks( get_post_field( 'post_content', $post_id ) );
		$seen = array();
		$out  = array();
		foreach ( $pairs as $p ) {
			$key = strtolower( $p[0] );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$out[]        = $p;
		}
		return $out;
	}

	/* ------------------------------------------------------------ Rank Math */

	/** Homepage and FAQ page: no Article — Rank Math then outputs only the WebPage entity. */
	public static function default_type( $type, $post_type = '', $post_id = 0 ) {
		$post_id = $post_id ? $post_id : get_queried_object_id();
		if ( 'page' === $post_type && ( self::is_home_page( $post_id ) || self::is_faq_page( $post_id ) ) ) {
			return 'off';
		}
		return $type;
	}

	public static function json_ld( $data, $jsonld = null ) {
		if ( ! is_array( $data ) || ! is_singular() ) {
			return $data;
		}
		$post_id = get_queried_object_id();
		$is_page = self::is_home_page( $post_id ) || self::is_faq_page( $post_id );

		// Homepage / FAQ page: WebPage, not Article (even if an Article was set manually).
		if ( $is_page ) {
			$has_webpage = false;
			foreach ( $data as $key => $entity ) {
				$types = is_array( $entity ) ? (array) ( $entity['@type'] ?? array() ) : array();
				if ( array_intersect( $types, array( 'Article', 'BlogPosting', 'NewsArticle' ) ) ) {
					unset( $data[ $key ] );
				}
				if ( in_array( 'WebPage', $types, true ) ) {
					$has_webpage = true;
				}
			}
			if ( ! $has_webpage ) {
				$url               = get_permalink( $post_id );
				$data['WebPage']   = array(
					'@type'      => 'WebPage',
					'@id'        => $url . '#webpage',
					'url'        => $url,
					'name'       => wp_strip_all_tags( get_the_title( $post_id ) ),
					'inLanguage' => get_bloginfo( 'language' ),
				);
			}
		}

		if ( ! self::enabled( $post_id ) ) {
			return $data;
		}
		$pairs = self::questions_for( $post_id );
		if ( ! $pairs ) {
			return $data;
		}
		$questions = array_map(
			static fn( $p ) => array(
				'@type'          => 'Question',
				'name'           => $p[0],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $p[1],
				),
			),
			$pairs
		);

		// One FAQPage per page: merge into an existing one (e.g. from Rank Math's FAQ block).
		foreach ( $data as $key => $entity ) {
			if ( is_array( $entity ) && in_array( 'FAQPage', (array) ( $entity['@type'] ?? array() ), true ) ) {
				$existing = (array) ( $entity['mainEntity'] ?? array() );
				$names    = array_map( static fn( $q ) => strtolower( $q['name'] ?? '' ), $existing );
				foreach ( $questions as $q ) {
					if ( ! in_array( strtolower( $q['name'] ), $names, true ) ) {
						$existing[] = $q;
					}
				}
				$data[ $key ]['mainEntity'] = array_values( $existing );
				return $data;
			}
		}
		$url                 = get_permalink( $post_id );
		$data['BenoquikFAQ'] = array(
			'@type'      => 'FAQPage',
			'@id'        => $url . '#faq',
			'url'        => $url,
			'inLanguage' => get_bloginfo( 'language' ),
			'mainEntity' => $questions,
		);
		return $data;
	}
}
