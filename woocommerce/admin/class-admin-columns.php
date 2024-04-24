<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 */

if ( ! class_exists( 'Yoast_WCSEO_Local_Admin_Columns' ) ) {

	/**
	 * Class: Yoast_WCSEO_Local_Admin_Columns.
	 */
	class Yoast_WCSEO_Local_Admin_Columns {

		/**
		 * Pickup settings.
		 *
		 * @var array
		 */
		private $settings = null;

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->settings = get_option( 'woocommerce_yoast_wcseo_local_pickup_settings' );

			// Only proceed if the Shipping method is enabled.
			if ( isset( $this->settings['enabled'] ) && ( $this->settings['enabled'] === 'yes' ) ) {
				$this->init();
			}
		}

		public function init() {

			// Filters.
			add_filter( 'manage_wpseo_locations_posts_columns', [ $this, 'columns_head' ] );

			// Actions.
			add_action( 'manage_wpseo_locations_posts_custom_column', [ $this, 'columns_content' ], 10, 2 );
		}

		public function columns_head( $defaults ) {

			// Add our custom column head.
			$defaults['local_pickup_allowed'] = __( 'Local Pickup allowed?', 'yoast-local-seo' );

			return $defaults;
		}

		public function columns_content( $column_name, $post_id ) {

			// Create custom column content.
			if ( $column_name === 'local_pickup_allowed' ) {

				// First we check if this Location has been enabled via Location Specific settings.
				if ( isset( $this->settings['location_specific'][ $post_id ]['allowed'] ) && ( $this->settings['location_specific'][ $post_id ]['allowed'] === 'yes' ) ) {
					esc_html_e( 'Yes', 'yoast-local-seo' );
					return;
				}

				// First we check if this Location has been enabled via Location Specific settings.
				if ( isset( $this->settings['location_specific'][ $post_id ] ) && ( ! isset( $this->settings['location_specific'][ $post_id ]['allowed'] ) ) ) {
					esc_html_e( 'No', 'yoast-local-seo' );
					return;
				}

				// Otherwise check for an allowed category.
				$terms = get_the_terms( $post_id, 'wpseo_locations_category' );
				if ( $terms && ! is_wp_error( $terms ) ) {

					foreach ( $terms as $term ) {

						if ( isset( $this->settings['category_specific'][ $term->term_id ]['allowed'] ) && ( $this->settings['category_specific'][ $term->term_id ]['allowed'] === 'yes' ) ) {
							esc_html_e( 'Yes', 'yoast-local-seo' );
							return;
						}
					}
				}

				// Echo a negative if nothing has been found.
				esc_html_e( 'No', 'yoast-local-seo' );
			}
		}
	}
}
