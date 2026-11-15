<?php
/**
 * Product-page blocks (used by the theme's Single Product template):
 *   benoquik/product-subtitle   – pack size under the title
 *   benoquik/rx-notice          – "℞ Prescription required …" box
 *   benoquik/delivery-estimate  – delivery estimate by country
 *   benoquik/product-info       – Overview / How to use / … accordions
 *   benoquik/sticky-cart        – sticky add-to-cart bar on mobile
 * Plus the pill-style pack-size selector (progressive enhancement).
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Product_Blocks {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function assets() {
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_script(
				'benoquik-product',
				BENOQUIK_CORE_URL . 'assets/product.js',
				array(),
				BENOQUIK_CORE_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}

	public static function register() {
		$common = array(
			'editor_script_handles' => array( 'benoquik-blocks-editor' ),
			'style_handles'         => array( 'benoquik-blocks' ),
			'uses_context'          => array( 'postId', 'postType' ),
		);
		register_block_type( 'benoquik/product-subtitle', $common + array( 'render_callback' => array( __CLASS__, 'subtitle' ) ) );
		register_block_type( 'benoquik/rx-notice', $common + array( 'render_callback' => array( __CLASS__, 'rx_notice' ) ) );
		register_block_type( 'benoquik/delivery-estimate', $common + array( 'render_callback' => array( __CLASS__, 'delivery' ) ) );
		register_block_type( 'benoquik/product-info', $common + array( 'render_callback' => array( __CLASS__, 'info' ) ) );
		register_block_type( 'benoquik/price-note', $common + array( 'render_callback' => array( __CLASS__, 'price_note' ) ) );
		register_block_type( 'benoquik/shipping-note', $common + array( 'render_callback' => array( __CLASS__, 'shipping_note' ) ) );
		register_block_type( 'benoquik/marketed-by', $common + array( 'render_callback' => array( __CLASS__, 'marketed_by' ) ) );
		register_block_type( 'benoquik/sticky-cart', $common + array( 'render_callback' => array( __CLASS__, 'sticky' ) ) );
	}

	/** Current product (works in templates, the editor preview and loops). */
	private static function product( $block = null ) {
		$id = 0;
		if ( $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
			$id = (int) $block->context['postId'];
		}
		if ( ! $id ) {
			$id = get_the_ID() ? get_the_ID() : get_queried_object_id();
		}
		$product = $id ? wc_get_product( $id ) : null;
		return ( $product && 'product' === get_post_type( $product->get_id() ) ) ? $product : null;
	}

	public static function subtitle( $attrs, $content, $block ) {
		$p = self::product( $block );
		$s = $p ? get_post_meta( $p->get_id(), '_bq_subtitle', true ) : '';
		return $s ? '<p class="wp-block-benoquik-product-subtitle bq-product-subtitle">' . esc_html( $s ) . '</p>' : '';
	}

	public static function rx_notice( $attrs, $content, $block ) {
		$p = self::product( $block );
		if ( ! $p || ! Product_Fields::requires_prescription( $p ) ) {
			return '';
		}
		return '<div class="wp-block-benoquik-rx-notice bq-rx-notice" role="note"><span class="bq-rx-notice__icon" aria-hidden="true">℞</span><p>' . esc_html( Settings::get( 'rx_notice_text' ) ) . '</p></div>';
	}

	/** Country the visitor is most likely in: billing country, else IP geolocation, else none. */
	private static function guess_country() {
		$restricted = Settings::country_list( Settings::get( 'restricted_countries' ) );
		$c          = '';
		if ( is_user_logged_in() && WC()->customer ) {
			$c = WC()->customer->get_billing_country();
		}
		if ( ! $c && class_exists( 'WC_Geolocation' ) ) {
			$geo = \WC_Geolocation::geolocate_ip( '', true, false );
			$c   = $geo['country'] ?? '';
		}
		return ( $c && ! in_array( $c, $restricted, true ) ) ? $c : '';
	}

	/** Country-based delivery estimate (rules in Benoquik → Settings). */
	public static function delivery() {
		$rules     = International::delivery_rules();
		$default   = Settings::get( 'delivery_default' );
		$countries = WC()->countries->get_allowed_countries();
		$current   = self::guess_country();
		$text      = $current && isset( $rules[ $current ] ) ? $rules[ $current ] : $default;
		$data      = wp_json_encode( array( 'rules' => $rules, 'default' => $default ) );
		ob_start();
		?>
		<div class="wp-block-benoquik-delivery-estimate bq-delivery" data-bq-delivery="<?php echo esc_attr( $data ); ?>">
			<p class="bq-delivery__line">
				<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h11v9H3zM14 9h4l3 3v3h-7"/><circle cx="7" cy="17" r="1.8"/><circle cx="17.5" cy="17" r="1.8"/></svg>
				<span class="bq-delivery__result" aria-live="polite"><strong><?php esc_html_e( 'Estimated delivery:', 'benoquik-core' ); ?></strong> <span class="bq-delivery__text"><?php echo esc_html( $text ); ?></span></span>
			</p>
			<p class="bq-delivery__form">
				<label for="bq-delivery-country"><?php esc_html_e( 'Ship to', 'benoquik-core' ); ?></label>
				<select id="bq-delivery-country" autocomplete="country">
					<option value=""><?php esc_html_e( 'Choose your country', 'benoquik-core' ); ?></option>
					<?php foreach ( $countries as $code => $name ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current, $code ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/** "Prices in USD, excluding taxes, import duties and customs fees." */
	public static function price_note() {
		$t = trim( (string) Settings::get( 'price_note' ) );
		return $t ? '<p class="wp-block-benoquik-price-note bq-price-note">' . esc_html( $t ) . '</p>' : '';
	}

	/** "Ships from India · Tracked international delivery · Discreet packaging" */
	public static function shipping_note() {
		$t = trim( (string) Settings::get( 'shipping_note' ) );
		return $t ? '<p class="wp-block-benoquik-shipping-note bq-shipping-note">' . esc_html( $t ) . '</p>' : '';
	}

	public static function info( $attrs, $content, $block ) {
		$p = self::product( $block );
		if ( ! $p ) {
			return '';
		}
		$out   = '';
		$first = true;
		foreach ( Product_Fields::info_fields() as $key => $label ) {
			$html = trim( (string) get_post_meta( $p->get_id(), $key, true ) );
			if ( '' === $html ) {
				continue;
			}
			// Same markup as the core "Details" block, so it shares its styling.
			$out  .= sprintf(
				'<details class="wp-block-details"%s><summary>%s</summary><div class="bq-product-info__body">%s</div></details>',
				$first ? ' open' : '',
				esc_html( $label ),
				wpautop( wp_kses_post( $html ) )
			);
			$first = false;
		}
		return $out ? '<div class="wp-block-benoquik-product-info bq-product-info bq-accordion">' . $out . '</div>' : '';
	}

	public static function marketed_by() {
		$by = Settings::get( 'marketed_by' );
		return $by ? '<p class="wp-block-benoquik-marketed-by bq-marketed-by">' . esc_html__( 'Marketed by', 'benoquik-core' ) . ' <strong>' . esc_html( $by ) . '</strong>, ' . esc_html__( 'India', 'benoquik-core' ) . '</p>' : '';
	}

	public static function sticky( $attrs, $content, $block ) {
		$p = self::product( $block );
		if ( ! $p || ! $p->is_purchasable() ) {
			return '';
		}
		return sprintf(
			'<div class="wp-block-benoquik-sticky-cart bq-sticky-cart" aria-hidden="true" inert><div class="bq-sticky-cart__info"><span class="bq-sticky-cart__name">%1$s<span class="bq-sticky-cart__pack"></span></span><span class="bq-sticky-cart__price">%2$s</span></div><button type="button" class="bq-sticky-cart__button">%3$s</button></div>',
			esc_html( $p->get_name() ),
			wp_kses_post( $p->get_price_html() ),
			esc_html__( 'Add to cart', 'benoquik-core' )
		);
	}
}
