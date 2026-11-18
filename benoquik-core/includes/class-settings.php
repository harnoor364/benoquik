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
				'title'  => __( 'Contact & support', 'benoquik-core' ),
				'fields' => array(
					'whatsapp_number'  => array( __( 'WhatsApp number', 'benoquik-core' ), 'text', '18314802325', __( 'International format, digits only, starting with the country code. Example for India: 919876543210. Used by every button with the CSS class "bq-whatsapp".', 'benoquik-core' ) ),
					'whatsapp_message' => array( __( 'WhatsApp pre-filled message', 'benoquik-core' ), 'text', 'Hello, I have a question about Benoquik.', '' ),
					'support_phone'    => array( __( 'Support phone (display, with country code)', 'benoquik-core' ), 'text', '+1-8314802325', '' ),
					'support_email'    => array( __( 'Support email', 'benoquik-core' ), 'email', 'Support@benoquik.us', '' ),
					'support_hours'    => array( __( 'Support hours (with time zone)', 'benoquik-core' ), 'text', 'Mon–Sat, 09:00–18:00 IST (UTC+5:30) · We reply within 1 business day', __( 'Shown on the Contact page and in emails.', 'benoquik-core' ) ),
				),
			),
			'messaging'  => array(
				'title'  => __( 'Announcement & marquee', 'benoquik-core' ),
				'fields' => array(
					'announcement_text' => array( __( 'Announcement bar text', 'benoquik-core' ), 'text', 'Worldwide shipping · Free shipping over $99 · Genuine product', __( 'Shown above the header on every page. Leave empty to hide.', 'benoquik-core' ) ),
					'announcement_link' => array( __( 'Announcement bar link (optional)', 'benoquik-core' ), 'url', '', '' ),
					'marquee_items'     => array( __( 'Marquee items (one per line)', 'benoquik-core' ), 'textarea', "Worldwide shipping\nFree shipping over $99\nGenuine product\nDiscreet packaging\nTracked delivery", __( 'The blue scrolling strip on the homepage.', 'benoquik-core' ) ),
				),
			),
			'product'    => array(
				'title'  => __( 'Product page, cart & checkout', 'benoquik-core' ),
				'fields' => array(
					'rx_notice_text'      => array( __( 'Prescription notice', 'benoquik-core' ), 'text', '℞ Prescription required — upload a valid prescription from a licensed doctor in your country at checkout. Our pharmacist verifies every order.', __( 'Shown on products with "Requires prescription" ticked.', 'benoquik-core' ) ),
					'price_note'          => array( __( 'Note under the price', 'benoquik-core' ), 'text', 'Prices in USD, excluding taxes, import duties and customs fees.', '' ),
					'shipping_note'       => array( __( 'Shipping line on product pages', 'benoquik-core' ), 'text', 'Ships from India · Tracked international delivery · Discreet packaging', '' ),
					'delivery_default'    => array( __( 'Default delivery estimate', 'benoquik-core' ), 'text', '10–20 business days after prescription approval', __( 'Used for any country without its own rule below.', 'benoquik-core' ) ),
					'delivery_rules'      => array( __( 'Delivery estimate by country', 'benoquik-core' ), 'textarea', "US|7–12 business days\nCA|7–12 business days\nGB|7–10 business days\nIE|7–10 business days\nDE|7–12 business days\nFR|7–12 business days\nAE|5–8 business days\nSA|6–10 business days\nAU|8–14 business days\nNZ|8–14 business days", __( 'One rule per line: two-letter country code|delivery text. Example: US|7–12 business days.', 'benoquik-core' ) ),
					'duties_notice'       => array( __( 'Import duties notice (cart & checkout)', 'benoquik-core' ), 'textarea', 'Prices exclude any import duties, VAT or customs fees charged by your country. These are paid by the recipient on delivery.', '' ),
					'import_confirmation' => array( __( 'Import confirmation checkbox (checkout)', 'benoquik-core' ), 'text', 'I confirm this medicine may be legally imported into my country for personal use with a valid prescription.', __( 'Required at checkout and saved on the order.', 'benoquik-core' ) ),
				),
			),
			'blog'       => array(
				'title'  => __( 'Learn (blog)', 'benoquik-core' ),
				'fields' => array(
					'blog_disclaimer' => array( __( 'Medical disclaimer at the end of every article', 'benoquik-core' ), 'textarea', 'This article is for general information only and is not medical advice. Monobenzone is a prescription medicine — always follow your doctor’s advice.', '' ),
				),
			),
			'shipping'   => array(
				'title'  => __( 'Shipping countries', 'benoquik-core' ),
				'fields' => array(
					'restricted_countries' => array( __( 'Countries we do not ship to', 'benoquik-core' ), 'text', 'IN', __( 'Two-letter country codes separated by commas, e.g. IN, US. These countries cannot be chosen at checkout. Review this list with your legal advisor.', 'benoquik-core' ) ),
				),
			),
			'compliance' => array(
				'title'  => __( 'Company & legal details (Indian legal entity, shown in the footer)', 'benoquik-core' ),
				'fields' => array(
					'legal_name'       => array( __( 'Registered business name (your pharmacy)', 'benoquik-core' ), 'text', 'Benoquik Pharmacy Pvt. Ltd. (placeholder)', '' ),
					'legal_address'    => array( __( 'Registered address (India)', 'benoquik-core' ), 'text', 'Registered address, India (placeholder)', __( 'Shown only in the footer legal block and on legal pages.', 'benoquik-core' ) ),
					'marketed_by'      => array( __( 'Product marketed by', 'benoquik-core' ), 'text', 'Quik Pharmaceuticals', __( 'Shown on product pages and in the footer as "Marketed by …".', 'benoquik-core' ) ),
					'marketer_address' => array( __( 'Marketer address (India)', 'benoquik-core' ), 'text', 'Address (placeholder)', __( 'From the pack. Shown in the footer legal block.', 'benoquik-core' ) ),
					'marketer_licence' => array( __( 'Manufacturer / marketer license no.', 'benoquik-core' ), 'text', 'Mfg. Lic. No. (placeholder)', __( 'From the pack.', 'benoquik-core' ) ),
					'drug_licence'     => array( __( 'Drug license no. (India)', 'benoquik-core' ), 'text', 'DL-XX-000000 (placeholder)', '' ),
					'gst_number'       => array( __( 'GSTIN (Indian tax registration)', 'benoquik-core' ), 'text', '00AAAAA0000A0Z0 (placeholder)', '' ),
					'pharmacist_name'  => array( __( 'Registered pharmacist', 'benoquik-core' ), 'text', 'Pharmacist Name (placeholder)', '' ),
					'pharmacist_reg'   => array( __( 'Pharmacist registration no.', 'benoquik-core' ), 'text', 'Reg. No. 00000 (placeholder)', '' ),
				),
			),
		);
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'migrate_contact_details' ) );
	}

	/**
	 * One-time update: writes the current support phone, WhatsApp number and email
	 * into the saved settings, so sites that already saved older values pick them up.
	 * Runs once; after that, edit them in Benoquik -> Settings as usual.
	 */
	public static function migrate_contact_details() {
		$flag = 'benoquik_contact_migrated_2026_11';
		if ( get_option( $flag ) ) {
			return;
		}
		$opts                    = get_option( self::OPTION, array() );
		$opts                    = is_array( $opts ) ? $opts : array();
		$opts['whatsapp_number'] = '18314802325';
		$opts['support_phone']   = '+1-8314802325';
		$opts['support_email']   = 'Support@benoquik.us';
		update_option( self::OPTION, $opts );
		update_option( $flag, 1, false );
	}

	/** Parse "IN, us ,GB" into ['IN','US','GB'] (valid WooCommerce country codes only). */
	public static function country_list( $value ) {
		$codes = array_filter( array_map( 'strtoupper', array_map( 'trim', preg_split( '/[\s,;]+/', (string) $value ) ) ) );
		if ( function_exists( 'WC' ) && WC()->countries ) {
			$all   = WC()->countries->get_countries();
			$codes = array_filter( $codes, static fn( $c ) => isset( $all[ $c ] ) );
		}
		return array_values( array_unique( $codes ) );
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
		$clean['whatsapp_number']      = preg_replace( '/\D+/', '', $clean['whatsapp_number'] );
		$clean['restricted_countries'] = implode( ', ', self::country_list( $clean['restricted_countries'] ) );
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
		echo '<p>' . esc_html__( 'Changes here update the whole site instantly (announcement bar, homepage marquee, WhatsApp buttons, delivery estimates, checkout notices, shipping countries and the footer legal details).', 'benoquik-core' ) . '</p>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'benoquik' );
		do_settings_sections( 'benoquik' );
		submit_button();
		echo '</form></div>';
	}
}
