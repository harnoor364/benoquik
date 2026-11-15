<?php
/**
 * Block style variations the owner can pick in the editor sidebar
 * (select a block -> Styles).
 *
 * @package Benoquik
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		register_block_style( 'core/button', array( 'name' => 'dark', 'label' => __( 'Dark pill', 'benoquik' ) ) );
		register_block_style( 'core/button', array( 'name' => 'ghost', 'label' => __( 'Ghost (on images)', 'benoquik' ) ) );
		register_block_style( 'core/button', array( 'name' => 'outline-dark', 'label' => __( 'Outline', 'benoquik' ) ) );
		register_block_style( 'core/group', array( 'name' => 'card', 'label' => __( 'White card', 'benoquik' ) ) );
		register_block_style( 'core/group', array( 'name' => 'dark-card', 'label' => __( 'Dark blue card', 'benoquik' ) ) );
		// Article boxes (Learn): select a Group block → Styles.
		register_block_style( 'core/group', array( 'name' => 'callout-info', 'label' => __( 'Info box', 'benoquik' ) ) );
		register_block_style( 'core/group', array( 'name' => 'callout-important', 'label' => __( 'Important box', 'benoquik' ) ) );
		register_block_style( 'core/group', array( 'name' => 'callout-warning', 'label' => __( 'Safety warning box', 'benoquik' ) ) );
		register_block_style( 'core/group', array( 'name' => 'key-takeaways', 'label' => __( 'Key takeaways box', 'benoquik' ) ) );
		register_block_style( 'core/paragraph', array( 'name' => 'eyebrow', 'label' => __( 'Small label', 'benoquik' ) ) );
		register_block_style( 'core/paragraph', array( 'name' => 'pill', 'label' => __( 'Pill tag', 'benoquik' ) ) );
	}
);
