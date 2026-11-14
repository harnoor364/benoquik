<?php
/**
 * Small Core Web Vitals helpers. No SEO output here (Rank Math owns that).
 *
 * @package Benoquik
 */

defined( 'ABSPATH' ) || exit;

/**
 * Preload the heading font (largest text on most pages = LCP candidate).
 */
add_action(
	'wp_head',
	static function () {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( 'assets/fonts/instrument-serif-latin-400-normal.woff2' ) )
		);
	},
	1
);

/**
 * The hero image is the LCP element on the homepage: never lazy-load it and
 * give it high fetch priority. Any cover block with the class "bq-hero" counts.
 */
add_filter(
	'render_block_core/cover',
	static function ( $html, $block ) {
		if ( empty( $block['attrs']['className'] ) || ! str_contains( $block['attrs']['className'], 'bq-hero' ) ) {
			return $html;
		}
		$html = str_replace( ' loading="lazy"', '', $html );
		return preg_replace( '/<img /', '<img fetchpriority="high" decoding="async" ', $html, 1 );
	},
	10,
	2
);

/**
 * Cover-block images below the fold: lazy-load them (core does not always).
 */
add_filter(
	'render_block_core/cover',
	static function ( $html, $block ) {
		if ( ! empty( $block['attrs']['className'] ) && str_contains( $block['attrs']['className'], 'bq-hero' ) ) {
			return $html;
		}
		if ( str_contains( $html, 'wp-block-cover__image-background' ) && ! str_contains( $html, 'loading=' ) ) {
			$html = preg_replace( '/<img /', '<img loading="lazy" decoding="async" ', $html, 1 );
		}
		return $html;
	},
	11,
	2
);

/** Remove emoji detection script/styles (not needed, saves a request). */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Theme-bundled placeholder images (patterns) have no attachment ID, so WP
 * can't add width/height or lazy-loading. Add them here to avoid layout
 * shift (CLS) and to defer off-screen images.
 */
add_filter(
	'render_block_core/image',
	static function ( $html ) {
		$theme_uri = get_theme_file_uri( 'assets/images/' );
		if ( ! str_contains( $html, $theme_uri ) || str_contains( $html, ' width=' ) ) {
			return $html;
		}
		return preg_replace_callback(
			'/<img ([^>]*?)src="([^"]+)"/',
			static function ( $m ) use ( $theme_uri ) {
				$file = get_theme_file_path( 'assets/images/' . basename( wp_parse_url( $m[2], PHP_URL_PATH ) ) );
				$size = is_readable( $file ) ? wp_getimagesize( $file ) : false;
				$dims = $size ? sprintf( 'width="%d" height="%d" ', $size[0], $size[1] ) : '';
				return '<img ' . $m[1] . $dims . 'loading="lazy" decoding="async" src="' . $m[2] . '"';
			},
			$html,
			1
		);
	}
);
