<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

/**
 * Class: Yoast_WCSEO_Local_Emails.
 */
class Yoast_WCSEO_Local_Emails {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Filters.
		add_filter( 'woocommerce_email_classes', [ $this, 'add_email_classes' ] );
		add_filter( 'woocommerce_email_actions', [ $this, 'add_email_actions' ] );
	}

	public function add_email_actions( $hooks ) {

		$hooks[] = 'woocommerce_order_status_processing_to_transporting';
		$hooks[] = 'woocommerce_order_status_transporting_to_ready-for-pickup';

		return $hooks;
	}

	public function add_email_classes( $email_classes ) {

		// Include our custom email class.
		require_once WPSEO_LOCAL_PATH . 'woocommerce/emails/class-wc-email-transporting.php';
		require_once WPSEO_LOCAL_PATH . 'woocommerce/emails/class-wc-email-readyforpickup-order.php';

		// Add the email class to the list of email classes that WooCommerce loads.
		$email_classes['WC_Email_Transporting_Order']   = new WC_Email_Transporting_Order();
		$email_classes['WC_Email_ReadyForPickup_Order'] = new WC_Email_ReadyForPickup_Order();

		return $email_classes;
	}
}

new Yoast_WCSEO_Local_Emails();
