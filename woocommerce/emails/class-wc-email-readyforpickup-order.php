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

require_once WPSEO_LOCAL_PATH . 'woocommerce/emails/abstract-wc-email.php';

/**
 * A custom Ready for Pickup Order WooCommerce Email class.
 *
 * @since 0.1
 */
class WC_Email_ReadyForPickup_Order extends WPSEO_Local_WooCommerce_Email {

	/**
	 * Set email defaults.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Set ID, this simply needs to be a unique name.
		$this->id = 'wc_readyforpickup_order';

		// This is the title in WooCommerce Email settings.
		/* translators: Ready for pickup order = the title for an email-notification that is being sent to the customer when an order is ready to be picked up at the local store */
		$this->title = __( 'Ready for pickup order', 'yoast-local-seo' );

		// This is the description in WooCommerce email settings.
		$this->description = __( 'Ready for pickup order notification emails are sent when an order has been delivered to the local pickup store and is ready for pickup', 'yoast-local-seo' );

		// These are the default heading and subject lines that can be overridden using the settings.
		$this->heading = __( 'Ready for pickup order', 'yoast-local-seo' );
		$this->subject = __( 'Ready for pickup order', 'yoast-local-seo' );

		/*
		 * These define the locations of the templates that this email should use,
		 * we'll just use the new order template since this email is similar.
		 */
		$this->template_html  = 'emails/customer-readyforpickup-order.php';
		$this->template_plain = 'emails/plain/customer-readyforpickup-order.php';

		// Trigger on orders put to transporting.
		add_action( 'woocommerce_order_status_transporting_to_ready-for-pickup_notification', [ $this, 'trigger' ] );

		// Call parent constructor to load any other defaults not explicity defined here.
		parent::__construct();
	}
}
