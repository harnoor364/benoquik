<?php
/**
 * WP-CLI commands:  wp benoquik <doctor|fix-encoding|repair-products|rebuild-home|flush|upgrade>
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class CLI {

	private static function out( $lines ) {
		foreach ( (array) $lines as $l ) {
			\WP_CLI::log( $l );
		}
	}

	/**
	 * Report encoding and product problems. Changes nothing.
	 */
	public function doctor() {
		self::out( Repair::doctor() );
	}

	/**
	 * Repair double-encoded characters (â€” → —) in every table, serialization-safe.
	 *
	 * [--dry-run]
	 * : Only report what would change.
	 *
	 * @subcommand fix-encoding
	 */
	public function fix_encoding( $args, $assoc ) {
		self::out( Repair::fix_encoding( ! empty( $assoc['dry-run'] ) ) );
		self::out( Repair::doctor() );
	}

	/**
	 * Re-link variations to pack sizes, check prices/stock, split pack size into
	 * the subtitle, fill empty medicine-information boxes, turn reviews off.
	 *
	 * @subcommand repair-products
	 */
	public function repair_products() {
		self::out( Repair::repair_products() );
	}

	/**
	 * Update the three products to the real Benoquik range (20 g packs,
	 * names, subtitles, SKUs, product photos). Keeps IDs, prices and orders.
	 *
	 * @subcommand migrate-v030
	 */
	public function migrate_v030() {
		self::out( Repair::migrate_v030() );
	}

	/**
	 * Rebuild the Home page from the theme's homepage sections.
	 *
	 * @subcommand rebuild-home
	 */
	public function rebuild_home() {
		self::out( Repair::rebuild_home() );
	}

	/**
	 * Flush transients, caches and WooCommerce lookup tables.
	 */
	public function flush() {
		self::out( Repair::flush() );
	}

	/**
	 * Run everything: fix-encoding, repair-products, rebuild-home, flush, doctor.
	 *
	 * [--keep-home]
	 * : Don't rebuild the Home page.
	 */
	public function upgrade( $args, $assoc ) {
		self::out( Repair::upgrade( empty( $assoc['keep-home'] ) ) );
		\WP_CLI::success( 'Benoquik repair complete.' );
	}
}
