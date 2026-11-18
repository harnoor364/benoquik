<?php
/**
 * Plugin Name: Benoquik Core
 * Description: Business logic for the international Benoquik store — store settings (WhatsApp, marquee, announcement, licenses, shipping countries), dynamic blocks, newsletter subscribers and (coming next) prescription upload & review. Kept separate from the theme so switching themes never breaks the store.
 * Version: 0.5.3
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Author: Benoquik
 * License: GPL-2.0-or-later
 * Text Domain: benoquik-core
 *
 * @package BenoquikCore
 */

defined( 'ABSPATH' ) || exit;

define( 'BENOQUIK_CORE_VERSION', '0.5.3' );
define( 'BENOQUIK_CORE_FILE', __FILE__ );
define( 'BENOQUIK_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'BENOQUIK_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once BENOQUIK_CORE_DIR . 'includes/class-settings.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-blocks.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-subscribers.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-frontend.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-demo-import.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-encoding.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-catalogue.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-product-fields.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-product-blocks.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-repair.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-checkout-gate.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-international.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-blog.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-blog-setup.php';
require_once BENOQUIK_CORE_DIR . 'includes/class-faq-schema.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once BENOQUIK_CORE_DIR . 'includes/class-cli.php';
	WP_CLI::add_command( 'benoquik', 'Benoquik\\CLI' );
}

add_action(
	'plugins_loaded',
	static function () {
		Benoquik\Settings::init();
		Benoquik\Blocks::init();
		Benoquik\Subscribers::init();
		Benoquik\Frontend::init();
		Benoquik\Demo_Import::init();
		Benoquik\Product_Fields::init();
		Benoquik\Product_Blocks::init();
		Benoquik\Repair::init();
		Benoquik\Checkout_Gate::init();
		Benoquik\International::init();
		Benoquik\Blog::init();
		Benoquik\Blog_Setup::init();
		Benoquik\FAQ_Schema::init();
	}
);
