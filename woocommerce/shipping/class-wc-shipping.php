<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 */

/**
 * Class: Yoast_WCSEO_Local_Shipping.
 */
class Yoast_WCSEO_Local_Shipping {

	/**
	 * Pickup settings.
	 *
	 * @var array
	 */
	private $settings = null;

	/**
	 * @var bool
	 */
	private $has_location_categories = null;

	public function init() {
		$this->settings = get_option( 'woocommerce_yoast_wcseo_local_pickup_settings' );

		// Actions.
		add_action( 'init', [ $this, 'find_location_categories' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'checkout_script' ] );
		add_action( 'woocommerce_thankyou', [ $this, 'woocommerce_thankyou' ] );

		// Filters.
		add_filter( 'woocommerce_shipping_methods', [ $this, 'add_shipping_method' ], 10, 1 );
		add_filter( 'woocommerce_customer_taxable_address', [ $this, 'taxable_address_filter' ], 999, 1 );
		add_filter( 'woocommerce_order_formatted_shipping_address', [ $this, 'order_formatted_shipping_address' ], 10, 2 );
		add_filter( 'admin_body_class', [ $this, 'add_admin_body_class' ] );
	}

	public function find_location_categories() {
		// Get all the terms ( which have locations! ).
		$terms = get_terms( 'wpseo_locations_category' );

		$this->has_location_categories = false;
		if ( is_array( $terms ) && ( ! empty( $terms ) ) ) {
			// Found some.
			$this->has_location_categories = true;
		}
	}

	public function add_admin_body_class( $classes ) {

		// Get all the terms ( which have locations! ).
		$terms = get_terms( 'wpseo_locations_category' );

		// Found some?
		$append_class = 'has-no-locations-categories';
		if ( $this->has_location_categories ) {
			$append_class = 'has-locations-categories';
		}

		// Add the admin body class.
		return "$classes $append_class";
	}

	public function order_formatted_shipping_address( $shipping_address, $order ) {

		// Get the specs of the current shipping method.
		$order_shipping_methods = $order->get_shipping_methods();
		$order_shipping_method  = array_shift( $order_shipping_methods );
		$location_id            = (int) str_replace( 'yoast_wcseo_local_pickup_', '', $order_shipping_method['method_id'] );

		// Only alter the shipping address when local shipping has been selected.
		if ( strstr( $order_shipping_method['method_id'], 'yoast_wcseo_local_pickup' ) === false ) {
			return $shipping_address;
		}

		// Get the shipping method address.
		$_wpseo_business_name    = $order_shipping_method['name'];
		$_wpseo_business_address = get_post_meta( $location_id, '_wpseo_business_address', true );
		$_wpseo_business_city    = get_post_meta( $location_id, '_wpseo_business_city', true );
		$_wpseo_business_zipcode = get_post_meta( $location_id, '_wpseo_business_zipcode', true );
		$_wpseo_business_state   = get_post_meta( $location_id, '_wpseo_business_state', true );
		$_wpseo_business_country = get_post_meta( $location_id, '_wpseo_business_country', true );

		// Store the shipping method address.
		$shipping_address['company']   = $_wpseo_business_name;
		$shipping_address['address_1'] = $_wpseo_business_address;
		$shipping_address['city']      = $_wpseo_business_city;
		$shipping_address['postcode']  = $_wpseo_business_zipcode;
		$shipping_address['state']     = $_wpseo_business_state;
		$shipping_address['country']   = $_wpseo_business_country;

		return $shipping_address;
	}

	public function taxable_address_filter( $address ) {
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		// If the session variable isn't present don't do anything.
		if ( ! $chosen_methods ) {
			return $address;
		}

		$chosen_shipping = $chosen_methods[0];

		// Only alter the address when local shipping has been selected.
		if ( strstr( $chosen_shipping, 'yoast_wcseo_local_pickup' ) === false ) {
			return $address;
		}

		$tax_based_on = get_option( 'woocommerce_tax_based_on' );
		if ( ( $tax_based_on !== 'base' ) && ( $tax_based_on !== 'billing' ) ) {

			$pickup_id = str_replace( 'yoast_wcseo_local_pickup_', '', $chosen_shipping );

			if ( $pickup_id === 'single' ) {

				$wpseo_local_settings = get_option( 'wpseo_local' );

				$address[0] = $wpseo_local_settings['location_country'];
				$address[1] = $wpseo_local_settings['location_state'];
				$address[2] = $wpseo_local_settings['location_zipcode'];
				$address[3] = $wpseo_local_settings['location_city'];
			}
			elseif ( is_numeric( $pickup_id ) ) {

				$pickup_id = (int) $pickup_id;

				$address[0] = get_post_meta( $pickup_id, '_wpseo_business_country', true );
				$address[1] = get_post_meta( $pickup_id, '_wpseo_business_state', true );
				$address[2] = get_post_meta( $pickup_id, '_wpseo_business_zipcode', true );
				$address[3] = get_post_meta( $pickup_id, '_wpseo_business_city', true );
			}
		}

		return $address;
	}

