<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Admin\
 * @since   4.1
 * @todo    CHECK THE @SINCE VERSION NUMBER!!!!!!!!
 */

use Yoast\WP\Local\Repositories\Api_Keys_Repository;
use Yoast\WP\Local\Repositories\Timezone_Repository;

if ( ! defined( 'WPSEO_LOCAL_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! class_exists( 'WPSEO_Local_Admin_Map_Settings' ) ) {

	/**
	 * WPSEO_Local_Admin_Map_Settings class.
	 *
	 * Build the WPSEO Local admin form.
	 *
	 * @since   4.0
	 */
	class WPSEO_Local_Admin_Map_Settings {

		/**
		 * Holds the slug for this settings tab.
		 *
		 * @var string
		 */
		private $slug = 'maps_settings';

		/**
		 * Holds WPSEO Local Core instance.
		 *
		 * @var mixed
		 */
		private $wpseo_local_core;

		/**
		 * Holds the API keys repository.
		 *
		 * @var Api_Keys_Repository
		 */
		private $api_repository;

		/**
		 * Holds the Timezone repository.
		 *
		 * @var Timezone_Repository
		 */
		private $wpseo_local_timezone_repository;

		/**
		 * WPSEO_Local_Admin_Map_Settings constructor.
		 */
		public function __construct() {
			$this->get_core();
			$this->get_timezone_repository();
			$this->api_repository = new Api_Keys_Repository();
			$this->api_repository->initialize();

			add_filter( 'wpseo_local_admin_tabs', [ $this, 'create_tab' ] );
			add_filter( 'wpseo_local_admin_help_center_video', [ $this, 'set_video' ] );

			add_action( 'wpseo_local_admin_' . $this->slug . '_content', [ $this, 'maps_settings' ], 10 );
			add_action( 'wpseo_local_admin_' . $this->slug . '_content', [ $this, 'store_locator' ], 10 );
		}

		/**
		 * Set WPSEO Local Core instance in local property
		 *
		 * @return void
		 */
		private function get_core() {
			global $wpseo_local_core;
			$this->wpseo_local_core = $wpseo_local_core;
		}

		/**
		 * Set WPSEO Local Core Timezone Repository in local property
		 *
		 * @return void
		 */
		private function get_timezone_repository() {
			$timezone_repository = new Timezone_Repository();
			$timezone_repository->initialize();
			$this->wpseo_local_timezone_repository = $timezone_repository;
		}

		/**
		 * @param array $tabs Array holding the tabs.
		 *
		 * @return mixed
		 */
		public function create_tab( $tabs ) {
			$tabs[ $this->slug ] = [
				'tab_title'     => __( 'Maps settings', 'yoast-local-seo' ),
				'content_title' => __( 'Maps settings', 'yoast-local-seo' ),
			];

			return $tabs;
		}

		/**
		 * @param array $videos Array holding the videos for the help center.
		 *
		 * @return mixed
		 */
		public function set_video( $videos ) {
			$videos[ $this->slug ] = 'https://yoa.st/screencast-local-settings-api-keys';

			return $videos;
		}

		/**
		 * Advanced settings section.
		 *
		 * @return void
		 */
		public function maps_settings() {
			$api_key_browser = $this->api_repository->get_api_key( 'browser' );
			$api_key         = $this->api_repository->get_api_key();

			if ( ( empty( $api_key ) && empty( $api_key_browser ) ) || empty( $api_key_browser ) ) {
				echo '<p>' . esc_html__( 'In order to use the Maps settings, you should set an API key. You can add an API key in the API settings tab, which allows you to change the Maps settings here.', 'yoast-local-seo' ) . '</p>';
			}

			if ( ! empty( $api_key_browser ) || ! empty( $api_key ) ) {
				$select_options = [
					'METRIC'   => __( 'Kilometers', 'yoast-local-seo' ),
					'IMPERIAL' => __( 'Miles', 'yoast-local-seo' ),
				];
				WPSEO_Local_Admin_Wrappers::select(
					'unit_system',
					__( 'Measurement system', 'yoast-local-seo' ),
					$select_options
				);

				$select_options = [
					'HYBRID'    => __( 'Hybrid', 'yoast-local-seo' ),
					'SATELLITE' => __( 'Satellite', 'yoast-local-seo' ),
					'ROADMAP'   => __( 'Roadmap', 'yoast-local-seo' ),
					'TERRAIN'   => __( 'Terrain', 'yoast-local-seo' ),
				];
				WPSEO_Local_Admin_Wrappers::select(
					'map_view_style',
					__( 'Default map style', 'yoast-local-seo' ),
					$select_options
				);

				WPSEO_Local_Admin_Page::section_before( 'wpseo-local-custom-marker', null, 'wpseo-local-custom_marker-wrapper' );
				echo '<label class="textinput" for="local_custom_marker">' . esc_html__( 'Custom marker', 'yoast-local-seo' ) . '</label>';
				WPSEO_Local_Admin_Wrappers::hidden( 'local_custom_marker' );

				$custom_marker = WPSEO_Options::get( 'local_custom_marker' );
				$show_marker   = ! empty( $custom_marker );
				echo '<div class="wpseo-local-custom_marker-wrapper__content">';
				echo '<img src="' . ( isset( $custom_marker ) ? esc_url( wp_get_attachment_url( $custom_marker ) ) : '' ) . '" id="local_custom_marker_image_container" class="wpseo-local-hide-button' . ( ( $show_marker === false ) ? ' hidden' : '' ) . '">';
				echo '<br class="wpseo-local-hide-button' . ( ( $show_marker === false ) ? ' hidden' : '' ) . '">';
				echo '<div class="wpseo-local-custom_marker-wrapper__buttons">';
				echo '<button type="button" class="set_custom_images button" data-id="local_custom_marker">' . esc_html__( 'Set custom marker image', 'yoast-local-seo' ) . '</button>';
				echo '<br>';
				echo '<a href="javascript:" class="remove_custom_image wpseo-local-hide-button' . ( ( $show_marker === false ) ? ' hidden' : '' ) . '" data-id="local_custom_marker">' . esc_html__( 'Remove marker', 'yoast-local-seo' ) . '</a>';
				echo '</div>';
				echo '</div>';
				if ( $show_marker === true ) {
					$this->wpseo_local_core->check_custom_marker_size( $custom_marker );
				}
				else {
					echo '<p>' . esc_html__( 'The custom marker should be 100x100px. If the image exceeds those dimensions it could (partially) cover the info popup.', 'yoast-local-seo' ) . '</p>';
				}

				WPSEO_Local_Admin_Page::section_after(); // End wpseo-local-custom-marker section.
			}
		}

		/**
		 * Store locator settings section.
		 *
		 * @return void
		 */
		public function store_locator() {
			$api_key_browser = $this->api_repository->get_api_key( 'browser' );
			$api_key         = $this->api_repository->get_api_key( 'server' );

			if ( ! empty( $api_key_browser ) || ! empty( $api_key ) ) {
				WPSEO_Local_Admin_Page::section_before( 'sl-settings', 'clear: both; ' . ( wpseo_has_multiple_locations() ? '' : 'display: none;' ) );
				echo '<h3>' . esc_html__( 'Store locator settings', 'yoast-local-seo' ) . '</h3>';
				WPSEO_Local_Admin_Wrappers::textinput( 'sl_num_results', __( 'Number of results', 'yoast-local-seo' ) );

				// Chosen allows us to clear a set option (to pass no value), but to do that it requires an empty option.
				$countries = ( [ '' => '' ] + WPSEO_Local_Frontend::get_country_array() );

				WPSEO_Local_Admin_Wrappers::select( 'default_country', __( 'Primary country', 'yoast-local-seo' ), $countries );

				echo '<p style="border:none;" class="label desc">' . esc_html__( 'From which country does your business mainly operate? This will improve the accuracy of the store locator.', 'yoast-local-seo' ) . '</p>';
				WPSEO_Local_Admin_Wrappers::textinput( 'show_route_label', __( '"Show route" label', 'yoast-local-seo' ), '', [ 'placeholder' => __( 'Show route', 'yoast-local-seo' ) ] );

				if ( is_ssl() === true ) {
					Yoast_Form::get_instance()->light_switch(
						'detect_location',
						__( 'Location detection', 'yoast-local-seo' ),
						[
							__( 'Off', 'yoast-local-seo' ),
							__( 'On', 'yoast-local-seo' ),
						]
					);

					echo '<p>' . esc_html__( 'Automatically detect the user\'s location as the starting point.', 'yoast-local-seo' ) . '</p>';
				}
				else {
					echo '<label class="checkbox" for="detect_location">Location detection: (disabled)</label>';
					echo '<p style="border:none; margin-bottom: 0;"><em>';
					printf(
					/* translators: 1: link open tag; 2: link close tag. */
						esc_html__( 'This option only works on HTTPS websites. Using HTTPS is important, read more about it in the %1$sYoast KB%2$s', 'yoast-local-seo' ),
						'<a href="https://yoa.st/local-seo-https">',
						'</a>'
					);
					echo '</em></p>';
				}
				WPSEO_Local_Admin_Page::section_after(); // End Store Locator settings.
			}
		}
	}
}
