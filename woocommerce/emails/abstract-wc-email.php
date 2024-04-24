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
 * Class: WPSEO_Local_WooCommerce_Email.
 */
abstract class WPSEO_Local_WooCommerce_Email extends WC_Email {

	/**
	 * Set email defaults.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Call parent constructor to load any other defaults not explicity defined here.
		parent::__construct();

		// This sets the recipient to the current customer.
		$this->customer_email = true;
		$this->template_base  = WPSEO_LOCAL_PATH . 'woocommerce/templates/';
	}

	/**
	 * Determine if the email should actually be sent and setup email merge variables.
	 *
	 * @since 0.1
	 * @param int $order_id Order ID.
	 *
	 * @return void
	 */
	public function trigger( $order_id ) {

		// Bail if no order ID is present.
		if ( ! $order_id ) {
			return;
		}

		// Setup order object.
		$this->object = new WC_Order( $order_id );

		// Set mail recipient to the current customer.
		$this->recipient = $this->object->billing_email;

		// Replace variables in the subject/headings.
		$this->find[]    = '{order_date}';
		$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );

		$this->find[]    = '{order_number}';
		$this->replace[] = $this->object->get_order_number();

		if ( ! $this->is_enabled() ) {
			return;
		}

		// Woohoo, send the email!
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Retrieves the content for an HTML email.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_html() {
		$args = [
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'         => $this,
		];

		return wc_get_template_html( $this->template_html, $args );
	}

	/**
	 * Retrieves the content for a plain text email.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_plain() {
		$args = [
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'         => $this,
		];

		return wc_get_template_html( $this->template_plain, $args );
	}

	/**
	 * Initialize Settings Form Fields.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = [
			'enabled'    => [
				'title'   => __( 'Enable/Disable', 'yoast-local-seo' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'yoast-local-seo' ),
				'default' => 'yes',
			],
			'subject'    => [
				'title'       => __( 'Subject', 'yoast-local-seo' ),
				'type'        => 'text',
				/* translators: %s translates to the default email subject. */
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'yoast-local-seo' ), $this->subject ),
				'placeholder' => '',
				'default'     => '',
			],
			'heading'    => [
				'title'       => __( 'Email Heading', 'yoast-local-seo' ),
				'type'        => 'text',
				/* translators: %s translates to the default email heading. */
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'yoast-local-seo' ), $this->heading ),
				'placeholder' => '',
				'default'     => '',
			],
			'email_type' => [
				'title'       => __( 'Email type', 'yoast-local-seo' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'yoast-local-seo' ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => [
					'plain'     => __( 'Plain text', 'yoast-local-seo' ),
					'html'      => __( 'HTML', 'yoast-local-seo' ),
					'multipart' => __( 'Multipart', 'yoast-local-seo' ),
				],
			],
		];
	}
}
