<?php
/**
 * Theme supports, assets and pattern categories.
 *
 * @package Benoquik
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_editor_style( 'assets/css/theme.css' );
	}
);

/**
 * Front-end CSS + a tiny vanilla JS file (sticky header + scroll reveal).
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'benoquik', get_theme_file_uri( 'assets/css/theme.css' ), array(), BENOQUIK_THEME_VERSION );
		wp_enqueue_script(
			'benoquik',
			get_theme_file_uri( 'assets/js/theme.js' ),
			array(),
			BENOQUIK_THEME_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		// Checkout-only helper (numeric keypads / autocomplete); not loaded anywhere else.
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			wp_enqueue_script(
				'benoquik-checkout',
				get_theme_file_uri( 'assets/js/checkout.js' ),
				array(),
				BENOQUIK_THEME_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}
);

/**
 * Pattern categories so the owner can find every section in the inserter
 * (Editor -> "+" -> Patterns -> "Benoquik: Homepage").
 */
add_action(
	'init',
	static function () {
		register_block_pattern_category( 'benoquik-home', array( 'label' => __( 'Benoquik: Homepage sections', 'benoquik' ) ) );
		register_block_pattern_category( 'benoquik-pages', array( 'label' => __( 'Benoquik: Page sections', 'benoquik' ) ) );
	}
);

/**
 * Helper used by patterns to reference bundled placeholder images.
 *
 * @param string $file File name inside assets/images.
 */
function benoquik_img( $file ) {
	return esc_url( get_theme_file_uri( 'assets/images/' . $file ) );
}

/**
 * Adds "has-hero" to <body> on the homepage so the header can sit
 * transparently over the hero image.
 */
add_filter(
	'body_class',
	static function ( $classes ) {
		if ( is_front_page() ) {
			$classes[] = 'bq-has-hero';
		}
		return $classes;
	}
);

/**
 * Enables the scroll-reveal styles only when JS runs (no-JS users see
 * everything immediately). A safety timer reveals everything after 3s in
 * case the deferred script fails to load.
 */
add_action(
	'wp_head',
	static function () {
		echo "<script>document.documentElement.classList.add('js-reveal');setTimeout(function(){document.querySelectorAll('.bq-reveal').forEach(function(e){e.classList.add('is-visible')})},3000);</script>\n";
	},
	2
);
