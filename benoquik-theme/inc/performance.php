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
		foreach ( array( 'instrument-serif-latin-400-normal.woff2', 'inter-latin-wght-normal.woff2' ) as $font ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( get_theme_file_uri( 'assets/fonts/' . $font ) )
			);
		}
		// Homepage: preload the LCP image (the hero product image; fall back to the hero background).
		if ( is_front_page() ) {
			$content = (string) get_post_field( 'post_content', (int) get_option( 'page_on_front' ) );
			if ( preg_match( '/<figure class="wp-block-image[^"]*bq-hero__image[^"]*"><img src="([^"]+)"/', $content, $m )
				|| preg_match( '/<!-- wp:cover \{"url":"([^"]+)"[^}]*"className":"bq-hero/', $content, $m ) ) {
				printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( $m[1] ) );
			}
		}
		// Product pages: preload the main product photo (responsive).
		if ( function_exists( 'is_product' ) && is_product() ) {
			$product = wc_get_product( get_queried_object_id() );
			$img_id  = $product ? $product->get_image_id() : 0;
			if ( $img_id ) {
				$src    = wp_get_attachment_image_src( $img_id, 'woocommerce_single' );
				$srcset = wp_get_attachment_image_srcset( $img_id, 'woocommerce_single' );
				if ( $src ) {
					printf(
						'<link rel="preload" as="image" href="%s"%s imagesizes="(max-width: 781px) 100vw, 54vw" fetchpriority="high">' . "\n",
						esc_url( $src[0] ),
						$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : ''
					);
				}
			}
		}
	},
	1
);

/**
 * The hero product image (class "bq-hero__image") is above the fold: never
 * lazy-load it.
 */
add_filter(
	'render_block_core/image',
	static function ( $html, $block ) {
		if ( empty( $block['attrs']['className'] ) || ! str_contains( $block['attrs']['className'], 'bq-hero__image' ) ) {
			return $html;
		}
		$html = str_replace( array( ' loading="lazy"', ' fetchpriority="high"' ), '', $html );
		return preg_replace( '/<img /', '<img fetchpriority="high" ', $html, 1 );
	},
	20,
	2
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
		return str_replace( ' loading="lazy"', '', $html );
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


/**
 * jQuery is only needed by WooCommerce's classic product form; load it at the
 * end of the page instead of blocking the first paint.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_admin() ) {
			return;
		}
		foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate' ) as $handle ) {
			wp_scripts()->add_data( $handle, 'group', 1 );
		}
	},
	100
);

/**
 * WooCommerce's classic stylesheets are only needed on shop, product, cart,
 * checkout and account pages (the homepage cards use the block styles).
 */
add_filter(
	'woocommerce_enqueue_styles',
	static function ( $styles ) {
		if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
			return $styles;
		}
		return array();
	}
);

/** First gallery image on product pages: high priority, never lazy. */
add_filter(
	'woocommerce_gallery_image_html_attachment_image_params',
	static function ( $params, $attachment_id, $image_size, $main_image ) {
		if ( $main_image ) {
			$params['fetchpriority'] = 'high';
			$params['loading']       = 'eager';
		}
		return $params;
	},
	10,
	4
);

/**
 * Inline small block stylesheets into the page instead of separate requests
 * (fewer render-blocking files on mobile). WordPress default is 20 KB total.
 */
add_filter( 'styles_inline_size_limit', static fn() => 60000 );
