<?php
/**
 * Front-end helpers: WhatsApp links and breadcrumbs.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Frontend {

	public static function init() {
		add_filter( 'render_block_core/button', array( __CLASS__, 'whatsapp_button' ), 10, 2 );
		add_filter( 'render_block_core/navigation-link', array( __CLASS__, 'whatsapp_button' ), 10, 2 );
		add_shortcode( 'benoquik_breadcrumbs', array( __CLASS__, 'breadcrumbs' ) );
		add_shortcode( 'benoquik_whatsapp_url', array( __CLASS__, 'whatsapp_url' ) );
	}

	public static function whatsapp_url() {
		$num = Settings::get( 'whatsapp_number' );
		$msg = Settings::get( 'whatsapp_message' );
		return 'https://wa.me/' . rawurlencode( $num ) . ( $msg ? '?text=' . rawurlencode( $msg ) : '' );
	}

	/**
	 * Any Button or menu link with the CSS class "bq-whatsapp" links to the WhatsApp
	 * number saved in Benoquik -> Settings (so it's changed in one place).
	 */
	public static function whatsapp_button( $html, $block ) {
		if ( empty( $block['attrs']['className'] ) || ! str_contains( $block['attrs']['className'], 'bq-whatsapp' ) ) {
			return $html;
		}
		$url = esc_url( self::whatsapp_url() );
		$html = preg_replace( '/href="[^"]*"/', 'href="' . $url . '"', $html, 1 );
		return str_replace( '<a ', '<a target="_blank" rel="noopener" ', $html );
	}

	/**
	 * Breadcrumbs. Uses Rank Math's breadcrumbs (which also output the
	 * BreadcrumbList schema) when Rank Math is active — the theme never adds
	 * its own schema. Enable in Rank Math -> General Settings -> Breadcrumbs.
	 */
	public static function breadcrumbs() {
		if ( is_front_page() ) {
			return '';
		}
		if ( function_exists( 'rank_math_get_breadcrumbs' ) ) {
			return '<nav class="bq-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'benoquik-core' ) . '">' . rank_math_get_breadcrumbs() . '</nav>';
		}
		// Plain fallback (no schema) when Rank Math is not active.
		$crumbs = array( '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'benoquik-core' ) . '</a>' );
		$learn  = (int) get_option( 'page_for_posts' );
		$link   = static fn( $url, $text ) => '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
		$here   = static fn( $text ) => '<span aria-current="page">' . esc_html( $text ) . '</span>';
		if ( is_singular( 'post' ) || is_category() || is_tag() || is_author() ) {
			if ( $learn ) {
				$crumbs[] = $link( get_permalink( $learn ), get_the_title( $learn ) );
			}
			if ( is_singular( 'post' ) ) {
				$cats = get_the_category();
				if ( $cats ) {
					$crumbs[] = $link( get_category_link( $cats[0] ), $cats[0]->name );
				}
				$crumbs[] = $here( get_the_title() );
			} elseif ( is_author() ) {
				$crumbs[] = $here( get_the_author_meta( 'display_name', get_queried_object_id() ) );
			} else {
				$crumbs[] = $here( single_term_title( '', false ) );
			}
		} elseif ( is_home() ) {
			$crumbs[] = $here( $learn ? get_the_title( $learn ) : __( 'Learn', 'benoquik-core' ) );
		} elseif ( is_search() ) {
			$crumbs[] = $here( __( 'Search results', 'benoquik-core' ) );
		} elseif ( is_singular() ) {
			foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $anc ) {
				$crumbs[] = '<a href="' . esc_url( get_permalink( $anc ) ) . '">' . esc_html( get_the_title( $anc ) ) . '</a>';
			}
			$crumbs[] = '<span aria-current="page">' . esc_html( get_the_title() ) . '</span>';
		}
		return '<nav class="bq-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'benoquik-core' ) . '">' . implode( ' <span aria-hidden="true">/</span> ', $crumbs ) . '</nav>';
	}
}
