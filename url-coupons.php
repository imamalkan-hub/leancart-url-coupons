<?php
/**
 * Plugin Name: LeanCart URL Coupons
 * Plugin URI:  https://sewerlinelabs.com/
 * Description: Create a link like "store.com/?lc_coupon=CAMPAIGN" for a coupon code. When the customer opens the link, the coupon is applied to the cart automatically.
 * Version:     1.0.0
 * Author:      LeanCart
 * Author URI:  https://leancart.dev
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: url-coupons
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEANCART_URL_COUPONS_DEMO_SITE' ) ) {
	define( 'LEANCART_URL_COUPONS_DEMO_SITE', 'https://sewerlinelabs.com' );
}

require_once __DIR__ . '/includes/class-leancart-framework.php';

final class LeanCart_URL_Coupons {

	const SLUG = 'url-coupons';

	public static function init() {
		LeanCart_Framework::boot( array(
			'slug'    => self::SLUG,
			'name'    => 'LeanCart URL Coupons',
			'version' => '1.0.0',
			'file'    => __FILE__,
			'site'    => LEANCART_URL_COUPONS_DEMO_SITE,
		) );

		// WooCommerce may load after these plugins; defer the check to runtime.
		add_action( 'admin_notices', array( __CLASS__, 'wc_notice' ) );

		load_plugin_textdomain( 'url-coupons', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		add_action( 'leancart_' . self::SLUG . '_settings_fields', array( __CLASS__, 'fields' ) );

		// WooCommerce may load after this plugin; hooks are registered once WC is ready.
		if ( LeanCart_Framework::is_wc() ) {
			self::hooks();
		} else {
			add_action( 'woocommerce_loaded', array( __CLASS__, 'hooks' ) );
		}
	}

	public static function wc_notice() {
		if ( LeanCart_Framework::is_wc() ) { return; }
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( 'LeanCart URL Coupons' ) . '</strong>: ';
		esc_html_e( 'This plugin requires WooCommerce to be active.', 'url-coupons' );
		echo '</p></div>';
	}

	public static function fields() {
		LeanCart_Framework::field_text( 'invalid_msg', __('Invalid coupon message', 'url-coupons'), __('Shown if the coupon is not found.', 'url-coupons'), 'text' );
	}


	public static function hooks() {
		add_action( 'template_redirect', array( __CLASS__, 'apply' ), 20 );
	}

	public static function apply() {
		if ( ! isset( $_GET['lc_coupon'] ) || ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) { return; }
		$code = sanitize_text_field( wp_unslash( $_GET['lc_coupon'] ) );
		if ( ! $code ) { return; }
		if ( ! WC()->session->has_session() ) { WC()->session->set_customer_session_cookie( true ); }
		$coupon = new WC_Coupon( $code );
		if ( ! $coupon->get_id() ) {
			$msg = LeanCart_Framework::setting( 'invalid_msg' );
			if ( ! $msg ) { $msg = __( 'This coupon code is invalid.', 'url-coupons' ); }
			wc_add_notice( $msg, 'error' );
		} else {
			$applied = WC()->cart->apply_coupon( $code );
			if ( $applied ) { wc_add_notice( sprintf( __( 'Coupon %s has been applied.', 'url-coupons' ), esc_html( $code ) ) ); }
		}
		wp_safe_redirect( remove_query_arg( 'lc_coupon' ) );
		exit;
	}


}

LeanCart_URL_Coupons::init();

