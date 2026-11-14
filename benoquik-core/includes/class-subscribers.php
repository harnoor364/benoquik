<?php
/**
 * Footer "Get treatment tips & reminders" sign-up.
 * Emails are stored privately (admin -> Benoquik -> Subscribers) and can be
 * exported as CSV for your email tool.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Subscribers {

	const CPT = 'bq_subscriber';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'admin_post_nopriv_benoquik_subscribe', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_benoquik_subscribe', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_benoquik_export_subscribers', array( __CLASS__, 'export' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
	}

	public static function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'label'           => __( 'Subscribers', 'benoquik-core' ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'benoquik',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}

	public static function menu() {
		add_submenu_page( 'benoquik', __( 'Export subscribers', 'benoquik-core' ), __( 'Export subscribers (CSV)', 'benoquik-core' ), 'manage_options', 'benoquik-export', array( __CLASS__, 'export_page' ) );
	}

	public static function export_page() {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=benoquik_export_subscribers' ), 'bq_export' );
		echo '<div class="wrap"><h1>' . esc_html__( 'Export subscribers', 'benoquik-core' ) . '</h1><p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Download CSV', 'benoquik-core' ) . '</a></p></div>';
	}

	public static function export() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'bq_export' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'benoquik-core' ) );
		}
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=benoquik-subscribers.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'email', 'subscribed_at' ) );
		foreach ( get_posts( array( 'post_type' => self::CPT, 'numberposts' => -1, 'post_status' => 'private' ) ) as $p ) {
			fputcsv( $out, array( $p->post_title, $p->post_date ) );
		}
		exit;
	}

	public static function handle() {
		$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$back = remove_query_arg( 'bq_sub', $back );
		if ( ! isset( $_POST['bq_sub_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bq_sub_nonce'] ), 'bq_subscribe' ) || ! empty( $_POST['bq_website'] ) ) {
			wp_safe_redirect( add_query_arg( 'bq_sub', 'error', $back ) . '#bq-subscribe' );
			exit;
		}
		$email = isset( $_POST['bq_email'] ) ? sanitize_email( wp_unslash( $_POST['bq_email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'bq_sub', 'invalid', $back ) . '#bq-subscribe' );
			exit;
		}
		$exists = get_posts( array( 'post_type' => self::CPT, 'title' => $email, 'post_status' => 'private', 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( ! $exists ) {
			wp_insert_post( array( 'post_type' => self::CPT, 'post_title' => $email, 'post_status' => 'private' ) );
		}
		wp_safe_redirect( add_query_arg( 'bq_sub', 'ok', $back ) . '#bq-subscribe' );
		exit;
	}

	public static function render_form() {
		$state = isset( $_GET['bq_sub'] ) ? sanitize_key( $_GET['bq_sub'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$msgs  = array(
			'ok'      => __( 'Thank you — you’re subscribed.', 'benoquik-core' ),
			'invalid' => __( 'Please enter a valid email address.', 'benoquik-core' ),
			'error'   => __( 'Something went wrong. Please try again.', 'benoquik-core' ),
		);
		ob_start();
		?>
		<form id="bq-subscribe" class="wp-block-benoquik-subscribe bq-subscribe" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<label for="bq-email" class="screen-reader-text"><?php esc_html_e( 'Email address', 'benoquik-core' ); ?></label>
			<input id="bq-email" type="email" name="bq_email" required autocomplete="email" placeholder="<?php esc_attr_e( 'Email address', 'benoquik-core' ); ?>">
			<input type="text" name="bq_website" value="" tabindex="-1" autocomplete="off" class="bq-hp" aria-hidden="true">
			<input type="hidden" name="action" value="benoquik_subscribe">
			<?php wp_nonce_field( 'bq_subscribe', 'bq_sub_nonce' ); ?>
			<button type="submit"><?php esc_html_e( 'Subscribe', 'benoquik-core' ); ?></button>
			<?php if ( isset( $msgs[ $state ] ) ) : ?>
				<p class="bq-subscribe__msg" role="status"><?php echo esc_html( $msgs[ $state ] ); ?></p>
			<?php endif; ?>
		</form>
		<?php
		return ob_get_clean();
	}
}
