<?php
/**
 * Small dynamic blocks whose text comes from Benoquik -> Settings.
 * They appear in the editor under "Benoquik" in the block inserter.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Blocks {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'block_categories_all', array( __CLASS__, 'category' ) );
	}

	public static function category( $cats ) {
		array_unshift(
			$cats,
			array(
				'slug'  => 'benoquik',
				'title' => __( 'Benoquik', 'benoquik-core' ),
			)
		);
		return $cats;
	}

	public static function register() {
		wp_register_script(
			'benoquik-blocks-editor',
			BENOQUIK_CORE_URL . 'assets/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			BENOQUIK_CORE_VERSION,
			true
		);
		wp_register_style( 'benoquik-blocks', BENOQUIK_CORE_URL . 'assets/blocks.css', array(), BENOQUIK_CORE_VERSION );
		wp_register_script(
			'benoquik-marquee',
			BENOQUIK_CORE_URL . 'assets/marquee.js',
			array(),
			BENOQUIK_CORE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$common = array(
			'editor_script_handles' => array( 'benoquik-blocks-editor' ),
			'style_handles'         => array( 'benoquik-blocks' ),
		);

		register_block_type(
			'benoquik/marquee',
			$common + array(
				'render_callback'      => array( __CLASS__, 'marquee' ),
				'view_script_handles'  => array( 'benoquik-marquee' ),
				'attributes'           => array( 'align' => array( 'type' => 'string', 'default' => 'full' ) ),
				'supports'             => array( 'align' => array( 'full', 'wide' ) ),
			)
		);
		register_block_type( 'benoquik/announcement', $common + array( 'render_callback' => array( __CLASS__, 'announcement' ) ) );
		register_block_type( 'benoquik/compliance', $common + array( 'render_callback' => array( __CLASS__, 'compliance' ) ) );
		register_block_type( 'benoquik/subscribe', $common + array( 'render_callback' => array( Subscribers::class, 'render_form' ) ) );
	}

	/** Blue scrolling strip. Text: Settings -> "Marquee items". */
	public static function marquee( $attrs ) {
		$items = array_filter( array_map( 'trim', explode( "\n", (string) Settings::get( 'marquee_items' ) ) ) );
		if ( ! $items ) {
			return '';
		}
		$group = '<ul class="bq-marquee__group">';
		foreach ( $items as $item ) {
			$group .= '<li>' . esc_html( $item ) . '</li><li class="bq-marquee__sep">✺</li>';
		}
		$group .= '</ul>';
		// Repeat so the strip is always wider than the screen, then duplicate for a seamless loop.
		$half  = str_repeat( $group, max( 2, (int) ceil( 8 / count( $items ) ) ) );
		$align = ! empty( $attrs['align'] ) ? ' align' . sanitize_html_class( $attrs['align'] ) : '';

		return sprintf(
			'<div class="wp-block-benoquik-marquee bq-marquee%1$s"><p class="screen-reader-text">%2$s</p><div class="bq-marquee__track" aria-hidden="true">%3$s%3$s</div><button type="button" class="bq-marquee__toggle" aria-pressed="false" aria-label="%4$s"><span aria-hidden="true">❚❚</span></button></div>',
			esc_attr( $align ),
			esc_html( implode( ' · ', $items ) ),
			$half, // Already escaped above.
			esc_attr__( 'Pause scrolling text', 'benoquik-core' )
		);
	}

	/** Thin bar above the header. Text: Settings -> "Announcement bar text". */
	public static function announcement() {
		$text = Settings::get( 'announcement_text' );
		if ( '' === trim( (string) $text ) ) {
			return '';
		}
		$link = Settings::get( 'announcement_link' );
		$text = esc_html( $text );
		if ( $link ) {
			$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
		}
		return '<div class="wp-block-benoquik-announcement bq-announcement"><p>' . $text . '</p></div>';
	}

	/** Footer bottom row: licence, GST, pharmacist, privacy link, © year. */
	public static function compliance() {
		$parts = array();
		$map   = array(
			'drug_licence' => __( 'Drug licence no.', 'benoquik-core' ),
			'gst_number'   => __( 'GSTIN', 'benoquik-core' ),
		);
		foreach ( $map as $key => $label ) {
			$v = Settings::get( $key );
			if ( $v ) {
				$parts[] = '<span>' . esc_html( $label ) . ' ' . esc_html( $v ) . '</span>';
			}
		}
		$pharm = trim( Settings::get( 'pharmacist_name' ) . ( Settings::get( 'pharmacist_reg' ) ? ' · ' . Settings::get( 'pharmacist_reg' ) : '' ) );
		if ( $pharm ) {
			$parts[] = '<span>' . esc_html__( 'Registered pharmacist:', 'benoquik-core' ) . ' ' . esc_html( $pharm ) . '</span>';
		}
		if ( Settings::get( 'marketed_by' ) ) {
			$parts[] = '<span>' . esc_html__( 'Benoquik® is marketed by', 'benoquik-core' ) . ' ' . esc_html( Settings::get( 'marketed_by' ) ) . '</span>';
		}
		$privacy = get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' );
		$parts[] = '<a href="' . esc_url( $privacy ) . '">' . esc_html__( 'Privacy policy', 'benoquik-core' ) . '</a>';
		$parts[] = '<span>© ' . esc_html( wp_date( 'Y' ) ) . ' ' . esc_html( Settings::get( 'legal_name' ) ) . '. ' . esc_html__( 'All rights reserved.', 'benoquik-core' ) . '</span>';

		return '<div class="wp-block-benoquik-compliance bq-compliance">' . implode( '', $parts ) . '</div>';
	}
}
