<?php
/**
 * One-click demo content: Home page (built from the theme's patterns),
 * placeholder pages, and 3 variable products.
 *
 * Run from WordPress admin -> Benoquik -> Import demo content,
 * or on the command line: wp eval 'Benoquik\Demo_Import::run();'
 *
 * Safe to run more than once: existing pages/products (matched by slug)
 * are left untouched unless "overwrite home" is chosen.
 *
 * ALL PRICES, IMAGES AND TEXT ARE PLACEHOLDERS.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Demo_Import {

	/** Homepage sections, top to bottom (theme pattern slugs). */
	const HOME_PATTERNS = array(
		'benoquik/hero',
		'benoquik/intro-products',
		'benoquik/split-dermatologists',
		'benoquik/bento-how-it-works',
		'benoquik/quote-dermatologist',
		'benoquik/marquee',
		'benoquik/patient-stories',
		'benoquik/faq',
		'benoquik/cta-pharmacist',
	);

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_action( 'admin_post_benoquik_demo_import', array( __CLASS__, 'handle' ) );
	}

	public static function menu() {
		add_submenu_page( 'benoquik', __( 'Import demo content', 'benoquik-core' ), __( 'Import demo content', 'benoquik-core' ), 'manage_options', 'benoquik-demo', array( __CLASS__, 'page' ) );
	}

	public static function page() {
		$log = get_transient( 'benoquik_demo_log' );
		echo '<div class="wrap"><h1>' . esc_html__( 'Import demo content', 'benoquik-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Creates the homepage, placeholder pages and 3 demo products. Existing pages and products with the same address are kept. Requires the Benoquik theme and WooCommerce to be active.', 'benoquik-core' ) . '</p>';
		if ( $log ) {
			echo '<div class="notice notice-success"><p>' . wp_kses_post( implode( '<br>', (array) $log ) ) . '</p></div>';
			delete_transient( 'benoquik_demo_log' );
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'bq_demo' );
		echo '<input type="hidden" name="action" value="benoquik_demo_import">';
		echo '<p><label><input type="checkbox" name="overwrite_home" value="1"> ' . esc_html__( 'Rebuild the Home page from the original sections (replaces your homepage edits)', 'benoquik-core' ) . '</label></p>';
		submit_button( __( 'Import demo content', 'benoquik-core' ) );
		echo '</form></div>';
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'bq_demo' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'benoquik-core' ) );
		}
		$log = self::run( ! empty( $_POST['overwrite_home'] ) );
		set_transient( 'benoquik_demo_log', $log, 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=benoquik-demo' ) );
		exit;
	}

	/**
	 * @param bool $overwrite_home Rebuild Home page content even if it exists.
	 * @return string[] Log lines.
	 */
	public static function run( $overwrite_home = false ) {
		$log = array();

		// ---- Home page from patterns -------------------------------------
		$home_content = self::home_content();
		$home         = get_page_by_path( 'home' );
		if ( ! $home ) {
			$home_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'Home',
					'post_name'    => 'home',
					'post_content' => $home_content,
				)
			);
			$log[] = 'Created Home page.';
		} else {
			$home_id = $home->ID;
			if ( $overwrite_home ) {
				wp_update_post( array( 'ID' => $home_id, 'post_content' => $home_content ) );
				$log[] = 'Rebuilt Home page.';
			}
		}
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );

		// ---- Blog ("Learn") page -------------------------------------------
		$learn_id = self::ensure_page( 'learn', 'Learn', '' );
		update_option( 'page_for_posts', $learn_id );

		// ---- Placeholder pages (full content arrives in the next build step) ---
		$stub = '<!-- wp:paragraph --><p>This page is a placeholder. Edit it in Pages → %s.</p><!-- /wp:paragraph -->';
		foreach ( array(
			'about'                        => 'About',
			'contact'                      => 'Contact',
			'faq'                          => 'FAQ',
			'upload-prescription'          => 'Upload Prescription',
			'track-order'                  => 'Track Order',
			'terms-and-conditions'         => 'Terms & Conditions',
			'returns-refunds-cancellation' => 'Returns, Refunds & Cancellation',
			'shipping-policy'              => 'Shipping Policy',
			'grievance-officer'            => 'Grievance Officer',
			'licences-compliance'          => 'Licences & Compliance',
			'medical-disclaimer'           => 'Medical Disclaimer',
		) as $slug => $title ) {
			self::ensure_page( $slug, $title, sprintf( $stub, $title ) );
		}

		// ---- Products --------------------------------------------------------
		if ( class_exists( 'WC_Product_Variable' ) ) {
			$log = array_merge( $log, self::products() );
		} else {
			$log[] = 'WooCommerce is not active — products skipped.';
		}

		return $log;
	}

	/** Expand each homepage pattern into real, editable blocks. */
	public static function home_content() {
		$registry = \WP_Block_Patterns_Registry::get_instance();
		$out      = array();
		foreach ( self::HOME_PATTERNS as $slug ) {
			$pattern = $registry->get_registered( $slug );
			if ( $pattern && ! empty( $pattern['content'] ) ) {
				$out[] = trim( $pattern['content'] );
			}
		}
		return implode( "\n\n", $out );
	}

	private static function ensure_page( $slug, $title, $content ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			return $page->ID;
		}
		return wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			)
		);
	}

	/** Import a theme placeholder image into the Media Library once. */
	private static function import_image( $file, $alt ) {
		$existing = get_posts(
			array(
				'post_type'   => 'attachment',
				'meta_key'    => '_bq_demo_source',
				'meta_value'  => $file,
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		if ( $existing ) {
			return $existing[0];
		}
		$src = get_theme_file_path( 'assets/images/' . $file );
		if ( ! is_readable( $src ) ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam( $file );
		copy( $src, $tmp );
		$id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), 0 );
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		update_post_meta( $id, '_bq_demo_source', $file );
		return $id;
	}

	private static function products() {
		$log  = array();
		$term = term_exists( 'prescription-creams', 'product_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( 'Prescription cream', 'product_cat', array( 'slug' => 'prescription-creams' ) );
		}
		$cat_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;

		$desc = '<p>Benoquik contains monobenzone 20% w/w, a prescription depigmenting agent for people with <strong>extensive vitiligo</strong>. It permanently removes pigment from the remaining normally pigmented skin so that skin tone becomes even.</p><p><strong>Benoquik is not a cure for vitiligo and does not restore colour.</strong> Use only under the supervision of a dermatologist.</p>';
		$short = 'Prescription monobenzone 20% w/w cream for extensive vitiligo. Permanently removes remaining pigment for an even skin tone. Not a cure; does not restore colour. ℞ Prescription required.';

		// [slug, name, subtitle, image, attribute name, [ [option, price, mrp], ... ] ]  — PLACEHOLDER PRICES.
		$products = array(
			array( 'benoquik-cream-30g', 'Benoquik 20% w/w Cream', '30 g tube', 'product-30g.webp', 'Pack size', array( array( '1 tube (30 g)', 649, 749 ), array( '2 tubes (2 × 30 g)', 1249, 1498 ) ) ),
			array( 'benoquik-2-pack', 'Benoquik 2-Pack', '2 × 30 g tubes', 'product-2pack.webp', 'Bundle', array( array( 'Cream only', 1249, 1498 ), array( 'With SPF 50 sunscreen', 1649, 1948 ) ) ),
			array( 'benoquik-3-month-pack', 'Benoquik 3-Month Pack', '3 × 30 g tubes', 'product-3month.webp', 'Bundle', array( array( 'Cream only', 1799, 2247 ), array( 'With SPF 50 sunscreen', 2199, 2697 ) ) ),
		);

		foreach ( $products as $i => $p ) {
			list( $slug, $name, $subtitle, $img, $attr_name, $options ) = $p;
			if ( get_page_by_path( $slug, OBJECT, 'product' ) ) {
				$log[] = "Product exists: {$name}";
				continue;
			}
			$image_id = self::import_image( $img, $name . ' (placeholder image)' );

			$product = new \WC_Product_Variable();
			$product->set_name( $name );
			$product->set_slug( $slug );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_description( $desc );
			$product->set_short_description( $short );
			$product->set_category_ids( array( $cat_id ) );
			$product->set_menu_order( $i + 1 );
			$product->set_sku( 'BQ-' . strtoupper( substr( md5( $slug ), 0, 6 ) ) );
			if ( $image_id ) {
				$product->set_image_id( $image_id );
			}
			$attr = new \WC_Product_Attribute();
			$attr->set_name( $attr_name );
			$attr->set_options( wp_list_pluck( $options, 0 ) );
			$attr->set_visible( true );
			$attr->set_variation( true );
			$product->set_attributes( array( $attr ) );
			$product->update_meta_data( '_bq_requires_prescription', 'yes' );
			$product->update_meta_data( '_bq_subtitle', $subtitle );
			foreach ( Product_Fields::default_info() as $key => $html ) {
				$product->update_meta_data( $key, $html );
			}
			$product->set_reviews_allowed( false );
			$product->set_default_attributes( array( sanitize_title( $attr_name ) => $options[0][0] ) );
			$product_id = $product->save();

			foreach ( $options as $o ) {
				$v = new \WC_Product_Variation();
				$v->set_parent_id( $product_id );
				$v->set_attributes( array( sanitize_title( $attr_name ) => $o[0] ) );
				$v->set_regular_price( (string) $o[2] ); // MRP (shown struck through).
				$v->set_sale_price( (string) $o[1] );    // Selling price.
				$v->set_manage_stock( false );
				$v->set_stock_status( 'instock' );
				$v->save();
			}
			\WC_Product_Variable::sync( $product_id );
			$log[] = "Created product: {$name}";
		}
		return $log;
	}
}
