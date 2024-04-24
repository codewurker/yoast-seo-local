<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 */

use Yoast\WP\Local\PostType\PostType;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class: Yoast_WCSEO_Local_Shipping_Method.
 */
class Yoast_WCSEO_Local_Shipping_Method extends WC_Shipping_Flat_Rate {

	/**
	 * Available locations.
	 *
	 * @var array
	 */
	private $available_locations = [];

	/**
	 * Save locations.
	 *
	 * @var array
	 */
	private $saved_locations = [];

	/**
	 * Location categories.
	 *
	 * @var array
	 */
	private $location_categories = [];

	/**
	 * @var string The post type used for Yoast SEO: Local locations.
	 */
	private $local_post_type;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id           = 'yoast_wcseo_local_pickup';
		$this->title        = __( 'Local store pickup', 'yoast-local-seo' );
		$this->method_title = __( 'Local Store Pickup', 'yoast-local-seo' );

		$description = __(
			'This shipping method enables customers to pick up their order in local stores defined in the Local SEO plugin.',
			'yoast-local-seo'
		);

		$deprecation_title = sprintf(
			/* translators: %1$s <strong> open tag, %2$s Yoast Local SEO, %3$s <strong> close tag */
			__( '%1$sThis feature will soon be deprecated from %2$s%3$s', 'yoast-local-seo' ),
			'<strong>',
			'Yoast Local SEO',
			'</strong>'
		);
		$deprecation_body = sprintf(
			/* translators: %1$s <br> tag, %2$s <a> open tag, %3$s <a> close tag */
			__( 'Please use the \'Local Pickup\' feature in the latest version of WooCommerce instead. To ensure functionality, please re-enter your settings there. %1$s%2$sRead more about setting up%3$s.', 'yoast-local-seo' ),
			'<br>',
			'<a href="' . WPSEO_Shortlinker::get( 'https://yoa.st/local-setting-up-shipping' ) . '" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);

		$deprecation_alert = '<div class="inline notice notice-warning woocommerce-message woocommerce-notice-invalid-variation"><p>' . $deprecation_title . '<br>' . $deprecation_body . '</p></div>';

		$this->method_description = $deprecation_alert . $description;

		$this->enabled = $this->get_option( 'enabled' );

		$post_type_instance = new PostType();
		$post_type_instance->initialize();
		$this->local_post_type = $post_type_instance->get_post_type();

		$this->init();
	}

