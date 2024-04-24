<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_LOCAL\Admin
 */

use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\Local\Repositories\Api_Keys_Repository;
use Yoast\WP\SEO\Presenters\Admin\Alert_Presenter;

if ( ! defined( 'WPSEO_LOCAL_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! class_exists( 'WPSEO_Local_Admin' ) ) {

	/**
	 * Class that holds most of the admin functionality for WP SEO Local.
	 */
	class WPSEO_Local_Admin {

		/**
		 * Option name.
		 *
		 * @var string
		 */
		public $option_name = 'wpseo_local';

		/**
		 * Admin Asset Manager object.
		 *
		 * @var WPSEO_Local_Admin_Assets
		 */
		private $asset_manager;

		/**
		 * Holds the API key repository class.
		 *
		 * @var Api_Keys_Repository
		 */
		private $api_repository;

		/**
		 * @var bool True if the current page is the Yoast SEO: Local settings page.
		 */
		private $is_settings_page;

		/**
		 * @var bool True if the current page is a Yoast SEO: Local locations page.
		 */
		private $is_locations_page;

		/**
		 * @var bool True if the current page is a Yoast SEO: Local locations category term page.
		 */
		private $is_locations_category_term_page;

		/**
		 * @var bool True if the current page is a post edit or create page.
		 */
		private $is_edit_page;

		/**
		 * @var bool True if the current page is the Yoast SEO titles settings page.
		 */
		private $is_yoast_seo_titles_page;

		/**
		 * Class constructor
		 */
		public function __construct() {
			$this->register_custom_option();

			add_action( 'admin_init', [ $this, 'options_init' ] );
			add_action( 'current_screen', [ $this, 'set_current_screen' ] );

			// Adds page to WP SEO menu.
			add_action( 'wpseo_submenu_pages', [ $this, 'register_settings_page' ], 20 );

			// Register local into admin_pages.
			$this->register_wpseo();

			// Add styles and scripts.
			add_action( 'admin_enqueue_scripts', [ $this, 'config_page_scripts' ] );
			add_action( 'admin_print_styles', [ $this, 'config_page_styles' ] );
			add_action( 'admin_footer', [ $this, 'config_page_footer' ] );

			// Flush the rewrite rules after options change.
			add_action( 'update_option_wpseo_local', [ $this, 'update_multiple_locations' ], 10, 2 );
			add_action( 'admin_init', [ $this, 'flush_rewrite_rules' ] );

			add_filter( 'wpseo_helpscout_beacon_settings', [ $this, 'filter_helpscout_beacon' ] );
			add_action( 'admin_init', [ $this, 'maps_api_browser_key_notification' ] );

			$this->asset_manager = new WPSEO_Local_Admin_Assets();
			$this->asset_manager->register_assets();
			$this->api_repository = new Api_Keys_Repository();
			$this->api_repository->initialize();

			add_action( 'admin_notices', [ $this, 'missing_organization_name_logo_notice' ] );
		}

		/**
		 * Register wpeso_local option with Yoast SEO Options framework.
		 *
		 * @return void
		 */
		public function register_custom_option() {
			WPSEO_Options::register_option( WPSEO_Local_Option::get_instance() );
		}

		/**
		 * Makes sure the Local SEO settings page has a HelpScout beacon.
		 *
		 * @param array $helpscout_settings The HelpScout settings.
		 *
		 * @return array The HelpScout settings with the News SEO beacon added.
		 */
		public function filter_helpscout_beacon( $helpscout_settings ) {
			$helpscout_settings['pages_ids']['wpseo_local'] = '84c1df2c-435a-45ac-8708-f5e3a00843c5';
			$helpscout_settings['products'][]               = WPSEO_Addon_Manager::LOCAL_SLUG;

			return $helpscout_settings;
		}

		/**
		 * Registers the wpseo_local setting for Settings API
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		public function options_init() {
			register_setting( 'yoast_wpseo_local_options', 'wpseo_local' );
		}

		/**
		 * Adds local page to admin_page variable of wpseo
		 *
		 * @return void
		 */
		public function register_wpseo() {
			add_filter( 'wpseo_admin_pages', [ $this, 'register_local_page' ] );
		}

		/**
		 * Registers local page
		 *
		 * @param array $pages Array of admin pages.
		 *
		 * @return array
		 */
		public function register_local_page( $pages ) {
			$pages[] = 'wpseo_local';

			return $pages;
		}

		/**
		 * Registers the settings page in the WP SEO menu.
		 *
		 * @since 1.0
		 *
		 * @param array $submenu_pages Array of submenu pages for SEO admin menu item.
		 *
		 * @return array
		 */
		public function register_settings_page( $submenu_pages ) {
			$submenu_pages[] = [
				'wpseo_dashboard',
				'Yoast SEO: Local SEO',
				'Local SEO',
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- YoastSEO Free hook.
				apply_filters( 'wpseo_manage_options_capability', 'wpseo_manage_options' ),
				'wpseo_local',
				[ 'WPSEO_Local_Admin_Page', 'build_page' ],
			];

			return $submenu_pages;
		}

		/**
		 * Set true or false values to see what screen we are on.
		 *
		 * @return void
		 */
		public function set_current_screen() {
			global $pagenow;
			$current_screen = get_current_screen();

			$post_type_instance = new PostType();
			$post_type_instance->initialize();
			$post_type = $post_type_instance->get_post_type();

			$this->is_settings_page                = ( $current_screen->id === 'seo_page_wpseo_local' );
			$this->is_locations_page               = ( $current_screen->base === 'post' && $current_screen->id === $post_type );
			$this->is_locations_category_term_page = ( $current_screen->base === 'term' && $current_screen->id === 'edit-wpseo_locations_category' );
			$this->is_edit_page                    = in_array( $pagenow, [ 'post.php', 'post-new.php' ], true );
			$this->is_yoast_seo_titles_page        = ( $current_screen->id === 'seo_page_wpseo_titles' );
		}

		/**
		 * Loads some CSS
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		public function config_page_styles() {
			if ( $this->is_locations_page || $this->is_settings_page || $this->is_edit_page || $this->is_locations_category_term_page ) {
				$this->asset_manager->enqueue_style( 'admin-css' );
			}
		}

		/**
		 * Enqueues the (tiny) global JS needed for the plugin.
		 *
		 * @return void
		 */
		public function config_page_scripts() {
			if ( $this->is_settings_page || $this->is_locations_page || $this->is_locations_category_term_page ) {
				$this->asset_manager->enqueue_script( 'global-script' );
				$localization_data = [
					'ajaxurl' => 'admin-ajax.php',
				];

				wp_localize_script(
					WPSEO_Local_Admin_Assets::PREFIX . 'global-script',
					'wpseo_local_data',
					$localization_data
				);

				wp_enqueue_media();
			}

			if ( $this->is_locations_page ) {
				$this->asset_manager->enqueue_script( 'location-settings' );
			}

			if ( $this->is_settings_page || $this->is_locations_page ) {
				$api_repository = new Api_Keys_Repository();
				$api_repository->initialize();
				$api_key = $api_repository->get_api_key( 'browser' );

				// Only load the Geocoding scripts if an API key is entered.
				if ( ! empty( $api_key ) ) {
					add_action( 'wp_footer', 'wpseo_enqueue_geocoder' );
					add_action( 'admin_footer', 'wpseo_enqueue_geocoder' );

					$this->asset_manager->enqueue_script( 'google-maps' );
					$calculate_succes_message = self::display_notification( esc_html__( 'The coordinates of the entered business address have been calculated.', 'yoast-local-seo' ), 'success', 'location-calculate-alert', false );
					$calculate_error_message  = self::display_notification( esc_html__( 'The coordinates of the entered business address were not calculated. An error occurred.', 'yoast-local-seo' ), 'warning', 'location-calculate-alert', false );
					$address_changed_message  = self::display_notification( esc_html__( 'We\'ve noticed that you changed your business address. You can recalculate the coordinates of your business\'s location below.', 'yoast-local-seo' ), 'warning', 'location-calculate-alert', false );

					$strings = [
						'apiKey'                        => $api_key,
						'calculateLatLngSuccessMessage' => $calculate_succes_message,
						'calculateLatLngErrorMessage'   => $calculate_error_message,
						'addressChangedMessage'         => $address_changed_message,
					];

					wp_localize_script(
						WPSEO_Local_Admin_Assets::PREFIX . 'locations',
						'wpseoLocalLocations',
						$strings
					);
					$this->asset_manager->enqueue_script( 'locations' );
				}
			}

			if ( $this->is_settings_page ) {
				$this->asset_manager->enqueue_script( 'settings' );
			}
		}

		/**
		 * Print the required JavaScript in the footer
		 *
		 * @return void
		 */
		public function config_page_footer() {
			if ( $this->is_settings_page || $this->is_locations_page ) {
				$this->asset_manager->enqueue_script( 'select2' );
				$this->asset_manager->enqueue_style( 'select2' );
				?>
				<script>
					jQuery( document ).ready( function( $ ) {
						$( "#business_type, #wpseo_business_type" ).select2( {
							placeholder: <?php echo wp_json_encode( __( 'Choose a business type', 'yoast-local-seo' ) ); ?>,
							allowClear: true,
						} );
						$( "#location_timezone, #wpseo_business_timezone" ).select2( {
							placeholder: <?php echo wp_json_encode( __( 'Choose a time zone', 'yoast-local-seo' ) ); ?>,
							allowClear: true,
						} );
						$( "#location_price_range, #wpseo_business_price_range" ).select2( {
							placeholder: <?php echo wp_json_encode( __( 'Select your price indication', 'yoast-local-seo' ) ); ?>,
							allowClear: true,
							minimumResultsForSearch: Infinity,
						} );
						$( "#location_country, #wpseo_business_country, #default_country" ).select2( {
							placeholder: <?php echo wp_json_encode( __( 'Choose a country', 'yoast-local-seo' ) ); ?>,
							allowClear: true,
						} );
						$( "#multiple_locations_primary_location" ).select2( {
							placeholder: <?php echo wp_json_encode( __( 'Select a location', 'yoast-local-seo' ) ); ?>,
							allowClear: true,
						} );
						$( "#address_format" ).select2( {
							allowClear: false,
							minimumResultsForSearch: Infinity,
						} );
						$( "#wpseo_copy_from_location" ).select2( {
							placeholder: <?php echo wp_json_encode( __( 'Select a location', 'yoast-local-seo' ) ); ?>,
							allowClear: true,
						} );
					} );
				</script>
				<?php
			}

			if ( $this->is_yoast_seo_titles_page ) {
				echo '<script>';
				echo 'const personCompanySelect = document.getElementById( "company_or_person" );';
				echo 'personCompanySelect.disabled = true;';
				echo 'personCompanySelect.parentNode.classList.remove( "yoast-styled-select" );';
				echo '</script>';
			}
		}

		/**
		 * Generates the import panel for importing locations via CSV
		 *
		 * @return void
		 */
		public function import_panel() {

			echo '<div id="local-seo-import" class="yoastbox">';
			echo '<h2>' . esc_html__( 'CSV import of locations for Local Search', 'yoast-local-seo' ) . '</h2>';

			echo '</div>';
		}

		/**
		 * Flushes the rewrite rules if multiple locations is turned on or off or the slug is changed.
		 *
		 * @since 1.3.1
		 *
		 * @param mixed $old_option_value Value of the current option.
		 * @param mixed $new_option_value Value of the new, currently saved option.
		 *
		 * @return void
		 */
		public function update_multiple_locations( $old_option_value, $new_option_value ) {
			$old_value_exists = array_key_exists( 'use_multiple_locations', $old_option_value );
			$new_value_exists = array_key_exists( 'use_multiple_locations', $new_option_value );

			$old_option_value['locations_slug'] = isset( $old_option_value['locations_slug'] ) ? esc_attr( $old_option_value['locations_slug'] ) : '';
			$new_option_value['locations_slug'] = isset( $new_option_value['locations_slug'] ) ? esc_attr( $new_option_value['locations_slug'] ) : '';

			$old_option_value['locations_taxo_slug'] = isset( $old_option_value['locations_taxo_slug'] ) ? esc_attr( $old_option_value['locations_taxo_slug'] ) : '';
			$new_option_value['locations_taxo_slug'] = isset( $new_option_value['locations_taxo_slug'] ) ? esc_attr( $new_option_value['locations_taxo_slug'] ) : '';

			if ( ( $old_value_exists === false && $new_value_exists === true ) || ( $old_option_value['locations_slug'] != $new_option_value['locations_slug'] ) || ( $old_option_value['locations_taxo_slug'] != $new_option_value['locations_taxo_slug'] ) ) {
				set_transient( 'wpseo_local_permalinks_settings_changed', true, 60 );
			}
		}

		/**
		 * Flushes the rewrite rules if multiple locations is turned on or off or the slug is changed.
		 *
		 * @since 1.3.1
		 *
		 * @return void
		 */
		public function flush_rewrite_rules() {
			if ( get_transient( 'wpseo_local_permalinks_settings_changed' ) == true ) {
				flush_rewrite_rules();

				delete_transient( 'plugin_settings_have_changed' );
			}
		}

		/**
		 * Registers a notification if the Google Maps API browser key has not yet been set.
		 *
		 * @return void
		 */
		public function maps_api_browser_key_notification() {
			if ( ! class_exists( 'Yoast_Notification_Center' ) ) {
				return;
			}

			$message_text = sprintf(
				/* translators: %1$s expands to Yoast SEO: Local, %2$s expands to Google Maps,%3$s expands to a link open tag to the settings page, %4$s expands to the closing tag for the link(s) to the settings page and %5$s expands to the opening tag for the link to the help center article */
				__( '%1$s needs a %2$s API key to show %2$s on your website. You haven\'t set a %2$s API key yet. Go to the %3$s%1$s API key tab%4$s to set the key, or %5$svisit the Yoast help center%4$s for more information.', 'yoast-local-seo' ),
				'Yoast SEO: Local',
				'Google Maps',
				'<a href="' . admin_url( 'admin.php?page=wpseo_local#top#api_keys' ) . '">',
				'</a>',
				'<a href="https://yoa.st/generate-set-google-maps-browser-key" target="_blank">'
			);

			$message_options = [
				'type' => Yoast_Notification::WARNING,
				'id'   => 'LocalSEOBrowserKey',
			];

			$api_key             = $this->api_repository->get_api_key();
			$notification_center = Yoast_Notification_Center::get();
			$notification        = new Yoast_Notification( $message_text, $message_options );

			if ( empty( $api_key ) ) {
				$notification_center->add_notification( $notification );
			}
			else {
				$notification_center->remove_notification( $notification );
			}
		}

		/**
		 * Checks if the page is a local seo page.
		 *
		 * @param string $page The page that might be a local seo page.
		 *
		 * @return bool
		 */
		private function is_local_seo_page( $page ) {
			$pages = [ 'wpseo_local' ];

			return in_array( $page, $pages, true );
		}

		/**
		 * @param string $message   The notification message to display.
		 * @param string $type      The type of notification to display. Can be warning, success or info.
		 * @param string $css_class Optional. CSS class name(s).
		 * @param bool   $to_echo   Whether to echo or return the notification.
		 *
		 * @return string
		 */
		public static function display_notification( $message, $type = 'warning', $css_class = '', $to_echo = true ) {
			$svg = [
				'warning' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" role="img" aria-hidden="true" focusable="false"><path d="M569.517 440.013C587.975 472.007 564.806 512 527.94 512H48.054c-36.937 0-59.999-40.055-41.577-71.987L246.423 23.985c18.467-32.009 64.72-31.951 83.154 0l239.94 416.028zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z"/></svg>',
				'success' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" role="img" aria-hidden="true" focusable="false"><path d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z"/></svg>',
				'info'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" role="img" aria-hidden="true" focusable="false"><path d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 110c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"/></svg>',
			];

			$notification  = '<div class="yoast-seo-local__alert yoast-seo-local__alert--' . esc_attr( $type ) . ' ' . esc_attr( $css_class ) . ' ">';
			$notification .= '<span class="alert__icon">';
			$notification .= $svg[ $type ];
			$notification .= '</span>';
			$notification .= '<span class="alert__content">';
			$notification .= $message;
			$notification .= '</span>';
			$notification .= '</div>';

			if ( ! $to_echo ) {
				return $notification;
			}

			echo $notification;
		}

		/**
		 * Returns a warning alert regarding the business zipcode or country not being set.
		 *
		 * @return Alert_Presenter The alert.
		 */
		public static function get_missing_zipcode_country_alert() {
			$message = sprintf(
				/* translators: 1: expands to a link opening tag; 2: expands to a link closing tag */
				esc_html__( 'A zipcode and country need to be set for structured data to work properly. %1$sLearn more about the importance of structured data.%2$s', 'yoast-local-seo' ),
				'<a href ="' . esc_url( WPSEO_Shortlinker::get( 'https://yoa.st/4ca' ) ) . '" target="_blank">',
				WPSEO_Admin_Utils::get_new_tab_message() . '</a>'
			);

			return new Alert_Presenter( $message, 'warning' );
		}

		/**
		 * Displays an admin notice when the Organization name or logo aren't set.
		 *
		 * @return void
		 */
		public function missing_organization_name_logo_notice() {
			if ( ! $this->should_display_missing_organization_name_logo_notice() ) {
				return;
			}

			printf(
				'<div class="notice notice-warning"><p>%1$s</p><p>%2$s</p></div>',
				sprintf(
					/* translators: 1: Link start tag to a yoast.com page about structured data, 2: Link closing tag. */
					esc_html__( 'An Organization name and logo need to be set for structured data to work properly. %1$sLearn more about the importance of structured data.%2$s', 'yoast-local-seo' ),
					'<a href="' . esc_url( WPSEO_Shortlinker::get( 'https://yoa.st/4cb' ) ) . '" target="_blank">',
					WPSEO_Admin_Utils::get_new_tab_message() . '</a>'
				),
				sprintf(
					/* translators: 1: Link start tag to the Yoast SEO Search Appearance settings page, 2: Link closing tag. */
					esc_html__( '%1$sSet your Organization name and logo.%2$s', 'yoast-local-seo' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=wpseo_page_settings#/site-representation' ) ) . '" class="button">',
					'</a>'
				)
			);
		}

		/**
		 * Determines whether the "missing organization name and logo" admin notice should be displayed.
		 *
		 * @return bool Whether the "missing organization name and logo" admin notice should be displayed.
		 */
		protected function should_display_missing_organization_name_logo_notice() {
			$relevant_wp_pages = [
				'index.php',
				'plugins.php',
			];

			return (
				$this->is_organization_name_or_logo_missing()
				&& WPSEO_Capability_Utils::current_user_can( 'wpseo_manage_options' )
				&& (
					YoastSEO()->helpers->current_page->is_yoast_seo_page()
					|| in_array( YoastSEO()->helpers->current_page->get_current_admin_page(), $relevant_wp_pages, true )
				)
			);
		}

		/**
		 * Checks whether the Organization name or logo aren't set.
		 *
		 * @return bool Whether the Organization name or logo aren't set.
		 */
		protected function is_organization_name_or_logo_missing() {
			return WPSEO_Options::get( 'company_name', '' ) === ''
				|| WPSEO_Options::get( 'company_logo', '' ) === ''
				|| WPSEO_Options::get( 'company_logo_id', 0 ) === 0;
		}
	}
}