	public function woocommerce_thankyou( $order_id ) {

		$order                  = new WC_Order( $order_id );
		$order_shipping_methods = $order->get_shipping_methods();
		$order_shipping_method  = array_shift( $order_shipping_methods );

		if ( $order_shipping_method['method_id'] === 'yoast_wcseo_local_pickup' ) {

			$checkout_text = '';

			// Get our general fallback text.
			if ( isset( $this->settings['checkout_text'] ) ) {
				$checkout_text = $this->settings['checkout_text'];
			}

			// Echo the checkout text, not only when one has been entered,... somewhere.
			if ( ! empty( $checkout_text ) ) {
				echo '<header class="title"><h3>' . esc_html__( 'Local Pickup Information', 'yoast-local-seo' ) . '</h3></header>';
				echo '<p>' . $checkout_text . '</p>';
			}
		}
	}

	/**
	 * Determine whether we should load local scripts when at checkout.
	 *
	 * @return bool
	 */
	private function should_load_scripts() {
		return ( is_cart() || is_checkout() );
	}

	public function checkout_script() {

		if ( ! $this->should_load_scripts() ) {
			return;
		}

		$settings = get_option( 'woocommerce_yoast_wcseo_local_pickup_settings' );

		if ( wpseo_check_falses( $settings['enabled'] ) ) {
			$asset_manager = new WPSEO_Local_Admin_Assets();
			$asset_manager->register_assets();
			$asset_manager->enqueue_script( 'checkout' );

			// Localize the script with new data.
			$yoast_wcseo_local_translations            = [];
			$yoast_wcseo_local_translations['select2'] = 'disabled';

			if ( $settings['checkout_mode'] === 'select2' ) {
				$yoast_wcseo_local_translations['select2'] = 'enabled';
				$asset_manager->enqueue_script( 'select2' );
				$woocommerce_assets_path = str_replace( [ 'http:', 'https:' ], '', WC()->plugin_url() ) . '/assets/';

				// As the version shipped with WooCommerce is used, we don't manage the version ourselves.
				// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
				wp_enqueue_style( 'select2', $woocommerce_assets_path . 'css/select2.css' );
			}

			wp_localize_script( WPSEO_Local_Admin_Assets::PREFIX . 'checkout', 'yoast_wcseo_local_translations', $yoast_wcseo_local_translations );
		}
	}

	public function add_shipping_method( $methods ) {
		$methods['yoast_wcseo_local_pickup'] = 'Yoast_WCSEO_Local_Shipping_Method';
		return $methods;
	}

	public function enqueue_scripts() {

		if ( isset( $_GET['section'] ) && $_GET['section'] === 'yoast_wcseo_local_pickup' ) {

			$asset_manager = new WPSEO_Local_Admin_Assets();
			$asset_manager->register_assets();

			$asset_manager->enqueue_script( 'shipping-settings' );

			// Localize the script with new data.
			$yoast_wcseo_local_translations = [
				'has_categories'             => (int) $this->has_location_categories,
				'label_remove'               => esc_attr( __( 'Remove', 'yoast-local-seo' ) ),
				/* translators: Hidden accessibility text; %s expands to a pickup location title. */
				'label_allow_location'       => esc_attr( __( 'Allow pickup location: %s', 'yoast-local-seo' ) ),
				/* translators: Hidden accessibility text; %s expands to a pickup location title. */
				'label_costs_location'       => esc_attr( __( 'Costs for pickup location: %s', 'yoast-local-seo' ) ),
				'placeholder_costs_location' => esc_attr( __( 'Enter a price (excl. tax), like: 42.12', 'yoast-local-seo' ) ),
				/* translators: 1: expands to "Yoast SEO: Local SEO for WooCommerce"; 2: expands to "WooCommerce. */
				'warning_enable_pickup'      => esc_attr( sprintf( __( 'Note: Please make sure you have configured at least one location before enabling this shipping method, as enabling Local Pickup in %1$s will disable the default %2$s local pickup.', 'yoast-local-seo' ), 'Yoast SEO: Local SEO', 'WooCommerce' ) ),
			];
			wp_localize_script( WPSEO_Local_Admin_Assets::PREFIX . 'shipping-settings', 'yoast_wcseo_local_translations', $yoast_wcseo_local_translations );
		}
	}
}
