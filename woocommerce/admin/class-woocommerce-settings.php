<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 * @since   4.0
 */

if ( ! defined( 'WPSEO_LOCAL_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! class_exists( 'WPSEO_Local_Admin_Woocommerce_Settings' ) ) {

	/**
	 * WPSEO_Local_Admin_API_Settings class.
	 *
	 * Build the WPSEO Local admin form.
	 *
	 * @since   4.1
	 */
	class WPSEO_Local_Admin_Woocommerce_Settings {

		/**
		 * Holds the slug for this settings tab.
		 *
		 * @var string
		 */
		private $slug = 'woocommerce';

		/**
		 * WPSEO_Local_Admin_API_Settings constructor.
		 */
		public function __construct() {
			add_filter( 'wpseo_local_admin_tabs', [ $this, 'create_tab' ], 99 );

			add_action( 'wpseo_local_admin_' . $this->slug . '_content', [ $this, 'tab_content' ], 10 );
		}

		/**
		 * Adds the WooCommerce Settings tab in the WPSEO local admin panel.
		 *
		 * @param array $tabs Array holding the tabs.
		 *
		 * @return mixed
		 */
		public function create_tab( $tabs ) {
			$tabs[ $this->slug ] = [
				/* translators: 1: expands to 'WooCommerce'. */
				'tab_title'     => sprintf( __( '%1$s settings', 'yoast-local-seo' ), 'WooCommerce' ),
				/* translators: 1: expands to 'Local SEO for WooCommerce'. */
				'content_title' => sprintf( esc_html__( '%1$s settings', 'yoast-local-seo' ), 'Local SEO for WooCommerce' ),
			];

			return $tabs;
		}

		/**
		 * Create tab content for API Settings.
		 *
		 * @return void
		 */
		public function tab_content() {
			echo '<p>';
			printf(
			/* translators: 1: expands to '<a>'; 2: expands to '</a>' */
				esc_html__( '%1$sClick here%2$s for the specific WooCommerce settings', 'yoast-local-seo' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=yoast_wcseo_local_pickup' ) ) . '">',
				'</a>'
			);
			echo '</p>';
		}
	}
}
