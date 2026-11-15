<?php
/**
 * Product-page blocks (used by the theme's Single Product template):
 *   benoquik/product-subtitle   – pack size under the title
 *   benoquik/rx-notice          – "℞ Prescription required …" box
 *   benoquik/delivery-estimate  – delivery line + pincode check
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

	/** Pincode rules: lines "prefix|text", e.g. "40|2–3 working days (Mumbai)". */
	public static function delivery_rules() {
		$rules = array();
		foreach ( explode( "\n", (string) Settings::get( 'delivery_rules' ) ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( 2 === count( $parts ) && preg_match( '/^\d{1,6}$/', $parts[0] ) && '' !== $parts[1] ) {
				$rules[ $parts[0] ] = $parts[1];
			}
		}
		return $rules;
	}

	public static function delivery() {
		$default = Settings::get( 'delivery_default' );
		$data    = wp_json_encode(
			array(
				'rules'   => self::delivery_rules(),
				'default' => $default,
				'invalid' => __( 'Please enter a valid 6-digit pincode.', 'benoquik-core' ),
				'prefix'  => __( 'Delivery to %s:', 'benoquik-core' ),
			)
		);
		ob_start();
		?>
		<div class="wp-block-benoquik-delivery-estimate bq-delivery" data-bq-delivery="<?php echo esc_attr( $data ); ?>">
			<p class="bq-delivery__line">
				<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h11v9H3zM14 9h4l3 3v3h-7"/><circle cx="7" cy="17" r="1.8"/><circle cx="17.5" cy="17" r="1.8"/></svg>
				<span class="bq-delivery__result" aria-live="polite"><strong><?php esc_html_e( 'Delivery:', 'benoquik-core' ); ?></strong> <?php echo esc_html( $default ); ?></span>
			</p>
			<form class="bq-delivery__form" novalidate>
				<label for="bq-pincode" class="screen-reader-text"><?php esc_html_e( 'Pincode', 'benoquik-core' ); ?></label>
				<input id="bq-pincode" name="pincode" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="6" pattern="[0-9]{6}" placeholder="<?php esc_attr_e( 'Enter pincode', 'benoquik-core' ); ?>">
				<button type="submit"><?php esc_html_e( 'Check', 'benoquik-core' ); ?></button>
			</form>
		</div>
		<?php
		return ob_get_clean();
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
		if ( ! $by ) {
			return '';
		}
		$extra = array_filter( array( Settings::get( 'marketer_address' ), Settings::get( 'marketer_licence' ) ) );
		return '<p class="wp-block-benoquik-marketed-by bq-marketed-by">' . esc_html__( 'Marketed by', 'benoquik-core' ) . ' <strong>' . esc_html( $by ) . '</strong>' . ( $extra ? '<br><span>' . esc_html( implode( ' · ', $extra ) ) . '</span>' : '' ) . '</p>';
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