	public function init() {
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ], 0 );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'save_category_options' ], 0 );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'save_location_options' ], 0 );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'flush_shipping_cache' ], 1 );
		add_action( 'update_option_woocommerce_' . $this->id . '_settings', [ $this, 'save_shadow_setting' ], 10, 2 );
	}

	public function init_form_fields() {
		$this->form_fields = [
			'enabled'        => [
				'title'   => __( 'Enable/Disable', 'yoast-local-seo' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this shipping method', 'yoast-local-seo' ),
				'default' => 'no',
			],
			'checkout_text'  => [
				'title' => __( 'Thank you text', 'yoast-local-seo' ),
				'type'  => 'textarea',
				'label' => __( 'The text that appears during the checkout process for this shipping method', 'yoast-local-seo' ),
			],
			'checkout_mode'  => [
				'title'   => __( 'Checkout mode', 'yoast-local-seo' ),
				'label'   => __( 'Choose between these checkout modes', 'yoast-local-seo' ),
				'type'    => 'select',
				'default' => 'radio',
				'options' => [
					'radio'   => __( 'Radio', 'yoast-local-seo' ),
					'select'  => __( 'Dropdown (basic)', 'yoast-local-seo' ),
					'select2' => __( 'Dropdown (advanced)', 'yoast-local-seo' ),
				],
			],
			'category_costs' => [
				'type' => 'category_costs_table',
			],
			'location_costs' => [
				'type' => 'location_costs_table',
			],
		];

		if ( ! wpseo_has_multiple_locations() ) {
			$cost_desc = __( 'Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'yoast-local-seo' ) . '<br/><br/>' . __( 'Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'yoast-local-seo' );

			$this->form_fields['costs'] = [
				'title'       => __( 'Local pickup costs', 'yoast-local-seo' ),
				'desc_tip'    => esc_attr( $cost_desc ),
				'type'        => 'currency',
				'label'       => __( 'The costs for the local pickup for customers', 'yoast-local-seo' ),
				'placeholder' => __( 'Enter a price (excl. tax), like: 42.12', 'yoast-local-seo' ),
			];
		}
	}

	public function resolve_defaults( $location ) {

		$defaults = [
			'status' => '',
			'price'  => '',
		];

		// Get the category-terms for this location.
		$terms = get_the_terms( $location->ID, 'wpseo_locations_category' );

		// If we have found any...
		if ( is_array( $terms ) && ( ! empty( $terms ) ) ) {

			// There can be only one...
			$connor_mccloud = array_shift( $terms );

			// Lookup...
			foreach ( $this->location_categories as $category ) {

				if ( $category->term_id === $connor_mccloud->term_id ) {

					if ( $category->allowed === true ) {
						$defaults['status'] = __( 'Default: Allow', 'yoast-local-seo' );
					}
					else {
						$defaults['status'] = __( 'Default: Disallow', 'yoast-local-seo' );
					}

					/* translators: %d translates to the default price for a location category. */
					$defaults['price'] = sprintf( __( 'Default: %d', 'yoast-local-seo' ), $category->price );
				}
			}
		}

		return $defaults;
	}

	public function save_category_options() {

		$category_specific_settings = [];
		$field_names                = [
			'yoast_wcseo_local_pickup_cat_allowed',
			'yoast_wcseo_local_pickup_cat_cost',
		];
		$posted_ids                 = $this->get_posted_ids_for_keys( $field_names );

		foreach ( $posted_ids as $posted_id ) {
			if ( isset( $_POST['yoast_wcseo_local_pickup_cat_allowed'][ $posted_id ] ) && $_POST['yoast_wcseo_local_pickup_cat_allowed'][ $posted_id ] === 'on' ) {
				$category_specific_settings[ $posted_id ]['allowed'] = 'yes';
			}

			if ( isset( $_POST['yoast_wcseo_local_pickup_cat_cost'][ $posted_id ] ) ) {
				$category_specific_settings[ $posted_id ]['price'] = $this->sanitize_costs_field( $_POST['yoast_wcseo_local_pickup_cat_cost'][ $posted_id ] );
			}
		}

		$this->save_category_specific_settings( $category_specific_settings );
	}

	public function get_posted_ids_for_keys( $keys = [] ) {
		$ids = [];

		foreach ( $keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashing not needed for numeric values and the intval mapping takes care of sanitization.
				$ids = array_merge( $ids, array_map( 'intval', array_keys( $_POST[ $key ] ) ) );
			}
		}

		return $ids;
	}

	/**
	 * This method sanitizes the entered shipping costs.
	 *
	 * @param string $raw_post_value Raw value, entered in the costs input field.
	 *
	 * @return string Sanitized value.
	 */
	public function sanitize_costs_field( $raw_post_value ) {

		// First perform some basic sanitization.
		$sanitized_value = stripslashes( $raw_post_value );
		$sanitized_value = wp_specialchars_decode( $sanitized_value );
		$sanitized_value = sanitize_text_field( $sanitized_value );

		return $sanitized_value;
	}

	public function save_category_specific_settings( $settings ) {
		$this->save_settings_subset( 'category_specific', $settings );
	}

	public function save_settings_subset( $key, $settings_subset ) {
		$settings         = get_option( $this->plugin_id . $this->id . '_settings' );
		$settings[ $key ] = $settings_subset;
		update_option( $this->plugin_id . $this->id . '_settings', $settings );
	}

	public function save_location_options() {

		$location_specific_settings = [];
		$field_names                = [
			'yoast_wcseo_local_pickup_location_allowed',
			'yoast_wcseo_local_pickup_location_cost',
		];
		$posted_ids                 = $this->get_posted_ids_for_keys( $field_names );

		foreach ( $posted_ids as $posted_id ) {
			if ( isset( $_POST['yoast_wcseo_local_pickup_location_allowed'][ $posted_id ] ) && $_POST['yoast_wcseo_local_pickup_location_allowed'][ $posted_id ] === 'on' ) {
				$location_specific_settings[ $posted_id ]['allowed'] = 'yes';
			}

			if ( isset( $_POST['yoast_wcseo_local_pickup_location_cost'][ $posted_id ] ) ) {
				$location_specific_settings[ $posted_id ]['price'] = $this->sanitize_costs_field( $_POST['yoast_wcseo_local_pickup_location_cost'][ $posted_id ] );
			}
		}

		$this->save_location_specific_settings( $location_specific_settings );
	}

	/**
	 * Save a shadow setting in the Yoast SEO: Local options in order to track the usage of local pickup.
	 *
	 * @param array $old_value An array containing the old values of the settings.
	 * @param array $new_value An array containing the new values of the settings.
	 *
	 * @return void
	 */
	public function save_shadow_setting( $old_value, $new_value ) {
		if ( $old_value['enabled'] !== $new_value['enabled'] || $old_value['location_specific'] !== $new_value['location_specific'] ) {
			$value = 0;

			if ( $new_value['enabled'] === 'no' ) {
				$value = $new_value['enabled'];
			}

			if ( $new_value['enabled'] === 'yes' ) {
				foreach ( $new_value['location_specific'] as $location ) {
					if ( $location['allowed'] === 'yes' ) {
						++$value;
					}
				}
			}

			WPSEO_Options::set( 'woocommerce_local_pickup_setting', $value );
		}
	}

	public function save_location_specific_settings( $settings ) {
		$this->save_settings_subset( 'location_specific', $settings );
	}

	public function calculate_shipping( $package = [] ) {

		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! wpseo_has_multiple_locations() ) {
			$price = ( $this->settings['costs'] ?? 0 );

			// Evaluate the price, it may contain shortcodes.
			$args_for_shortcode = [
				'qty'  => $this->get_package_item_qty( $package ),
				'cost' => $package['contents_cost'],
			];
			$price              = $this->evaluate_cost( $price, $args_for_shortcode );

			$rate_args = [
				'id'      => $this->id . '_single',
				'label'   => __( 'Local store pickup', 'yoast-local-seo' ),
				'cost'    => $price,
				'package' => $package,
			];
			$this->add_rate( $rate_args );

			return;
		}

		// First we harvest all the single locations.
		$single_locations           = [];
		$allowed_location_ids       = [];
		$disallowed_location_ids    = [];
		$location_specific_settings = $this->get_location_specific_settings();
		if ( is_array( $location_specific_settings ) && ( ! empty( $location_specific_settings ) ) ) {

			// Get the specs for all entered single locations.
			foreach ( $location_specific_settings as $id => $location_setting ) {
				if ( isset( $location_setting['allowed'] ) && ( $location_setting['allowed'] === 'yes' ) ) {
					$allowed_location_ids[ $id ] = $location_setting['price'];
				}
				else {
					array_push( $disallowed_location_ids, $id );
				}
			}

			// If we have single locations that are allowed,...go get them.
			if ( is_array( $allowed_location_ids ) && ( ! empty( $allowed_location_ids ) ) ) {

				$params = [
					'post_type'      => $this->local_post_type,
					'posts_per_page' => -1,
					'post__in'       => array_keys( $allowed_location_ids ),
					'no_found_rows'  => true,
				];

				$single_locations = get_posts( $params );
			}
		}

		$allowed_category_ids    = [];
		$disallowed_category_ids = [];
		foreach ( $this->get_category_specific_settings() as $id => $category_setting ) {
			if ( isset( $category_setting['allowed'] ) && ( $category_setting['allowed'] === 'yes' ) ) {
				$allowed_category_ids[ $id ] = $category_setting['price'];
			}
			else {
				array_push( $disallowed_category_ids, $id );
			}
		}

		// Secondly we get the locations by category, ignoring the single ID's that are specifically not allowed.
		$params = [
			'post_type'      => $this->local_post_type,
			'posts_per_page' => -1,
			'post__not_in'   => array_values( $disallowed_location_ids ),
			'no_found_rows'  => true,
			'tax_query'      => [
				'relation' => 'AND',
				[
					'taxonomy' => 'wpseo_locations_category',
					'field'    => 'term_id',
					'terms'    => array_keys( $allowed_category_ids ),
				],
				[
					'taxonomy' => 'wpseo_locations_category',
					'field'    => 'term_id',
					'terms'    => array_values( $disallowed_category_ids ),
					'operator' => 'NOT IN',
				],
			],
		];

		if ( empty( $allowed_category_ids ) && empty( $disallowed_category_ids ) ) {
			unset( $params['tax_query'] );
		}

		$category_locations = get_posts( $params );

		// Merge all harvested locations.
		$locations = array_merge( $category_locations, $single_locations );

		foreach ( $locations as $location ) {

			unset( $price );

			if ( isset( $allowed_location_ids[ $location->ID ] ) && trim( $allowed_location_ids[ $location->ID ] ) !== '' ) {
				$price = $allowed_location_ids[ $location->ID ];
			}
			else {
				$location_categories = get_the_terms( $location->ID, 'wpseo_locations_category' );

				if ( is_array( $location_categories ) && ( ! empty( $location_categories ) ) ) {
					foreach ( $location_categories as $cat ) {
						if ( isset( $allowed_category_ids[ $cat->term_id ] ) ) {
							if ( ! empty( $allowed_category_ids[ $cat->term_id ] ) ) {
								$price = $allowed_category_ids[ $cat->term_id ];
							}
						}
					}
				}
			}

			if ( ! isset( $price ) ) {
				continue;
			}

			// Evaluate the price, it may contain shortcodes.
			$args_for_shortcode = [
				'qty'  => $this->get_package_item_qty( $package ),
				'cost' => $package['contents_cost'],
			];
			$price              = $this->evaluate_cost( $price, $args_for_shortcode );

			$rate_label = __( 'Local store pickup', 'yoast-local-seo' );

			if ( count( $locations ) > 1 ) {
				$rate_label = $location->post_title;
			}

			$rate_args = [
				'id'      => $this->id . '_' . $location->ID,
				'label'   => $rate_label,
				'cost'    => $price,
				'package' => $package,
			];

			$this->add_rate( $rate_args );
		}
	}

	public function get_location_specific_settings() {
		return $this->get_settings_subset( 'location_specific' );
	}

	public function get_settings_subset( $key ) {
		$settings = get_option( $this->plugin_id . $this->id . '_settings' );

		return ( isset( $settings[ $key ] ) ) ? $settings[ $key ] : [];
	}

	public function get_category_specific_settings() {
		return $this->get_settings_subset( 'category_specific' );
	}

	public function generate_category_costs_table_html() {

		$this->location_categories  = $this->get_location_categories();
		$category_specific_settings = $this->get_category_specific_settings();

		if ( ! wpseo_has_multiple_locations() ) {
			return '';
		}

		if ( empty( $this->location_categories ) || is_wp_error( $this->location_categories ) ) {
			$post_type_instance = new PostType();
			$post_type_instance->initialize();

			$url = admin_url( 'edit-tags.php?taxonomy=wpseo_locations_category&post_type=' . $post_type_instance->get_post_type() );

			/* translators: %s expands to the admin URL to add location categories. */
			$no_location_cats_text = __(
				'You have not yet added any location categories, or you haven\'t assigned locations yet to these categories. After <a href="%s">adding location categories</a>, you can set category specific shipping settings here.',
				'yoast-local-seo'
			);
			$no_location_cats_text = sprintf( $no_location_cats_text, esc_url( $url ) );

			return '<p>' . $no_location_cats_text . '</p>';
		}

		foreach ( $this->location_categories as $key => $value ) {
			if ( isset( $category_specific_settings[ $value->term_id ] ) ) {
				if ( isset( $category_specific_settings[ $value->term_id ]['allowed'] ) ) {
					$this->location_categories[ $key ]->allowed = ( $category_specific_settings[ $value->term_id ]['allowed'] === 'yes' );
				}

				if ( isset( $category_specific_settings[ $value->term_id ]['price'] ) ) {
					$this->location_categories[ $key ]->price = $category_specific_settings[ $value->term_id ]['price'];
				}
			}
		}

		ob_start();
		include WPSEO_LOCAL_PATH . 'woocommerce/shipping/includes/category-costs-table.php';

		return ob_get_clean();
	}

	public function get_location_categories() {
		return get_terms(
			[ 'wpseo_locations_category' ]
		);
	}

	public function generate_location_costs_table_html() {
		if ( ! wpseo_has_multiple_locations() ) {
			$url = admin_url( 'admin.php?page=wpseo_local' );

			/* translators: %s expands to the URL of the Yoast SEO: Local admin page. */
			$single_location_text = __(
				'You manage only a single location. In the <a href="%s">Local SEO settings</a>, you can specify if you want to manage multiple locations.',
				'yoast-local-seo'
			);
			$single_location_text = sprintf( $single_location_text, esc_url( $url ) );

			return '<p>' . $single_location_text . '</p>';
		}

		$this->available_locations = $this->get_available_locations();
		$this->saved_locations     = $this->get_saved_locations();

		if ( ( empty( $this->available_locations ) && empty( $this->saved_locations ) ) || is_wp_error( $this->available_locations ) ) {
			$post_type_instance = new PostType();
			$post_type_instance->initialize();

			$url = admin_url( 'edit.php?post_type=' . $post_type_instance->get_post_type() );

			/* translators: %s expands to the admin URL to add locations. */
			$no_locations_text = __(
				'You have not yet added any locations. After <a href="%s">adding locations</a>, you can set location specific shipping settings here.',
				'yoast-local-seo'
			);
			$no_locations_text = sprintf( $no_locations_text, esc_url( $url ) );

			return '<p>' . $no_locations_text . '</p>';
		}

		$location_specific_settings = $this->get_location_specific_settings();

		foreach ( $this->saved_locations as $key => $value ) {
			if ( isset( $location_specific_settings[ $value->ID ] ) ) {
				if ( isset( $location_specific_settings[ $value->ID ]['allowed'] ) ) {
					$this->saved_locations[ $key ]->allowed = ( $location_specific_settings[ $value->ID ]['allowed'] === 'yes' );
				}

				if ( isset( $location_specific_settings[ $value->ID ]['price'] ) ) {
					$this->saved_locations[ $key ]->price = $location_specific_settings[ $value->ID ]['price'];
				}
			}
		}

		ob_start();
		include WPSEO_LOCAL_PATH . 'woocommerce/shipping/includes/location-costs-table.php';

		return ob_get_clean();
	}

	public function get_available_locations() {
		$saved_location_ids = array_keys( $this->get_location_specific_settings() );
		$post_criteria      = [
			'post__not_in'   => $saved_location_ids,
			'post_type'      => $this->local_post_type,
			'posts_per_page' => -1,
		];

		return get_posts( $post_criteria );
	}

	public function get_saved_locations() {
		$saved_location_ids = array_keys( $this->get_location_specific_settings() );

		if ( empty( $saved_location_ids ) ) {
			return [];
		}

		$post_criteria = [
			'post__in'       => $saved_location_ids,
			'post_type'      => $this->local_post_type,
			'posts_per_page' => -1,
		];

		return get_posts( $post_criteria );
	}

	/**
	 * Flush the transients that hold the shipping methods. This is to prevent cached shipping methods being shown.
	 *
	 * @since 9.7
	 *
	 * @return void
	 */
	public function flush_shipping_cache() {
		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%wc_ship%'" );
	}
}
