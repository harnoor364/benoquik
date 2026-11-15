<?php
/**
 * Store data repair tasks. Run them from Local's site shell:
 *
 *   wp benoquik doctor              # report problems, change nothing
 *   wp benoquik fix-encoding        # repair â€” / Ã— style characters
 *   wp benoquik repair-products     # pack sizes, prices, subtitles, info, reviews off
 *   wp benoquik rebuild-home        # rebuild the Home page from the theme's sections
 *   wp benoquik migrate-v030        # real product: 20 g packs, names, images, SKUs
 *   wp benoquik upgrade             # all of the above + cache/lookup-table flush
 *
 * Or from WordPress admin -> Benoquik -> Repair store data.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Repair {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 40 );
		add_action( 'admin_post_benoquik_repair', array( __CLASS__, 'handle' ) );
	}

	public static function menu() {
		add_submenu_page( 'benoquik', __( 'Repair store data', 'benoquik-core' ), __( 'Repair store data', 'benoquik-core' ), 'manage_options', 'benoquik-repair', array( __CLASS__, 'page' ) );
	}

	public static function page() {
		$log = get_transient( 'benoquik_repair_log' );
		echo '<div class="wrap"><h1>' . esc_html__( 'Repair store data', 'benoquik-core' ) . '</h1>';
		echo '<p><strong>' . esc_html__( 'Back up your database first', 'benoquik-core' ) . '</strong> ' . esc_html__( '(e.g. with UpdraftPlus, or "wp db export" in Local’s site shell).', 'benoquik-core' ) . '</p>';
		echo '<p>' . esc_html__( 'Fixes garbled characters (â€” instead of —), product pack sizes and prices, product subtitles, empty medicine-information boxes, and turns reviews off.', 'benoquik-core' ) . '</p>';
		if ( $log ) {
			echo '<div class="notice notice-info"><pre style="white-space:pre-wrap">' . esc_html( implode( "\n", (array) $log ) ) . '</pre></div>';
			delete_transient( 'benoquik_repair_log' );
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'bq_repair' );
		echo '<input type="hidden" name="action" value="benoquik_repair">';
		echo '<p><label><input type="checkbox" name="rebuild_home" value="1"> ' . esc_html__( 'Also rebuild the Home page from the theme’s sections (replaces your homepage edits)', 'benoquik-core' ) . '</label></p>';
		submit_button( __( 'Run repair', 'benoquik-core' ) );
		echo '</form></div>';
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'bq_repair' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'benoquik-core' ) );
		}
		$log = self::upgrade( ! empty( $_POST['rebuild_home'] ) );
		set_transient( 'benoquik_repair_log', $log, 300 );
		wp_safe_redirect( admin_url( 'admin.php?page=benoquik-repair' ) );
		exit;
	}

	/** Everything, in the right order. */
	public static function upgrade( $rebuild_home = true ) {
		$log = array( '== Encoding ==' );
		$log = array_merge( $log, self::fix_encoding() );
		$log[] = '== Real product (v0.3.0) ==';
		$log = array_merge( $log, Catalogue::apply( false ) );
		$log[] = '== Products ==';
		$log = array_merge( $log, self::repair_products() );
		if ( $rebuild_home ) {
			$log[] = '== Home page ==';
			$log = array_merge( $log, self::rebuild_home() );
		}
		$log[] = '== Caches ==';
		$log = array_merge( $log, self::flush() );
		$log[] = '== Check ==';
		$log = array_merge( $log, self::doctor() );
		return $log;
	}

	public static function fix_encoding( $dry_run = false ) {
		$log = array();
		if ( 'UTF-8' !== get_option( 'blog_charset' ) && ! $dry_run ) {
			update_option( 'blog_charset', 'UTF-8' );
			$log[] = 'Set blog_charset to UTF-8.';
		}
		// Transients are rebuilt automatically; drop them first so we don't fix throwaway data.
		if ( ! $dry_run ) {
			self::delete_transients();
		}
		$t = Encoding::repair_database(
			$dry_run,
			static function ( $line ) use ( &$log ) {
				$log[] = $line;
			}
		);
		$log[] = sprintf(
			'%s %d value(s) in %d row(s). Serialized values with recalculated lengths: %d. Still broken: %d.',
			$dry_run ? 'Would fix' : 'Fixed',
			$t['values'],
			$t['rows'],
			$t['recounted'],
			$t['broken']
		);
		if ( $t['skipped_tables'] ) {
			$log[] = 'Skipped (no primary key): ' . implode( ', ', $t['skipped_tables'] );
		}
		wp_cache_flush();
		return $log;
	}

	/** Normalise a label for loose comparison. */
	private static function norm( $s ) {
		return strtolower( preg_replace( '/\s+/u', ' ', trim( Encoding::fix_text( (string) $s ) ) ) );
	}

	public static function repair_products() {
		$log = array();
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array( 'WooCommerce is not active.' );
		}
		update_option( 'woocommerce_enable_reviews', 'no' );
		update_option( 'woocommerce_enable_review_rating', 'no' );

		$ids = get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			wp_update_post( array( 'ID' => $id, 'comment_status' => 'closed', 'ping_status' => 'closed' ) );
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			$name = $product->get_name();

			// Pack size belongs in the subtitle, not the title ("Name — pack size").
			if ( '' === (string) get_post_meta( $id, '_bq_subtitle', true ) && preg_match( '/^(.+?)\s+[—–-]\s+(.+)$/u', $name, $m ) ) {
				$product->set_name( trim( $m[1] ) );
				$product->update_meta_data( '_bq_subtitle', trim( $m[2] ) );
				$log[] = "#{$id}: title \"{$m[1]}\" + subtitle \"{$m[2]}\"";
			}
			// Empty medicine-information boxes get the default text.
			foreach ( Product_Fields::default_info() as $key => $html ) {
				if ( '' === trim( (string) get_post_meta( $id, $key, true ) ) ) {
					$product->update_meta_data( $key, $html );
				}
			}
			if ( '' === (string) get_post_meta( $id, '_bq_requires_prescription', true ) ) {
				$product->update_meta_data( '_bq_requires_prescription', 'yes' );
			}

			if ( $product->is_type( 'variable' ) ) {
				$log = array_merge( $log, self::repair_variable( $product ) );
			}
			$product->save();
			if ( $product->is_type( 'variable' ) ) {
				\WC_Product_Variable::sync( $id );
			}
			wc_delete_product_transients( $id );
		}
		$log[] = sprintf( 'Checked %d product(s). Reviews turned off.', count( $ids ) );
		return $log;
	}

	/** Make every variation match the parent's attribute options exactly. */
	private static function repair_variable( \WC_Product_Variable $product ) {
		$log   = array();
		$id    = $product->get_id();
		$attrs = $product->get_attributes();
		if ( ! $attrs ) {
			$raw = get_post_meta( $id, '_product_attributes', true );
			return array( "#{$id}: ! no readable attributes" . ( is_string( $raw ) && $raw ? ' (stored value is corrupt — run fix-encoding first)' : '' ) );
		}
		// Clean the parent's option labels.
		$options = array();
		foreach ( $attrs as $key => $attr ) {
			if ( ! $attr->is_taxonomy() ) {
				$attr->set_options( array_map( array( Encoding::class, 'fix_text' ), $attr->get_options() ) );
				$attr->set_variation( true );
				$attr->set_visible( true );
				$options[ $key ] = $attr->get_options();
			} else {
				$options[ $key ] = wc_get_product_terms( $id, $attr->get_name(), array( 'fields' => 'slugs' ) );
			}
		}
		$product->set_attributes( $attrs );

		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( ! $v ) {
				continue;
			}
			$vattrs  = $v->get_attributes();
			$changed = false;
			foreach ( $options as $key => $opts ) {
				$current = isset( $vattrs[ $key ] ) ? (string) $vattrs[ $key ] : '';
				if ( in_array( $current, $opts, true ) ) {
					continue;
				}
				$match = '';
				foreach ( $opts as $o ) {
					if ( self::norm( $o ) === self::norm( $current ) ) {
						$match = $o;
						break;
					}
				}
				if ( '' === $match && '' !== $current ) {
					$log[] = "#{$vid}: ! variation value \"{$current}\" matches no option of #{$id}";
					continue;
				}
				$vattrs[ $key ] = $match; // '' means "any".
				$changed        = true;
			}
			if ( $changed ) {
				$v->set_attributes( $vattrs );
				$log[] = "#{$vid}: variation attributes re-linked";
			}
			if ( 'publish' !== $v->get_status() ) {
				$v->set_status( 'publish' );
				$log[] = "#{$vid}: enabled";
			}
			if ( '' === (string) $v->get_regular_price() ) {
				$log[] = "#{$vid}: ! has no price — set one in Products → edit → Variations";
			}
			if ( ! $v->get_manage_stock() && 'instock' !== $v->get_stock_status() ) {
				$v->set_stock_status( 'instock' );
				$log[] = "#{$vid}: set in stock";
			}
			$v->save();
		}
		// Pre-select the first pack so "Add to cart" is ready on page load.
		if ( ! $product->get_default_attributes() ) {
			$defaults = array();
			foreach ( $options as $key => $opts ) {
				if ( $opts ) {
					$defaults[ $key ] = reset( $opts );
				}
			}
			$product->set_default_attributes( $defaults );
		}
		$log[] = sprintf( '#%d: %d variation(s) OK', $id, count( $product->get_children() ) );
		return $log;
	}

	public static function migrate_v030() {
		$log = Catalogue::apply( false );
		$log = array_merge( $log, self::flush() );
		return $log;
	}

	public static function rebuild_home() {
		return Demo_Import::run( true );
	}

	private static function delete_transients() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%' OR option_name LIKE '\\_site\\_transient\\_%'" ); // phpcs:ignore
	}

	public static function flush() {
		$log = array();
		self::delete_transients();
		wp_cache_flush();
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}
		// Rebuild WooCommerce lookup tables now (prices / stock / attributes used by filters & sorting).
		if ( function_exists( 'wc_update_product_lookup_tables_column' ) ) {
			foreach ( array( 'min_max_price', 'stock_quantity', 'sku', 'stock_status', 'average_rating', 'total_sales', 'downloadable_virtual', 'onsale' ) as $col ) {
				wc_update_product_lookup_tables_column( $col );
			}
			$log[] = 'Rebuilt product lookup table.';
		}
		if ( function_exists( 'wc_get_container' ) && class_exists( '\Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore' ) ) {
			$store = wc_get_container()->get( \Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore::class );
			foreach ( wc_get_products( array( 'limit' => -1, 'return' => 'objects' ) ) as $p ) {
				$store->create_data_for_product( $p );
			}
			$log[] = 'Rebuilt product attributes lookup table.';
		}
		flush_rewrite_rules( false );
		$log[] = 'Transients, object cache and rewrite rules flushed.';
		return $log;
	}

	/** Read-only health check. */
	public static function doctor() {
		$log = array();
		foreach ( Encoding::count_remaining() as $needle => $n ) {
			$log[] = sprintf( 'Rows containing "%s": %d', $needle, $n );
		}
		$log[] = 'blog_charset: ' . get_option( 'blog_charset' );
		if ( class_exists( 'WooCommerce' ) ) {
			foreach ( wc_get_products( array( 'limit' => -1, 'type' => 'variable' ) ) as $p ) {
				$ok = 0;
				foreach ( $p->get_available_variations( 'objects' ) as $v ) {
					$ok += ( $v->is_purchasable() && $v->is_in_stock() ) ? 1 : 0;
				}
				$log[] = sprintf( '%s: %d attribute(s), %d purchasable variation(s)%s', $p->get_name(), count( $p->get_attributes() ), $ok, $ok ? '' : '  <-- PROBLEM' );
			}
			$log[] = 'Reviews: ' . get_option( 'woocommerce_enable_reviews' );
		}
		return $log;
	}
}
