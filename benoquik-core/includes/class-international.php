<?php
/**
 * International store (customers outside India; company and pharmacy in India).
 *
 * Runtime:
 *  - selling/shipping to all countries except Benoquik → Settings →
 *    "Countries we do not ship to" (starts with India);
 *  - import-duties notice in cart & checkout;
 *  - international phone field (required, with country code);
 *  - shipping details added to WooCommerce's Product structured data;
 *  - admin notice when Razorpay must have International Payments enabled;
 *  - shortcodes used by the international pages.
 *
 * One-time migration: `wp benoquik migrate-intl` (or Benoquik → Repair store data).
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class International {

	/** Shipping zones: name => [ locations, flat rate (USD), free-shipping minimum ]. PLACEHOLDER RATES. */
	public static function zones() {
		return array(
			'United States & Canada'    => array( array( 'country:US', 'country:CA' ), 15, 99 ),
			'United Kingdom & Europe'   => array( array( 'continent:EU' ), 15, 99 ),
			'Middle East (GCC)'         => array( array( 'country:AE', 'country:SA', 'country:QA', 'country:KW', 'country:BH', 'country:OM' ), 12, 99 ),
			'Australia & New Zealand'   => array( array( 'country:AU', 'country:NZ' ), 18, 99 ),
		);
	}

	const REST_OF_WORLD_RATE = 20;

	public static function init() {
		// Countries: all except the restricted list (India first). Shipping follows selling locations.
		add_filter( 'pre_option_woocommerce_allowed_countries', static fn() => 'all_except' );
		add_filter( 'pre_option_woocommerce_all_except_countries', static fn() => Settings::country_list( Settings::get( 'restricted_countries' ) ) );
		add_filter( 'pre_option_woocommerce_ship_to_countries', static fn() => '' );

		// International phone number: required, with country code.
		add_filter( 'pre_option_woocommerce_checkout_phone_field', static fn() => 'required' );
		add_filter( 'woocommerce_get_country_locale_default', array( __CLASS__, 'phone_locale' ) );
		add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'classic_phone_field' ) );

		// Cart & checkout: duties notice.
		add_filter( 'render_block_woocommerce/cart', array( __CLASS__, 'duties_notice' ) );
		add_filter( 'render_block_woocommerce/checkout', array( __CLASS__, 'duties_notice' ) );

		// Structured data (WooCommerce's own Product markup — not a second copy).
		add_filter( 'woocommerce_structured_data_product', array( __CLASS__, 'shipping_details' ), 20, 2 );

		add_action( 'admin_notices', array( __CLASS__, 'razorpay_currency_notice' ) );
		add_action( 'admin_init', array( __CLASS__, 'dismiss_notice' ) );

		add_shortcode( 'benoquik_restricted_countries', array( __CLASS__, 'sc_restricted' ) );
		add_shortcode( 'benoquik_delivery_table', array( __CLASS__, 'sc_delivery_table' ) );
		add_shortcode( 'benoquik_shipping_rates', array( __CLASS__, 'sc_shipping_rates' ) );
		add_shortcode( 'benoquik_contact', array( __CLASS__, 'sc_contact' ) );
		add_shortcode( 'benoquik_legal_details', array( __CLASS__, 'sc_legal' ) );
	}

	/* ------------------------------------------------------------- runtime */

	public static function phone_locale( $fields ) {
		$fields['phone'] = array_merge(
			isset( $fields['phone'] ) ? (array) $fields['phone'] : array(),
			array(
				'label'    => __( 'Phone (with country code)', 'benoquik-core' ),
				'required' => true,
			)
		);
		return $fields;
	}

	public static function classic_phone_field( $fields ) {
		if ( isset( $fields['billing_phone'] ) ) {
			$fields['billing_phone']['label']       = __( 'Phone (with country code)', 'benoquik-core' );
			$fields['billing_phone']['placeholder'] = '+1 555 000 0000';
			$fields['billing_phone']['required']    = true;
			$fields['billing_phone']['type']        = 'tel';
		}
		return $fields;
	}

	public static function duties_notice( $html ) {
		$text = trim( (string) Settings::get( 'duties_notice' ) );
		if ( '' === $text ) {
			return $html;
		}
		$box = '<div class="bq-duties-notice" role="note"><svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v.01M11 12h1v5h1"/></svg><p>' . esc_html( $text ) . '</p></div>';
		return $box . $html;
	}

	/** Delivery rules parsed from Settings: [ 'US' => '7–12 business days', … ]. */
	public static function delivery_rules() {
		$rules = array();
		foreach ( explode( "\n", (string) Settings::get( 'delivery_rules' ) ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( 2 === count( $parts ) && preg_match( '/^[A-Za-z]{2}$/', $parts[0] ) && '' !== $parts[1] ) {
				$rules[ strtoupper( $parts[0] ) ] = $parts[1];
			}
		}
		return $rules;
	}

	/** "7–12 business days" → [7, 12]. */
	private static function day_range( $text ) {
		if ( preg_match( '/(\d+)\s*[–-]\s*(\d+)/u', (string) $text, $m ) ) {
			return array( (int) $m[1], (int) $m[2] );
		}
		return null;
	}

	/** Countries covered by each of our zones (continents expanded). */
	private static function zone_countries( \WC_Shipping_Zone $zone ) {
		$codes      = array();
		$continents = WC()->countries->get_continents();
		foreach ( $zone->get_zone_locations() as $loc ) {
			if ( 'country' === $loc->type ) {
				$codes[] = $loc->code;
			} elseif ( 'continent' === $loc->type && isset( $continents[ $loc->code ]['countries'] ) ) {
				$codes = array_merge( $codes, $continents[ $loc->code ]['countries'] );
			}
		}
		return array_values( array_diff( array_unique( $codes ), Settings::country_list( Settings::get( 'restricted_countries' ) ) ) );
	}

	/** First enabled flat rate in a zone (cost as number), and its free-shipping minimum. */
	private static function zone_rates( \WC_Shipping_Zone $zone ) {
		$flat = null;
		$free = null;
		foreach ( $zone->get_shipping_methods( true ) as $m ) {
			if ( 'flat_rate' === $m->id && null === $flat ) {
				$flat = (float) $m->get_option( 'cost' );
			}
			if ( 'free_shipping' === $m->id && null === $free ) {
				$free = (float) $m->get_option( 'min_amount' );
			}
		}
		return array( $flat, $free );
	}

	/** Adds OfferShippingDetails per shipping zone to WooCommerce's Product markup. */
	public static function shipping_details( $markup, $product ) {
		if ( empty( $markup['offers'] ) || ! class_exists( 'WC_Shipping_Zones' ) ) {
			return $markup;
		}
		$rules   = self::delivery_rules();
		$default = self::day_range( Settings::get( 'delivery_default' ) );
		$details = array();
		foreach ( \WC_Shipping_Zones::get_zones() as $z ) {
			$zone      = new \WC_Shipping_Zone( $z['id'] );
			$countries = self::zone_countries( $zone );
			list( $flat ) = self::zone_rates( $zone );
			if ( ! $countries || null === $flat ) {
				continue;
			}
			$range = self::day_range( $rules[ $countries[0] ] ?? '' );
			$range = $range ? $range : $default;
			$item  = array(
				'@type'               => 'OfferShippingDetails',
				'shippingRate'        => array(
					'@type'    => 'MonetaryAmount',
					'value'    => wc_format_decimal( $flat, wc_get_price_decimals() ),
					'currency' => get_woocommerce_currency(),
				),
				'shippingDestination' => array_map(
					static fn( $c ) => array(
						'@type'          => 'DefinedRegion',
						'addressCountry' => $c,
					),
					$countries
				),
				'shippingOrigin'      => array(
					'@type'          => 'DefinedRegion',
					'addressCountry' => 'IN',
				),
			);
			if ( $range ) {
				$item['deliveryTime'] = array(
					'@type'        => 'ShippingDeliveryTime',
					'handlingTime' => array( '@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 3, 'unitCode' => 'DAY' ),
					'transitTime'  => array( '@type' => 'QuantitativeValue', 'minValue' => $range[0], 'maxValue' => $range[1], 'unitCode' => 'DAY' ),
				);
			}
			$details[] = $item;
		}
		if ( ! $details ) {
			return $markup;
		}
		if ( isset( $markup['offers'][0] ) ) {
			foreach ( $markup['offers'] as $i => $offer ) {
				$markup['offers'][ $i ]['shippingDetails'] = $details;
			}
		} else {
			$markup['offers']['shippingDetails'] = $details;
		}
		return $markup;
	}

	public static function razorpay_currency_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'WC' ) || get_user_meta( get_current_user_id(), 'bq_dismiss_rzp_intl', true ) ) {
			return;
		}
		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
		if ( empty( $gateways['razorpay'] ) || 'yes' !== $gateways['razorpay']->enabled || 'INR' === get_woocommerce_currency() ) {
			return;
		}
		$url = wp_nonce_url( add_query_arg( 'bq_dismiss_rzp_intl', '1' ), 'bq_dismiss_rzp_intl' );
		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s</p><p><a href="%s">%s</a></p></div>',
			esc_html__( 'Razorpay and USD:', 'benoquik-core' ),
			esc_html(
				sprintf(
					/* translators: %s: currency code */
					__( 'Your store currency is %s. Razorpay can only accept non-INR payments if "International Payments" is activated on your Razorpay account (Razorpay Dashboard → Account & Settings → International Payments). Until it is approved, customers will see a payment error.', 'benoquik-core' ),
					get_woocommerce_currency()
				)
			),
			esc_url( $url ),
			esc_html__( 'I have confirmed International Payments are active — hide this notice', 'benoquik-core' )
		);
	}

	public static function dismiss_notice() {
		if ( isset( $_GET['bq_dismiss_rzp_intl'] ) && check_admin_referer( 'bq_dismiss_rzp_intl' ) ) {
			update_user_meta( get_current_user_id(), 'bq_dismiss_rzp_intl', 1 );
		}
	}

	/* ------------------------------------------------------------ shortcodes */

	public static function sc_restricted() {
		$codes = Settings::country_list( Settings::get( 'restricted_countries' ) );
		$all   = WC()->countries->get_countries();
		$names = array_map( static fn( $c ) => $all[ $c ] ?? $c, $codes );
		return $names ? '<ul class="bq-country-list"><li>' . implode( '</li><li>', array_map( 'esc_html', $names ) ) . '</li></ul>' : '<p>' . esc_html__( 'We currently ship to every country available at checkout.', 'benoquik-core' ) . '</p>';
	}

	public static function sc_delivery_table() {
		$all  = WC()->countries->get_countries();
		$rows = '';
		foreach ( self::delivery_rules() as $code => $text ) {
			$rows .= '<tr><td>' . esc_html( $all[ $code ] ?? $code ) . '</td><td>' . esc_html( $text ) . '</td></tr>';
		}
		$rows .= '<tr><td>' . esc_html__( 'All other countries', 'benoquik-core' ) . '</td><td>' . esc_html( Settings::get( 'delivery_default' ) ) . '</td></tr>';
		return '<figure class="wp-block-table bq-table"><table><thead><tr><th>' . esc_html__( 'Country', 'benoquik-core' ) . '</th><th>' . esc_html__( 'Estimated delivery', 'benoquik-core' ) . '</th></tr></thead><tbody>' . $rows . '</tbody></table></figure>';
	}

	public static function sc_shipping_rates() {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return '';
		}
		$rows  = '';
		$zones = array_map( static fn( $z ) => new \WC_Shipping_Zone( $z['id'] ), \WC_Shipping_Zones::get_zones() );
		$zones[] = new \WC_Shipping_Zone( 0 );
		foreach ( $zones as $zone ) {
			list( $flat, $free ) = self::zone_rates( $zone );
			if ( null === $flat ) {
				continue;
			}
			$name  = 0 === $zone->get_id() ? __( 'Rest of the world', 'benoquik-core' ) : $zone->get_zone_name();
			$rows .= '<tr><td>' . esc_html( $name ) . '</td><td>' . wp_kses_post( wc_price( $flat ) ) . '</td><td>' . ( $free ? sprintf( /* translators: %s amount */ esc_html__( 'Free over %s', 'benoquik-core' ), wp_kses_post( wc_price( $free ) ) ) : '—' ) . '</td></tr>';
		}
		return '<figure class="wp-block-table bq-table"><table><thead><tr><th>' . esc_html__( 'Region', 'benoquik-core' ) . '</th><th>' . esc_html__( 'Tracked shipping', 'benoquik-core' ) . '</th><th>' . esc_html__( 'Free shipping', 'benoquik-core' ) . '</th></tr></thead><tbody>' . $rows . '</tbody></table></figure>';
	}

	public static function sc_contact() {
		$wa    = Settings::get( 'whatsapp_number' );
		$items = array(
			__( 'Phone', 'benoquik-core' )         => esc_html( Settings::get( 'support_phone' ) ),
			__( 'Email', 'benoquik-core' )         => '<a href="mailto:' . esc_attr( Settings::get( 'support_email' ) ) . '">' . esc_html( Settings::get( 'support_email' ) ) . '</a>',
			__( 'WhatsApp', 'benoquik-core' )      => $wa ? '<a href="' . esc_url( Frontend::whatsapp_url() ) . '" target="_blank" rel="noopener">+' . esc_html( $wa ) . '</a>' : '—',
			__( 'Support hours', 'benoquik-core' ) => esc_html( Settings::get( 'support_hours' ) ),
		);
		$out = '<dl class="bq-contact">';
		foreach ( $items as $k => $v ) {
			$out .= '<dt>' . esc_html( $k ) . '</dt><dd>' . $v . '</dd>';
		}
		return $out . '</dl>';
	}

	public static function sc_legal() {
		$items = array(
			__( 'Registered business name', 'benoquik-core' )            => Settings::get( 'legal_name' ),
			__( 'Registered address (India)', 'benoquik-core' )          => Settings::get( 'legal_address' ),
			__( 'Drug license no. (India)', 'benoquik-core' )            => Settings::get( 'drug_licence' ),
			__( 'GSTIN (Indian tax registration)', 'benoquik-core' )     => Settings::get( 'gst_number' ),
			__( 'Registered pharmacist', 'benoquik-core' )               => trim( Settings::get( 'pharmacist_name' ) . ' · ' . Settings::get( 'pharmacist_reg' ), ' ·' ),
			__( 'Product marketed by', 'benoquik-core' )                 => trim( Settings::get( 'marketed_by' ) . ', ' . Settings::get( 'marketer_address' ), ', ' ),
			__( 'Manufacturer / marketer license no.', 'benoquik-core' ) => Settings::get( 'marketer_licence' ),
		);
		$out = '<dl class="bq-contact">';
		foreach ( $items as $k => $v ) {
			$out .= '<dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd>';
		}
		return $out . '</dl>';
	}

	/* ------------------------------------------------------------- migration */

	/** British → US spelling for our own content. */
	public static function us_spelling( $text ) {
		static $map = array(
			'colours' => 'colors', 'Colours' => 'Colors', 'colour' => 'color', 'Colour' => 'Color',
			'licences' => 'licenses', 'Licences' => 'Licenses', 'licence' => 'license', 'Licence' => 'License',
			'centre' => 'center', 'Centre' => 'Center', 'organisation' => 'organization', 'favour' => 'favor',
			'behaviour' => 'behavior', 'optimise' => 'optimize', 'personalise' => 'personalize', 'recognise' => 'recognize',
			'minimise' => 'minimize', 'programme' => 'program', 'travelling' => 'traveling', 'cancelled' => 'canceled',
			'working days' => 'business days',
		);
		return is_string( $text ) ? strtr( $text, $map ) : $text;
	}

	/** Old India-specific settings text → new defaults (only if the owner never changed it, or it mentions ₹/India). */
	private static function migrate_settings() {
		$opts = get_option( Settings::OPTION, array() );
		if ( ! is_array( $opts ) || ! $opts ) {
			return array( 'Settings: using the new international defaults.' );
		}
		$defaults = array();
		foreach ( Settings::fields() as $section ) {
			foreach ( $section['fields'] as $key => $f ) {
				$defaults[ $key ] = $f[2];
			}
		}
		$log = array();
		foreach ( array( 'announcement_text', 'marquee_items', 'rx_notice_text', 'delivery_default', 'delivery_rules' ) as $key ) {
			if ( isset( $opts[ $key ] ) && preg_match( '/₹|Pan-India|pincode|PIN code|working days|Mumbai|Delhi|Bengaluru|Chennai|Kolkata|you’ll upload it at checkout/iu', $opts[ $key ] ) ) {
				$opts[ $key ] = $defaults[ $key ];
				$log[]        = "Settings: \"{$key}\" replaced with the international default.";
			}
		}
		if ( isset( $opts['support_phone'] ) && '+91 99999 99999' === $opts['support_phone'] ) {
			$opts['support_phone'] = $defaults['support_phone'];
		}
		update_option( Settings::OPTION, $opts );
		return $log ? $log : array( 'Settings: nothing India-specific found.' );
	}

	private static function setup_zones() {
		$log      = array();
		$existing = array();
		foreach ( \WC_Shipping_Zones::get_zones() as $z ) {
			$existing[ $z['zone_name'] ] = $z['id'];
		}
		$order = 0;
		foreach ( self::zones() as $name => $def ) {
			list( $locations, $flat, $free ) = $def;
			$zone = isset( $existing[ $name ] ) ? new \WC_Shipping_Zone( $existing[ $name ] ) : new \WC_Shipping_Zone();
			$zone->set_zone_name( $name );
			$zone->set_zone_order( $order++ );
			$zone->clear_locations();
			foreach ( $locations as $loc ) {
				list( $type, $code ) = explode( ':', $loc );
				$zone->add_location( $code, $type );
			}
			$zone->save();
			self::ensure_methods( $zone, $flat, $free );
			$log[] = ( isset( $existing[ $name ] ) ? 'Updated' : 'Created' ) . " shipping zone \"{$name}\": flat \${$flat}, free over \${$free}";
		}
		self::ensure_methods( new \WC_Shipping_Zone( 0 ), self::REST_OF_WORLD_RATE, 99 );
		$log[] = 'Rest of world (other countries): flat $' . self::REST_OF_WORLD_RATE . ', free over $99';
		return $log;
	}

	private static function ensure_methods( \WC_Shipping_Zone $zone, $flat, $free ) {
		$have = array();
		foreach ( $zone->get_shipping_methods() as $instance_id => $m ) {
			$have[ $m->id ] = $instance_id;
		}
		if ( ! isset( $have['flat_rate'] ) ) {
			$have['flat_rate'] = $zone->add_shipping_method( 'flat_rate' );
		}
		if ( ! isset( $have['free_shipping'] ) ) {
			$have['free_shipping'] = $zone->add_shipping_method( 'free_shipping' );
		}
		update_option(
			'woocommerce_flat_rate_' . $have['flat_rate'] . '_settings',
			array(
				'title'      => 'Tracked international shipping',
				'tax_status' => 'none',
				'cost'       => (string) $flat,
			)
		);
		update_option(
			'woocommerce_free_shipping_' . $have['free_shipping'] . '_settings',
			array(
				'title'                => 'Free tracked shipping',
				'requires'             => 'min_amount',
				'min_amount'           => (string) $free,
				'ignore_discounts'     => 'no',
			)
		);
	}

	private static function setup_pages() {
		$log   = array();
		$pages = require __DIR__ . '/intl-pages.php';
		foreach ( $pages as $slug => $def ) {
			$post = get_page_by_path( $slug );
			if ( ! $post && ! empty( $def['old_slugs'] ) ) {
				foreach ( $def['old_slugs'] as $old ) {
					$post = get_page_by_path( $old );
					if ( $post ) {
						break;
					}
				}
			}
			if ( 'privacy-policy' === $slug && ! $post ) {
				$pid  = (int) get_option( 'wp_page_for_privacy_policy' );
				$post = $pid ? get_post( $pid ) : null;
			}
			$args = array(
				'post_title'   => $def['title'],
				'post_name'    => $slug,
				'post_content' => $def['content'],
			);
			if ( $post ) {
				$args['ID'] = $post->ID;
				wp_update_post( wp_slash( $args ) ); // Keeps the old address as a redirect when the slug changes.
				$log[] = "Page updated: {$def['title']} (/{$slug}/)" . ( 'publish' !== $post->post_status ? ' — still ' . $post->post_status . ', publish when reviewed' : '' );
			} else {
				$args['post_type']   = 'page';
				$args['post_status'] = 'publish';
				wp_insert_post( wp_slash( $args ) );
				$log[] = "Page created: {$def['title']} (/{$slug}/)";
			}
		}
		return $log;
	}

	private static function setup_emails() {
		$hours = Settings::get( 'support_hours' );
		update_option( 'woocommerce_email_footer_text', 'Benoquik® · Shipped worldwide from India · Support: ' . $hours . ' · {site_url}' );
		$extra = array(
			'customer_processing_order' => 'Our pharmacist is checking your prescription. We will email your tracking number as soon as your order ships. Prices exclude any import duties, VAT or customs fees charged by your country; these are paid by the recipient on delivery.',
			'customer_on_hold_order'    => 'Your order is waiting for a prescription check or payment confirmation. If we need anything from you, we will email you. Support: ' . $hours . '.',
			'customer_completed_order'  => 'Your order has shipped with tracking. Delivery times depend on your country and customs. Questions? Reply to this email — support hours: ' . $hours . '.',
			'customer_new_account'      => 'Welcome to Benoquik. You can now upload your prescription and track international orders from My account. Support hours: ' . $hours . '.',
			'customer_reset_password'   => 'If you did not ask to reset your password, you can ignore this email. Support hours: ' . $hours . '.',
		);
		foreach ( $extra as $id => $text ) {
			$opt                       = (array) get_option( "woocommerce_{$id}_settings", array() );
			$opt['additional_content'] = $text;
			update_option( "woocommerce_{$id}_settings", $opt );
		}
		return array( 'Emails: international footer and messages (with support hours and time zone).' );
	}

	private static function fix_text_content() {
		global $wpdb;
		$count = 0;
		// Our products and pages only: US spelling, no "working days".
		$ids = get_posts(
			array(
				'post_type'   => array( 'product', 'page' ),
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			$p   = get_post( $id );
			$new = array(
				'ID'           => $id,
				'post_content' => self::us_spelling( $p->post_content ),
				'post_excerpt' => self::us_spelling( $p->post_excerpt ),
				'post_title'   => self::us_spelling( $p->post_title ),
			);
			if ( $new['post_content'] !== $p->post_content || $new['post_excerpt'] !== $p->post_excerpt || $new['post_title'] !== $p->post_title ) {
				wp_update_post( wp_slash( $new ) );
				++$count;
			}
			foreach ( array_keys( Product_Fields::info_fields() ) as $key ) {
				$v = get_post_meta( $id, $key, true );
				if ( is_string( $v ) && self::us_spelling( $v ) !== $v ) {
					update_post_meta( $id, $key, self::us_spelling( $v ) );
					++$count;
				}
			}
		}
		return array( "Spelling: updated {$count} item(s) to US English." );
	}

	/** Everything, in order. Safe to run more than once. */
	public static function migrate() {
		$log = array();
		$set = static function ( $k, $v ) {
			update_option( $k, $v );
		};
		// Currency & number format.
		$set( 'woocommerce_currency', 'USD' );
		$set( 'woocommerce_currency_pos', 'left' );
		$set( 'woocommerce_price_num_decimals', 2 );
		$set( 'woocommerce_price_decimal_sep', '.' );
		$set( 'woocommerce_price_thousand_sep', ',' );
		// Customers & units.
		$set( 'woocommerce_default_customer_address', 'geolocation' );
		$set( 'woocommerce_weight_unit', 'g' );
		$set( 'woocommerce_dimension_unit', 'cm' );
		$set( 'woocommerce_calc_taxes', 'no' );
		$set( 'woocommerce_specific_allowed_countries', array() );
		// Language, dates (15 Nov 2026, 24h with time zone). Store address & time zone stay in India.
		$set( 'WPLANG', '' );
		$set( 'date_format', 'j M Y' );
		$set( 'time_format', 'H:i T' );
		$set( 'blogdescription', 'Prescription monobenzone 20% cream for extensive vitiligo — shipped worldwide' );
		// No cash on delivery for international orders.
		$cod            = (array) get_option( 'woocommerce_cod_settings', array() );
		$cod['enabled'] = 'no';
		$set( 'woocommerce_cod_settings', $cod );
		$log[] = 'Store: USD ($12.00), geolocate customers, grams/cm, en-US, dates "15 Nov 2026" + 24h time with time zone, Cash on delivery off.';

		$log = array_merge( $log, self::migrate_settings() );
		$log = array_merge( $log, Catalogue::apply_prices_and_shipping() );
		$log = array_merge( $log, self::setup_zones() );
		$log = array_merge( $log, self::setup_pages() );
		$log = array_merge( $log, self::setup_emails() );
		$log = array_merge( $log, self::fix_text_content() );
		return $log;
	}
}
