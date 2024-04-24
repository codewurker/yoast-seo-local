<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 */

/**
 * Class: Yoast_WCSEO_Local_Transport.
 *
 * @deprecated 14.9
 * @codeCoverageIgnore
 */
class Yoast_WCSEO_Local_Transport {

	/**
	 * Inits the class.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 * @return void
	 */
	public function init() {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		add_action( 'admin_menu', [ $this, 'register_submenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_styles' ] );
	}

	/**
	 * Registers styles.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 * @return void
	 */
	public function admin_styles() {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		if ( get_current_screen()->id === 'woocommerce_page_yoast_wcseo_local_transport' ) {
			wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', [], WC_VERSION );
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}
	}

	/**
	 * Registers submenu
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function register_submenu() {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		add_submenu_page(
			'woocommerce',
			__( 'Transport', 'yoast-local-seo' ),
			__( 'Transport', 'yoast-local-seo' ),
			'manage_options',
			'yoast_wcseo_local_transport',
			[ $this, 'menu_callback' ]
		);
	}

	/**
	 * Makes the menu item content.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 * @return void
	 */
	public function menu_callback() {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		echo '<h3>' . esc_html__( 'Transport', 'yoast-local-seo' ) . '</h3>';
		/* translators: transport-page-description-text = a container for placing an explanatory text for the Transport page, it elaborates on what the page is actually for */
		echo '<p>' . esc_html__( 'transport-page-description-text', 'yoast-local-seo' ) . '</p>';

		$list = new Yoast_WCSEO_Local_Transport_List();
		$list->prepare_items();
		$list->items = $this->get_transport_items();
		usort( $list->items, [ $list, 'usort_reorder' ] );
		$list->display();
	}

	/**
	 * Gets data from the database.
	 *
	 * @deprecated 14.9
	 * @codeCoverageIgnore
	 *
	 * @return array|object|stdClass[]|null
	 */
	public function get_transport_items() {
		_deprecated_function( __METHOD__, 'Yoast Local SEO 14.9' );
		global $wpdb;

		$query = "
			SELECT p.*
			FROM wp_woocommerce_order_itemmeta woim
			LEFT JOIN wp_woocommerce_order_items woi ON woi.order_item_id = woim.order_item_id
			LEFT JOIN wp_posts p ON p.ID = woi.order_id
			WHERE ( p.post_status = 'wc-processing' OR p.post_status = 'wc-transporting' OR p.post_status = 'wc-ready-for-pickup' )
			AND woim.meta_key = 'method_id'
			AND woim.meta_value LIKE 'yoast_wcseo_local_pickup%';
		";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is defined as a literal string above. No preparing needed.
		return $wpdb->get_results( $query );
	}
}
