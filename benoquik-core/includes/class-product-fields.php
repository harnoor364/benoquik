<?php
/**
 * Per-product fields on the WooCommerce product edit screen:
 * - General tab: "Requires prescription" checkbox + "Subtitle (pack size)".
 * - "Medicine information" tab: Overview, How to use, Side effects,
 *   Safety advice, Storage, FAQs — shown as accordions on the product page.
 *
 * Also: reviews off, no "Sale!" badges, friendlier variable-product button.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Product_Fields {

	/** meta key => label */
	public static function info_fields() {
		return array(
			'_bq_info_overview'     => __( 'Overview', 'benoquik-core' ),
			'_bq_info_how_to_use'   => __( 'How to use', 'benoquik-core' ),
			'_bq_info_side_effects' => __( 'Side effects', 'benoquik-core' ),
			'_bq_info_safety'       => __( 'Safety advice', 'benoquik-core' ),
			'_bq_info_storage'      => __( 'Storage', 'benoquik-core' ),
			'_bq_info_faqs'         => __( 'FAQs', 'benoquik-core' ),
		);
	}

	public static function init() {
		// Admin fields.
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'general_fields' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'tab_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );

		// Reviews off everywhere (prescription medicine: no unmoderated medical claims).
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'remove_tabs' ), 98 );
		add_filter( 'woocommerce_product_get_reviews_allowed', '__return_false' );
		add_filter( 'comments_open', array( __CLASS__, 'close_product_comments' ), 20, 2 );
		add_action( 'init', static fn() => remove_post_type_support( 'product', 'comments' ), 100 );
		add_filter( 'pre_option_woocommerce_enable_reviews', static fn() => 'no' );

		// No "Sale!" badges anywhere (MRP strikethrough is enough).
		add_filter( 'woocommerce_sale_flash', '__return_empty_string' );
		add_filter( 'render_block_woocommerce/product-sale-badge', '__return_empty_string' );

		// Card button text for pack-size products.
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'loop_button_text' ), 10, 2 );
	}

	public static function requires_prescription( $product ) {
		$product = wc_get_product( $product );
		if ( ! $product ) {
			return false;
		}
		$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		return 'yes' === get_post_meta( $id, '_bq_requires_prescription', true );
	}

	public static function general_fields() {
		echo '<div class="options_group">';
		woocommerce_wp_checkbox(
			array(
				'id'          => '_bq_requires_prescription',
				'label'       => __( 'Requires prescription', 'benoquik-core' ),
				'description' => __( 'Shows the ℞ notice on the product page and asks for a prescription at checkout.', 'benoquik-core' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => '_bq_subtitle',
				'label'       => __( 'Subtitle (pack size)', 'benoquik-core' ),
				'placeholder' => __( 'e.g. Monobenzone Cream USP 20% w/w · 1 tube (20 g)', 'benoquik-core' ),
				'desc_tip'    => true,
				'description' => __( 'Shown under the product name. Keep the pack size here, not in the product name.', 'benoquik-core' ),
			)
		);
		woocommerce_wp_textarea_input(
			array(
				'id'          => '_bq_admin_note',
				'label'       => __( 'Internal note (admin only)', 'benoquik-core' ),
				'description' => __( 'Never shown to customers.', 'benoquik-core' ),
				'desc_tip'    => true,
			)
		);
		echo '</div>';
	}

	public static function add_tab( $tabs ) {
		$tabs['bq_info'] = array(
			'label'    => __( 'Medicine information', 'benoquik-core' ),
			'target'   => 'bq_info_panel',
			'priority' => 15,
		);
		return $tabs;
	}

	public static function tab_panel() {
		global $post;
		echo '<div id="bq_info_panel" class="panel woocommerce_options_panel hidden" style="padding:0 12px 12px">';
		echo '<p class="description" style="padding:12px 0 0">' . esc_html__( 'Each box becomes an accordion on the product page. Leave a box empty to hide that accordion. Keep wording medically accurate — no cure, guarantee or timed-result claims.', 'benoquik-core' ) . '</p>';
		foreach ( self::info_fields() as $key => $label ) {
			echo '<h4 style="margin:18px 0 6px">' . esc_html( $label ) . '</h4>';
			wp_editor(
				(string) get_post_meta( $post->ID, $key, true ),
				'bq' . str_replace( '_', '', $key ),
				array(
					'textarea_name' => $key,
					'textarea_rows' => 6,
					'media_buttons' => false,
					'teeny'         => true,
				)
			);
		}
		echo '</div>';
	}

	public static function save( $product ) {
		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce verifies the product save nonce.
		$product->update_meta_data( '_bq_requires_prescription', isset( $_POST['_bq_requires_prescription'] ) ? 'yes' : 'no' );
		if ( isset( $_POST['_bq_admin_note'] ) ) {
			$product->update_meta_data( '_bq_admin_note', sanitize_textarea_field( wp_unslash( $_POST['_bq_admin_note'] ) ) );
		}
		if ( isset( $_POST['_bq_subtitle'] ) ) {
			$product->update_meta_data( '_bq_subtitle', sanitize_text_field( wp_unslash( $_POST['_bq_subtitle'] ) ) );
		}
		foreach ( array_keys( self::info_fields() ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$product->update_meta_data( $key, wp_kses_post( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
		// phpcs:enable
	}

	public static function remove_tabs( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	}

	public static function close_product_comments( $open, $post_id ) {
		return 'product' === get_post_type( $post_id ) ? false : $open;
	}

	public static function loop_button_text( $text, $product ) {
		return ( $product && $product->is_type( 'variable' ) && $product->is_purchasable() ) ? __( 'Choose pack', 'benoquik-core' ) : $text;
	}

	/**
	 * Default, medically careful information used by the demo import and by
	 * `wp benoquik repair-products` when a product's boxes are empty.
	 * GENERAL INFORMATION — have your pharmacist/medical advisor review it.
	 */
	public static function default_info() {
		return array(
			'_bq_info_overview'     => '<p><strong>Benoquik®</strong> is <strong>Monobenzone Cream USP 20% w/w</strong>, supplied in a 20 g tube and marketed by Quik Pharmaceuticals. It is a prescription depigmenting cream for people with <strong>extensive vitiligo</strong> (where most of the skin has already lost its colour). It permanently removes the remaining pigment from normally pigmented skin so that skin tone becomes even.</p><p><strong>It is not a cure for vitiligo and does not restore colour.</strong> The effect is permanent. Use only under the supervision of a dermatologist. It is not for cosmetic skin lightening, freckles, melasma or any other pigmentation condition.</p>',
			'_bq_info_how_to_use'   => '<ul><li><strong>Patch test first</strong> on a small area for about 7 days, as your doctor directs, and check for redness, itching or swelling.</li><li>Apply a <strong>thin layer</strong> only to the areas your doctor has prescribed, as often as prescribed.</li><li><strong>Avoid the eyes</strong>, mouth and inside of the nose.</li><li><strong>Wash your hands</strong> straight after applying.</li><li>Let the cream absorb before treated skin touches other people’s skin — it can lighten their skin too.</li><li>Do not apply to broken, sunburnt or irritated skin.</li></ul>',
			'_bq_info_side_effects' => '<p>Possible side effects include redness, burning, itching, dryness or a rash where the cream is applied. Monobenzone can also lighten skin that was <strong>not</strong> treated, including areas away from where you applied it.</p><p><strong>Stop using Benoquik and see your doctor</strong> if you notice marked irritation, swelling or blistering.</p>',
			'_bq_info_safety'       => '<ul><li><strong>Pregnancy:</strong> talk to your doctor before using Benoquik if you are pregnant or planning a pregnancy.</li><li><strong>Breastfeeding:</strong> talk to your doctor before use, and avoid applying where a baby’s skin could touch treated skin.</li><li><strong>Sun exposure:</strong> depigmented skin has little natural protection and burns easily. Use a broad-spectrum sunscreen every day, wear protective clothing and avoid strong midday sun.</li><li>Not for use in children unless a doctor specifically prescribes it.</li><li>Depigmentation is permanent — make the decision together with your dermatologist.</li></ul>',
			'_bq_info_storage'      => '<ul><li>Store below 25°C in a cool, dry place, away from direct sunlight.</li><li>Close the cap tightly after use.</li><li>Keep out of the reach and sight of children.</li><li>Do not use after the expiry date printed on the pack.</li></ul>',
			'_bq_info_faqs'         => '<p><strong>Does Benoquik cure vitiligo?</strong><br>No. It does not cure vitiligo or restore colour; it permanently removes remaining pigment so skin tone becomes even.</p><p><strong>Do I need a prescription?</strong><br>Yes. You will upload it at checkout and our pharmacist verifies every order before dispatch.</p><p><strong>How long does it take?</strong><br>Change is gradual and differs from person to person. Keep your follow-up appointments with your dermatologist.</p>',
		);
	}
}
