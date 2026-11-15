<?php
/**
 * The real Benoquik product range (from the packaging):
 *   Benoquik® — Monobenzone Cream USP 20% w/w — 20 g tube — ℞ only
 *   Marketed by Quik Pharmaceuticals.
 *
 * Used by the demo import (new sites) and by `wp benoquik migrate-v030`
 * (existing sites: updates the three products in place, no duplicates).
 *
 * PRICES ARE PLACEHOLDERS — see PLACEHOLDERS.md.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Catalogue {

	const ALT_SINGLE = 'Benoquik Monobenzone Cream USP 20% — 20 g tube and box';

	/**
	 * slug => spec. 'old_slugs' lets existing products be found and renamed.
	 * Option rows: [ label, price (USD), compare-at price or null, SKU suffix ].
	 * A compare-at price makes the item "on sale" (shown struck through).
	 * 'weight' is in grams, 'dimensions' in cm (L × W × H) — PLACEHOLDERS.
	 */
	public static function products() {
		return array(
			'benoquik-cream-20g' => array(
				'old_slugs' => array( 'benoquik-cream-30g' ),
				'name'      => 'Benoquik Cream 20 g',
				'subtitle'  => 'Monobenzone Cream USP 20% w/w · 1 tube (20 g)',
				'sku'       => 'BQ-20G',
				'image'     => 'benoquik-20g.webp',
				'alt'       => self::ALT_SINGLE,
				'attribute' => 'Pack size',
				'options'   => array(
					array( '1 tube (20 g)', 29, null, '1' ),
					array( '2 tubes (2 × 20 g)', 55, null, '2' ),
				),
				'weight'    => array( 45, 90 ),
				'dims'      => array( 15, 4, 3 ),
				'order'     => 1,
			),
			'benoquik-2-pack'    => array(
				'old_slugs' => array(),
				'name'      => 'Benoquik 2-Pack',
				'subtitle'  => 'Monobenzone Cream USP 20% w/w · 2 × 20 g tubes',
				'sku'       => 'BQ-20G-X2',
				'image'     => 'benoquik-2-pack.webp',
				'alt'       => self::ALT_SINGLE . ' (pack of 2)',
				'attribute' => 'Bundle',
				'options'   => array(
					array( 'Cream only', 55, null, 'C' ),
					array( 'With SPF 50 sunscreen', 105, null, 'SPF' ),
				),
				'weight'    => array( 90, 190 ),
				'dims'      => array( 15, 8, 4 ),
				'order'     => 2,
			),
			'benoquik-3-pack'    => array(
				'old_slugs' => array( 'benoquik-3-month-pack' ),
				'name'      => 'Benoquik 3-Pack',
				'subtitle'  => 'Monobenzone Cream USP 20% w/w · 3 × 20 g tubes',
				'sku'       => 'BQ-20G-X3',
				'image'     => 'benoquik-3-pack.webp',
				'alt'       => self::ALT_SINGLE . ' (pack of 3)',
				'attribute' => 'Bundle',
				'options'   => array(
					array( 'Cream only', 79, null, 'C' ),
					array( 'With SPF 50 sunscreen', 149, null, 'SPF' ),
				),
				'weight'    => array( 135, 235 ),
				'dims'      => array( 15, 12, 4 ),
				'order'     => 3,
			),
		);
	}

	public static function short_description() {
		return 'Monobenzone Cream USP 20% w/w (20 g) for people with extensive vitiligo. Used under a dermatologist’s supervision, it permanently removes remaining pigment so skin tone becomes even. It is not a cure and does not restore color.';
	}

	public static function description() {
		return '<p><strong>Benoquik®</strong> contains <strong>Monobenzone Cream USP 20% w/w</strong> in a 20 g tube. It is a prescription depigmenting cream for people with <strong>extensive vitiligo</strong>: it permanently removes pigment from the remaining normally pigmented skin so that skin tone becomes even.</p><p><strong>Benoquik is not a cure for vitiligo and does not restore color.</strong> Use only under the supervision of a dermatologist.</p><p>Marketed by Quik Pharmaceuticals.</p>';
	}

	public static function admin_note() {
		return 'Prices for every pack are PLACEHOLDER PRICES in USD — update them in Product data → Variations before launch (see PLACEHOLDERS.md). Weights/dimensions are placeholders too. This note is only visible in the admin.';
	}

	/** Price row → regular / sale price. */
	private static function set_price( \WC_Product_Variation $v, array $o ) {
		if ( null !== $o[2] && (float) $o[2] > (float) $o[1] ) {
			$v->set_regular_price( (string) $o[2] );
			$v->set_sale_price( (string) $o[1] );
		} else {
			$v->set_regular_price( (string) $o[1] );
			$v->set_sale_price( '' );
		}
	}

	/**
	 * International migration: set the USD placeholder prices, weights (g) and
	 * dimensions (cm) on the existing variations, matched by label.
	 */
	public static function apply_prices_and_shipping() {
		$log = array();
		foreach ( self::products() as $slug => $spec ) {
			$product = self::find( $slug, $spec );
			if ( ! $product || ! $product->is_type( 'variable' ) ) {
				$log[] = "Prices: product {$spec['name']} not found — skipped";
				continue;
			}
			$product->set_short_description( self::short_description() );
			$product->update_meta_data( '_bq_admin_note', self::admin_note() );
			$product->set_weight( (string) $spec['weight'][0] );
			list( $l, $w, $h ) = $spec['dims'];
			$product->set_length( (string) $l );
			$product->set_width( (string) $w );
			$product->set_height( (string) $h );
			$product->save();
			foreach ( $product->get_children() as $cid ) {
				$v   = wc_get_product( $cid );
				$val = $v ? (string) current( $v->get_attributes() ) : '';
				foreach ( $spec['options'] as $i => $o ) {
					if ( sanitize_title( $o[0] ) === sanitize_title( $val ) || $o[0] === $val ) {
						self::set_price( $v, $o );
						$v->set_weight( (string) $spec['weight'][ $i ] );
						$v->save();
						$log[] = sprintf( 'Price: %s — %s = $%s (weight %d g)', $spec['name'], $o[0], $o[1], $spec['weight'][ $i ] );
					}
				}
			}
			\WC_Product_Variable::sync( $product->get_id() );
			wc_delete_product_transients( $product->get_id() );
		}
		return $log;
	}

	/** Import (once) a bundled product image into the Media Library. */
	public static function image_id( $file, $alt ) {
		$existing = get_posts(
			array(
				'post_type'   => 'attachment',
				'meta_key'    => '_bq_demo_source', // phpcs:ignore
				'meta_value'  => $file, // phpcs:ignore
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		if ( $existing ) {
			update_post_meta( $existing[0], '_wp_attachment_image_alt', $alt );
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
		$id = media_handle_sideload(
			array(
				'name'     => $file,
				'tmp_name' => $tmp,
			),
			0,
			$alt
		);
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		update_post_meta( $id, '_bq_demo_source', $file );
		return $id;
	}

	/** Find an existing product by its current or any old slug. */
	public static function find( $slug, $spec ) {
		foreach ( array_merge( array( $slug ), $spec['old_slugs'] ) as $s ) {
			$post = get_page_by_path( $s, OBJECT, 'product' );
			if ( $post ) {
				return wc_get_product( $post->ID );
			}
		}
		return null;
	}

	/**
	 * Create or update every product to match the spec.
	 *
	 * @param bool $create_missing Create products that don't exist yet.
	 * @return string[] Log.
	 */
	public static function apply( $create_missing = true ) {
		$log = array();
		if ( ! class_exists( 'WC_Product_Variable' ) ) {
			return array( 'WooCommerce is not active.' );
		}
		$term = term_exists( 'prescription-creams', 'product_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( 'Prescription cream', 'product_cat', array( 'slug' => 'prescription-creams' ) );
		}
		$cat_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;

		foreach ( self::products() as $slug => $spec ) {
			$product = self::find( $slug, $spec );
			$is_new  = ! $product;
			if ( $is_new && ! $create_missing ) {
				$log[] = "Missing product (not created): {$spec['name']}";
				continue;
			}
			if ( $is_new ) {
				$product = new \WC_Product_Variable();
				$product->set_status( 'publish' );
				$product->set_category_ids( array( $cat_id ) );
				$product->set_description( self::description() );
			}
			if ( ! $product->is_type( 'variable' ) ) {
				$log[] = "#{$product->get_id()} is not a variable product — skipped";
				continue;
			}
			$old_slug = $product->get_slug();
			$product->set_name( $spec['name'] );
			$product->set_slug( $slug ); // WordPress keeps the old address as a redirect.
			$product->set_menu_order( $spec['order'] );
			$product->set_short_description( self::short_description() );
			if ( ! $is_new ) {
				$desc = str_replace( array( '30 g', '30g' ), '20 g', $product->get_description() );
				$product->set_description( $desc ? $desc : self::description() );
			}
			try {
				$product->set_sku( $spec['sku'] );
			} catch ( \Exception $e ) {
				$log[] = "SKU {$spec['sku']} already used — kept existing SKU";
			}
			$product->set_reviews_allowed( false );
			$image_id = self::image_id( $spec['image'], $spec['alt'] );
			if ( $image_id ) {
				$product->set_image_id( $image_id );
				$product->set_gallery_image_ids( array() );
			}
			$product->update_meta_data( '_bq_requires_prescription', 'yes' );
			$product->update_meta_data( '_bq_subtitle', $spec['subtitle'] );
			$product->update_meta_data( '_bq_admin_note', self::admin_note() );
			foreach ( Product_Fields::default_info() as $key => $html ) {
				$current = (string) $product->get_meta( $key );
				$current = str_replace( array( '30 g', '30g' ), '20 g', $current );
				$product->update_meta_data( $key, '' === trim( $current ) || '_bq_info_overview' === $key ? $html : $current );
			}

			// Attribute + variations: relabel in place (keeps variation IDs, orders and stock).
			$attr_key = sanitize_title( $spec['attribute'] );
			$labels   = wp_list_pluck( $spec['options'], 0 );
			$attr     = new \WC_Product_Attribute();
			$attr->set_name( $spec['attribute'] );
			$attr->set_options( $labels );
			$attr->set_visible( true );
			$attr->set_variation( true );
			$product->set_attributes( array( $attr ) );
			$product->set_default_attributes( array( $attr_key => $labels[0] ) );
			$product_id = $product->save();

			$children = $is_new ? array() : $product->get_children();
			foreach ( $spec['options'] as $i => $o ) {
				$v = null;
				// Match an existing variation by position-equivalent label (30 g -> 20 g) or exact label.
				foreach ( $children as $cid ) {
					$c   = wc_get_product( $cid );
					$val = $c ? (string) current( $c->get_attributes() ) : '';
					if ( $val === $o[0] || str_replace( '30 g', '20 g', $val ) === $o[0] ) {
						$v = $c;
						break;
					}
				}
				if ( ! $v && isset( $children[ $i ] ) ) {
					$v = wc_get_product( $children[ $i ] );
				}
				if ( ! $v ) {
					$v = new \WC_Product_Variation();
					$v->set_parent_id( $product_id );
					self::set_price( $v, $o );
				}
				$v->set_attributes( array( $attr_key => $o[0] ) );
				$v->set_status( 'publish' );
				if ( '' === (string) $v->get_regular_price() ) {
					self::set_price( $v, $o );
				}
				if ( ! $v->get_manage_stock() ) {
					$v->set_stock_status( 'instock' );
				}
				try {
					$v->set_sku( $spec['sku'] . '-' . $o[3] );
				} catch ( \Exception $e ) {
					$log[] = "Variation SKU {$spec['sku']}-{$o[3]} already used — kept existing";
				}
				$v->save();
			}
			\WC_Product_Variable::sync( $product_id );
			wc_delete_product_transients( $product_id );
			$log[] = sprintf( '%s #%d: %s%s', $is_new ? 'Created' : 'Updated', $product_id, $spec['name'], $old_slug && $old_slug !== $slug ? " (address /shop/{$old_slug}/ → /shop/{$slug}/)" : '' );
		}
		// Remove the old mock-up images that had "30 g" printed on them (only if no longer used).
		foreach ( array( 'product-30g.webp', 'product-2pack.webp', 'product-3month.webp' ) as $old ) {
			foreach ( get_posts( array( 'post_type' => 'attachment', 'meta_key' => '_bq_demo_source', 'meta_value' => $old, 'numberposts' => -1, 'fields' => 'ids' ) ) as $aid ) { // phpcs:ignore
				$used = get_posts( array( 'post_type' => array( 'product', 'product_variation' ), 'meta_key' => '_thumbnail_id', 'meta_value' => $aid, 'numberposts' => 1, 'fields' => 'ids', 'post_status' => 'any' ) ); // phpcs:ignore
				if ( ! $used ) {
					wp_delete_attachment( $aid, true );
					$log[] = "Deleted old image {$old}";
				}
			}
		}
		return $log;
	}
}
