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
		'benoquik/shipping-strip',
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
			'shipping-availability'        => 'Shipping & Availability',
			'grievance-officer'            => 'Grievance Officer',
			'licenses-compliance'          => 'Licenses & Compliance',
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
		return Catalogue::apply( true );
	}
}
