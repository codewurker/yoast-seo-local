<?php

namespace Yoast\WP\Local\Repositories;

use Yoast\WP\Local\Conditionals\No_Conditionals;
use Yoast\WP\SEO\Initializers\Initializer_Interface;

if ( ! \class_exists( Api_Keys_Repository::class ) ) {

	/**
	 * WPSEO_Local_Api_Keys class. Handles all basic needs for the api keys needed for the Google Maps.
	 */
	class Api_Keys_Repository implements Initializer_Interface {

		/**
		 * This trait is always required.
		 */
		use No_Conditionals;

		/**
		 * Stores the options for this plugin.
		 *
		 * @var mixed
		 */
		private $options;

		/**
		 * Initialize Api_Keys_Repository.
		 *
		 * @return void
		 */
		public function initialize() {
			$this->options = \get_option( 'wpseo_local' );
		}

		/**
		 * Returns the right API key when needed.
		 *
		 * @param string|null $type Optional. Either 'server' or 'browser'.
		 *
		 * @return mixed|string|void
		 */
		public function get_api_key( $type = null ) {
			$api_key = '';

			if ( isset( $this->options['googlemaps_api_key'] ) ) {
				$api_key = $this->options['googlemaps_api_key'];
			}

			if ( \defined( 'WPSEO_LOCAL_GOOGLEMAPS_API_KEY' ) ) {
				$api_key = \WPSEO_LOCAL_GOOGLEMAPS_API_KEY;
			}

			if ( empty( $api_key ) && ! empty( $type ) ) {
				if ( $type === 'server' ) {
					$api_key = $this->get_api_key_server();
				}

				if ( $type === 'browser' ) {
					$api_key = $this->get_api_key_browser();
				}
			}

			return $api_key;
		}

		/**
		 * Gets the api server key if it is set or if its set in a constant
		 *
		 * @return string|void
		 */
		public function get_api_key_server() {
			$api_key_server = '';

			if ( isset( $this->options['api_key'] ) ) {
				$api_key_server = $this->options['api_key'];
			}

			if ( \defined( 'WPSEO_LOCAL_API_KEY_SERVER' ) ) {
				$api_key_server = \WPSEO_LOCAL_API_KEY_SERVER;
			}

			return \esc_attr( $api_key_server );
		}

		/**
		 * Gets the api browser key if it is set or if its set in a constant
		 *
		 * @return string|void
		 */
		public function get_api_key_browser() {
			$api_key_browser = '';

			if ( isset( $this->options['local_api_key_browser'] ) ) {
				$api_key_browser = $this->options['local_api_key_browser'];
			}

			if ( \defined( 'WPSEO_LOCAL_API_KEY_BROWSER' ) ) {
				$api_key_browser = \WPSEO_LOCAL_API_KEY_BROWSER;
			}

			return \esc_attr( $api_key_browser );
		}
	}
}
