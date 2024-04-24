<?php

namespace Yoast\WP\Local\Repositories;

use Yoast\WP\Local\Conditionals\No_Conditionals;
use Yoast\WP\SEO\Initializers\Initializer_Interface;

if ( ! \class_exists( Options_Repository::class ) ) {

	/**
	 * WPSEO_Local_Options_Repository class. Handles all basic needs for the options.
	 *
	 * @since 13.9
	 */
	class Options_Repository implements Initializer_Interface {

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
		 * Initialize Options_Repository.
		 *
		 * @return void
		 */
		public function initialize() {
			$this->options = \get_option( 'wpseo_local' );
		}

		/**
		 * Determines whether multiple locations are used.
		 *
		 * @return bool Whether or not multiple locations are used.
		 */
		public function use_multiple_locations() {
			return isset( $this->options['use_multiple_locations'] )
				&& $this->options['use_multiple_locations'] === 'on';
		}

		/**
		 * Determines whether a single organization is being used.
		 *
		 * @return bool Whether or not it's a single organization.
		 */
		public function is_one_organization() {
			if ( ! $this->use_multiple_locations() ) {
				return false;
			}

			return isset( $this->options['multiple_locations_same_organization'] )
				&& $this->options['multiple_locations_same_organization'] === 'on';
		}

		/**
		 * Determines whether shared business information should be used.
		 *
		 * @return bool Whether shared business information should be used.
		 */
		public function use_shared_business_information() {
			// As we already check for multiple locations when checking the organization, this is sufficient.
			if ( ! $this->is_one_organization() ) {
				return false;
			}

			return isset( $this->options['multiple_locations_shared_business_info'] )
				&& $this->options['multiple_locations_shared_business_info'] === 'on';
		}

		/**
		 * Determines whether shared opening hours should be used.
		 *
		 * @return bool Whether shared opening hours should be used.
		 */
		public function use_shared_opening_hours() {
			// As we already check for multiple locations when checking the organization, this is sufficient.
			if ( ! $this->is_one_organization() ) {
				return false;
			}

			return isset( $this->options['multiple_locations_shared_opening_hours'] )
				&& $this->options['multiple_locations_shared_opening_hours'] === 'on';
		}

		/**
		 * Gets an option based on the passed name.
		 *
		 * @param string $option        The option name.
		 * @param string $default_value The default value to return if the option doesn't exist.
		 *
		 * @return mixed The option or default value if none could be found.
		 */
		public function get( $option, $default_value = '' ) {
			return ( $this->options[ $option ] ?? $default_value );
		}

		/**
		 * Determines whether a primary location is set.
		 *
		 * @return bool Whether a primary location is set.
		 */
		public function has_primary_location() {
			return $this->is_one_organization() && ! empty( $this->options['multiple_locations_primary_location'] );
		}

		/**
		 * Gets the primary location if one exists.
		 *
		 * @return mixed The primary location if it exists. Empty string otherwise.
		 */
		public function get_primary_location() {
			if ( ! $this->has_primary_location() ) {
				return '';
			}

			return $this->options['multiple_locations_primary_location'];
		}
	}
}
