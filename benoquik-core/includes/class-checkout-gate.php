<?php
/**
 * Checkout gate: an order (and therefore the Razorpay payment popup) is only
 * possible for a logged-in customer who has accepted the Terms & Conditions
 * and Privacy Policy.
 *
 * Flow: cart → /checkout/ → (logged out) /my-account/?redirect_to=/checkout/
 *       → log in, or register (terms checkbox required) → back to /checkout/
 *       → tick "I agree…" → Place order → payment gateway (Razorpay popup).
 *
 * Enforcement is server-side at every step:
 *  - logged-out visits to checkout / order-pay are redirected to log in;
 *  - guest checkout is switched off;
 *  - the Store API (block checkout) and the classic checkout reject orders
 *    from logged-out users or without the terms checkbox;
 *  - the order-pay "receipt" page, where Razorpay opens its popup, refuses to
 *    render for orders without a recorded terms acceptance.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Checkout_Gate {

	const FIELD_ID   = 'benoquik/accept-terms';
	const ORDER_META = '_bq_terms_accepted_at';
	const USER_META  = 'bq_terms_acceptance';

	public static function init() {
		// Store settings this flow depends on (enforced in code so they can't drift).
		add_filter( 'pre_option_woocommerce_enable_guest_checkout', static fn() => 'no' );
		add_filter( 'pre_option_woocommerce_enable_myaccount_registration', static fn() => 'yes' );
		add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', static fn() => 'no' );
		add_filter( 'pre_option_woocommerce_enable_checkout_login_reminder', static fn() => 'no' );
		add_filter( 'woocommerce_terms_and_conditions_page_id', array( __CLASS__, 'terms_page_id' ) );

		// 1. Login required before checkout.
		add_action( 'template_redirect', array( __CLASS__, 'gate_checkout' ), 5 );
		add_action( 'woocommerce_before_customer_login_form', array( __CLASS__, 'login_notice' ) );
		add_action( 'woocommerce_login_form_end', array( __CLASS__, 'redirect_field' ) );
		add_action( 'woocommerce_register_form_end', array( __CLASS__, 'redirect_field' ) );
		add_filter( 'woocommerce_login_redirect', array( __CLASS__, 'after_login' ), 20, 2 );
		add_filter( 'woocommerce_registration_redirect', array( __CLASS__, 'after_registration' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );

		// 2. Terms acceptance at registration.
		add_action( 'woocommerce_register_form', array( __CLASS__, 'registration_checkbox' ) );
		add_filter( 'woocommerce_process_registration_errors', array( __CLASS__, 'validate_registration' ), 10, 4 );
		add_action( 'woocommerce_created_customer', array( __CLASS__, 'save_registration_acceptance' ), 10, 3 );
		add_action( 'show_user_profile', array( __CLASS__, 'profile_record' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'profile_record' ) );

		// 3. Terms acceptance at checkout (block + classic), enforced server-side.
		add_action( 'woocommerce_init', array( __CLASS__, 'register_checkout_field' ) );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'store_api_guard' ), 5, 2 );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'classic_guard' ) );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'classic_record' ), 10, 3 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_order_record' ) );

		// 4. Payment page (where Razorpay opens its popup) only for accepted orders.
		add_action( 'woocommerce_before_pay_action', array( __CLASS__, 'pay_form_record' ) );
	}

	/* ------------------------------------------------------------------ helpers */

	/** Terms page: WooCommerce setting, or the page with the address /terms-and-conditions/. */
	public static function terms_page_id( $id ) {
		if ( $id ) {
			return $id;
		}
		$page = get_page_by_path( 'terms-and-conditions' );
		return $page ? $page->ID : 0;
	}

	/** Versions of both policies = their last-modified dates (GMT). */
	public static function policy_versions() {
		$terms   = function_exists( 'wc_terms_and_conditions_page_id' ) ? (int) wc_terms_and_conditions_page_id() : 0;
		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
		return array(
			'terms_page_id'     => $terms,
			'terms_version'     => $terms ? get_post_modified_time( 'c', true, $terms ) : '',
			'privacy_page_id'   => $privacy,
			'privacy_version'   => $privacy ? get_post_modified_time( 'c', true, $privacy ) : '',
		);
	}

	/** Linked label used at registration (and in error messages). */
	private static function links_html() {
		$terms   = function_exists( 'wc_terms_and_conditions_page_id' ) ? wc_terms_and_conditions_page_id() : 0;
		$t_url   = $terms ? get_permalink( $terms ) : home_url( '/terms-and-conditions/' );
		$p_url   = get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' );
		return sprintf(
			/* translators: 1: terms link, 2: privacy link */
			__( 'I agree to the %1$s and %2$s', 'benoquik-core' ),
			'<a href="' . esc_url( $t_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Terms & Conditions', 'benoquik-core' ) . '</a>',
			'<a href="' . esc_url( $p_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Privacy Policy', 'benoquik-core' ) . '</a>'
		);
	}

	/**
	 * The page to return to after login/registration — internal URLs only.
	 * Accepts a path ("/checkout/") or a full URL on this site.
	 */
	public static function requested_redirect() {
		// phpcs:ignore WordPress.Security.NonceVerification -- read-only; the value is validated below.
		$raw = isset( $_POST['bq_redirect_to'] ) ? wp_unslash( $_POST['bq_redirect_to'] ) : ( isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : '' );
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}
		if ( str_starts_with( $raw, '//' ) || preg_match( '#^[a-z][a-z0-9+.-]*:#i', $raw ) && ! preg_match( '#^https?://#i', $raw ) ) {
			return ''; // protocol-relative or non-http schemes (javascript:, data:…).
		}
		$url = str_starts_with( $raw, '/' ) ? home_url( $raw ) : $raw;
		$url = wp_validate_redirect( esc_url_raw( $url ), '' );
		if ( ! $url || wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return '';
		}
		return $url;
	}

	/* ---------------------------------------------------- 1. login before checkout */

	public static function gate_checkout() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		if ( is_user_logged_in() ) {
			self::gate_receipt();
			return;
		}
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			return; // Thank-you page after payment.
		}
		$request = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/checkout/' );
		$back    = ( $request['path'] ?? '/checkout/' ) . ( ! empty( $request['query'] ) ? '?' . $request['query'] : '' );
		wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $back ), wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}

	/**
	 * Razorpay (like most popup gateways) opens its popup on the order-pay
	 * "receipt" page. Only show that page for orders with a recorded terms
	 * acceptance; otherwise send the customer to the pay-for-order form, where
	 * WooCommerce requires the terms checkbox before payment.
	 */
	private static function gate_receipt() {
		if ( ! is_wc_endpoint_url( 'order-pay' ) || isset( $_GET['pay_for_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
		if ( ! $order || self::has_accepted( $order ) || ! $order->needs_payment() ) {
			return;
		}
		wc_add_notice( __( 'Please agree to the Terms & Conditions and Privacy Policy to pay for this order.', 'benoquik-core' ), 'notice' );
		wp_safe_redirect( $order->get_checkout_payment_url() );
		exit;
	}

	public static function login_notice() {
		if ( is_user_logged_in() || ! self::requested_redirect() ) {
			return;
		}
		echo '<div class="bq-login-required" role="status"><span class="bq-login-required__icon" aria-hidden="true">℞</span><p>' . esc_html__( 'Please log in or create an account to place your order.', 'benoquik-core' ) . '</p></div>';
	}

	public static function redirect_field() {
		$url = self::requested_redirect();
		if ( $url ) {
			echo '<input type="hidden" name="bq_redirect_to" value="' . esc_attr( $url ) . '">';
		}
	}

	public static function after_login( $redirect, $user = null ) {
		$url = self::requested_redirect();
		return $url ? $url : $redirect;
	}

	public static function after_registration( $redirect ) {
		$url = self::requested_redirect();
		return $url ? $url : $redirect;
	}

	public static function assets() {
		if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
			wp_enqueue_style( 'benoquik-account', BENOQUIK_CORE_URL . 'assets/account.css', array(), BENOQUIK_CORE_VERSION );
		}
	}

	/* ------------------------------------------------- 2. terms at registration */

	public static function registration_checkbox() {
		// phpcs:ignore WordPress.Security.NonceVerification -- re-display after a failed submit.
		$checked = ! empty( $_POST['bq_accept_terms'] );
		printf(
			'<p class="form-row bq-terms-row"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox" for="bq_accept_terms"><input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="bq_accept_terms" id="bq_accept_terms" value="1" required aria-required="true"%s> <span>%s</span>&nbsp;<abbr class="required" title="%s">*</abbr></label></p>',
			checked( $checked, true, false ),
			wp_kses( self::links_html(), array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) ) ),
			esc_attr__( 'required', 'benoquik-core' )
		);
	}

	public static function validate_registration( $errors, $username, $password, $email ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce verifies the registration nonce before this filter.
		if ( empty( $_POST['bq_accept_terms'] ) ) {
			$errors->add( 'bq_terms_required', __( 'Please tick the box to agree to the Terms & Conditions and Privacy Policy before creating your account.', 'benoquik-core' ) );
		}
		return $errors;
	}

	public static function save_registration_acceptance( $customer_id, $data = array(), $password_generated = false ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- runs inside WooCommerce's nonce-checked registration.
		if ( empty( $_POST['bq_accept_terms'] ) ) {
			return;
		}
		update_user_meta(
			$customer_id,
			self::USER_META,
			array_merge(
				array(
					'accepted_at' => gmdate( 'c' ),
					'source'      => 'registration',
				),
				self::policy_versions()
			)
		);
	}

	public static function profile_record( $user ) {
		if ( ! current_user_can( 'edit_users' ) && get_current_user_id() !== $user->ID ) {
			return;
		}
		$rec = get_user_meta( $user->ID, self::USER_META, true );
		echo '<h2>' . esc_html__( 'Terms & Privacy acceptance (record)', 'benoquik-core' ) . '</h2><table class="form-table" role="presentation"><tbody>';
		if ( ! is_array( $rec ) || empty( $rec['accepted_at'] ) ) {
			echo '<tr><th>' . esc_html__( 'Status', 'benoquik-core' ) . '</th><td>' . esc_html__( 'No acceptance recorded (account created before this was added, or by an admin).', 'benoquik-core' ) . '</td></tr>';
		} else {
			$rows = array(
				__( 'Accepted', 'benoquik-core' )                 => self::fmt( $rec['accepted_at'] ) . ' (' . esc_html( $rec['source'] ?? '' ) . ')',
				__( 'Terms & Conditions version', 'benoquik-core' ) => self::page_label( $rec['terms_page_id'] ?? 0, $rec['terms_version'] ?? '' ),
				__( 'Privacy Policy version', 'benoquik-core' )     => self::page_label( $rec['privacy_page_id'] ?? 0, $rec['privacy_version'] ?? '' ),
			);
			foreach ( $rows as $label => $value ) {
				echo '<tr><th>' . esc_html( $label ) . '</th><td>' . wp_kses_post( $value ) . '</td></tr>';
			}
		}
		echo '</tbody></table><p class="description">' . esc_html__( 'Read-only. Recorded automatically when the customer registered.', 'benoquik-core' ) . '</p>';
	}

	private static function fmt( $iso ) {
		$ts = $iso ? strtotime( $iso ) : 0;
		return $ts ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) . ' T', $ts ) ) : '—';
	}

	private static function page_label( $id, $version ) {
		$title = $id ? get_the_title( $id ) : __( '(page not set)', 'benoquik-core' );
		return esc_html( $title ) . ' — ' . esc_html__( 'last updated', 'benoquik-core' ) . ' ' . self::fmt( $version );
	}

	/* ---------------------------------------------------- 3. terms at checkout */

	/** Required checkbox in the Checkout block, validated by the Store API itself. */
	public static function register_checkout_field() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => self::FIELD_ID,
				'label'    => __( 'I have read and agree to the Terms & Conditions and Privacy Policy', 'benoquik-core' ),
				'location' => 'order',
				'type'     => 'checkbox',
				'required' => true,
			)
		);
	}

	private static function route_exception( $code, $message, $status ) {
		if ( class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
			return new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( $code, $message, $status );
		}
		return new \Exception( $message );
	}

	/**
	 * Block checkout (Store API): runs before payment is processed, so a
	 * rejected order never reaches Razorpay.
	 */
	public static function store_api_guard( $order, $request ) {
		if ( ! is_user_logged_in() ) {
			throw self::route_exception( 'benoquik_login_required', __( 'Please log in or create an account to place your order.', 'benoquik-core' ), 401 );
		}
		$fields   = (array) $request->get_param( 'additional_fields' );
		$accepted = ! empty( $fields[ self::FIELD_ID ] ) && filter_var( $fields[ self::FIELD_ID ], FILTER_VALIDATE_BOOLEAN );
		if ( ! $accepted ) {
			throw self::route_exception( 'benoquik_terms_required', __( 'Please tick the box to agree to the Terms & Conditions and Privacy Policy.', 'benoquik-core' ), 400 );
		}
		self::record_on_order( $order, 'checkout' );
	}

	/** Classic (shortcode) checkout fallback. */
	public static function classic_guard() {
		if ( ! is_user_logged_in() ) {
			wc_add_notice( __( 'Please log in or create an account to place your order.', 'benoquik-core' ), 'error' );
		}
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce verifies the checkout nonce.
		if ( empty( $_POST['terms'] ) ) {
			wc_add_notice( __( 'Please tick the box to agree to the Terms & Conditions and Privacy Policy.', 'benoquik-core' ), 'error' );
		}
	}

	public static function classic_record( $order_id, $posted, $order ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce verifies the checkout nonce.
		if ( ! empty( $_POST['terms'] ) ) {
			self::record_on_order( $order, 'checkout' );
			$order->save();
		}
	}

	/** "Pay for order" form (e.g. admin-created orders): WooCommerce validates its terms box; record it. */
	public static function pay_form_record( $order ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce verifies the pay-for-order nonce.
		if ( ! empty( $_POST['terms'] ) && $order instanceof \WC_Order ) {
			self::record_on_order( $order, 'pay-for-order' );
			$order->save();
		}
	}

	private static function record_on_order( $order, $source ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$order->update_meta_data( self::ORDER_META, gmdate( 'c' ) );
		$order->update_meta_data( '_bq_terms_acceptance', array_merge( array( 'source' => $source ), self::policy_versions() ) );
	}

	public static function has_accepted( $order ) {
		return $order instanceof \WC_Order && (bool) $order->get_meta( self::ORDER_META );
	}

	public static function admin_order_record( $order ) {
		$at  = $order->get_meta( self::ORDER_META );
		$rec = (array) $order->get_meta( '_bq_terms_acceptance' );
		echo '<p class="form-field form-field-wide bq-terms-record"><strong>' . esc_html__( 'Terms & Privacy accepted at checkout:', 'benoquik-core' ) . '</strong><br>';
		if ( $at ) {
			echo self::fmt( $at ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in fmt().
			if ( ! empty( $rec['terms_version'] ) ) {
				echo '<br><small>' . esc_html__( 'T&C version:', 'benoquik-core' ) . ' ' . self::fmt( $rec['terms_version'] ) . ' · ' . esc_html__( 'Privacy version:', 'benoquik-core' ) . ' ' . self::fmt( $rec['privacy_version'] ?? '' ) . '</small>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		} else {
			echo '<span style="color:#b32d2e">' . esc_html__( 'Not recorded', 'benoquik-core' ) . '</span>';
		}
		echo '</p>';
	}
}
