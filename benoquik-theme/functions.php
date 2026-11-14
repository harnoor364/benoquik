<?php
/**
 * Benoquik theme bootstrap.
 *
 * Design-only: business logic (prescriptions, settings, subscribers, order
 * statuses) lives in the "Benoquik Core" plugin so the store keeps working if
 * the theme is ever changed.
 *
 * SEO NOTE: this theme deliberately outputs NO meta descriptions, Open Graph
 * tags or JSON-LD schema. Rank Math SEO controls all of that.
 *
 * @package Benoquik
 */

defined( 'ABSPATH' ) || exit;

define( 'BENOQUIK_THEME_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );

require_once get_theme_file_path( 'inc/setup.php' );
require_once get_theme_file_path( 'inc/block-styles.php' );
require_once get_theme_file_path( 'inc/performance.php' );
