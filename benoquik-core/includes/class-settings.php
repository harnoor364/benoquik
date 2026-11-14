<?php
/**
 * WordPress admin -> Benoquik -> Settings.
 * Everything the owner may want to change without touching the editor.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Settings {

	const OPTION = 'benoquik_settings';

	/** Field definitions: key => [label, type, default, help]. */
	public static function fields() {
		return array(
			'store'      => array(
				'title'  => __( 'Contact', 'benoquik-core' ),
				'fields' => array(
					'whatsapp_number'  => array( __( 'WhatsApp number', 'benoquik-core' ), 'text', '919999999999', __( 'With country code, digits only. Example: 919876543210. Used by every button with the CSS class "bq-whatsapp".', 'benoquik-core' ) ),
					'whatsapp_message' => array( __( 'WhatsApp pre-filled message', 'benoquik-core' ), 'text', 'Hello, I have a question about Benoquik.', '' ),
					'support_phone'    => array( __( 'Support phone (display)', 'benoquik-core' ), 'text', '+91 99999 99999', '' ),
					'support_email'    => array( __( 'Support email', 'benoquik-core' ), 'email', 'care@example.com', '' ),
				),
			),
			'messaging'  => array(
				'title'  => __( 'Announcement & marquee', 'benoquik-core' ),
				'fields' => array(
					'announcement_text' => array( __( 'Announcement bar text', 'benoquik-core' ), 'text', '℞ Prescription required · Free delivery above ₹999 · Pan-India shipping', __( 'Shown above the header on every page. Leave empty to hide.', 'benoquik-core' ) ),
					'announcement_link' => array( __( 'Announcement bar link (optional)', 'benoquik-core' ), 'url', '', '' ),
					'marquee_items'     => array( __( 'Marquee items (one per line)', 'benoquik-core' ), 'textarea', "Free delivery above ₹999\nPrescription required\nGenuine product\nPan-India shipping", __( 'The lime scrolling strip on the homepage.', 'benoquik-core' ) ),
				),
			),
			'compliance' => array(
				'title'  => __( 'Licences & compliance (shown in the footer)', 'benoquik-core' ),
				'fields' => array(
					'legal_name'      => array( __( 'Registered business name', 'benoquik-core' ), 'text', 'Benoquik Pharmacy Pvt. Ltd. (placeholder)', '' ),
					'drug_licence'    => array( __( 'Drug licence no.', 'benoquik-core' ), 'text', 'DL-XX-000000 (placeholder)', '' ),
					'gst_number'      => array( __( 'GSTIN', 'benoquik-core' ), 'text', '00AAAAA0000A0Z0 (placeholder)', '' ),
					'pharmacist_name' => array( __( 'Registered pharmacist', 'benoquik-core' ), 'text', 'Pharmacist Name (placeholder)', '' ),
					'pharmacist_reg'  => array( __( 'Pharmacist registration no.', 'benoquik-core' ), 'text', 'Reg. No. 00000 (placeholder)', '' ),
				),
			),
		);
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/** Get one setting (falls back to the default). */
	public static function get( $key ) {
		$opts = get_option( self::OPTION, array() );
		if ( isset( $opts[ $key ] ) ) {
			return $opts[ $key ];
		}
		foreach ( self::fields() as $section ) {
			if ( isset( $section['fields'][ $key ] ) ) {
				return $section['fields'][ $key ][2];
			}
		}
		return '';
	}

	public static function menu() {
		add_menu_page( __( 'Benoquik', 'benoquik-core' ), __( 'Benoquik', 'benoquik-core' ), 'manage_options', 'benoquik', array( __CLASS__, 'render' ), 'dashicons-heart', 56 );
		add_submenu_page( 'benoquik', __( 'Settings', 'benoquik-core' ), __( 'Settings', 'benoquik-core' ), 'manage_options', 'benoquik', array( __CLASS__, 'render' ) );
	}

	public static function register() {
		register_setting(
			'benoquik',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
		foreach ( self::fields() as $section_id => $section ) {
			add_settings_section( 'bq_' . $section_id, $section['title'], '__return_false', 'benoquik' );
			foreach ( $section['fields'] as $key => $f ) {
				add_settings_field(
					$key,
					esc_html( $f[0] ),
					array( __CLASS__, 'field' ),
					'benoquik',
					'bq_' . $section_id,
					array(
						'key'       => $key,
						'type'      => $f[1],
						'help'      => $f[3],
						'label_for' => 'bq-' . $key,
					)
				);
			}
		}
	}

	public static function sanitize( $input ) {
		$clean = array();
		foreach ( self::fields() as $section ) {
			foreach ( $section['fields'] as $key => $f ) {
				$v = $input[ $key ] ?? '';
				switch ( $f[1] ) {
					case 'textarea':
						$clean[ $key ] = sanitize_textarea_field( $v );
						break;
					case 'url':
						$clean[ $key ] = esc_url_raw( $v );
						break;
					case 'email':
						$clean[ $key ] = sanitize_email( $v );
						break;
					default:
						$clean[ $key ] = sanitize_text_field( $v );
				}
			}
		}
		$clean['whatsapp_number'] = preg_replace( '/\D+/', '', $clean['whatsapp_number'] );
		return $clean;
	}

	public static function field( $args ) {
		$name  = self::OPTION . '[' . $args['key'] . ']';
		$value = self::get( $args['key'] );
		$id    = 'bq-' . $args['key'];
		if ( 'textarea' === $args['type'] ) {
			printf( '<textarea id="%s" name="%s" rows="5" class="large-text">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
		} else {
			printf( '<input id="%s" type="%s" name="%s" value="%s" class="regular-text">', esc_attr( $id ), esc_attr( 'email' === $args['type'] ? 'email' : ( 'url' === $args['type'] ? 'url' : 'text' ) ), esc_attr( $name ), esc_attr( $value ) );
		}
		if ( $args['help'] ) {
			printf( '<p class="description">%s</p>', esc_html( $args['help'] ) );
		}
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Benoquik settings', 'benoquik-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Changes here update the whole site instantly (header announcement, homepage marquee, WhatsApp buttons and footer licence details).', 'benoquik-core' ) . '</p>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'benoquik' );
		do_settings_sections( 'benoquik' );
		submit_button();
		echo '</form></div>';
	}
}
