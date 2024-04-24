<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Main
 */

use Yoast\WP\Local\Repositories\Options_Repository;

if ( ! class_exists( 'WPSEO_Local_Opening_Hours_Repository' ) ) {

	/**
	 * Class WPSEO_Local_Opening_Hours_Repository
	 *
	 * This class handles the querying of all locations
	 */
	class WPSEO_Local_Opening_Hours_Repository {

		/**
		 * Contains array for days with its translations and notations.
		 *
		 * @var LimitIterator
		 */
		protected $days;

		/**
		 * Contains keys for the daily opening hours.
		 *
		 * @var string[]
		 */
		protected $hours_keys = [
			'_from',
			'_to',
			'_second_from',
			'_second_to',
			'_24h',
		];

		/**
		 * @var Options_Repository
		 */
		protected $options;

		/**
		 * Contains the keys for the toggles.
		 *
		 * @var string[]
		 */
		protected $toggle_keys = [
			'multiple_opening_hours',
			'open_247',
			'format_24h',
		];

		/**
		 * WPSEO_Local_Opening_Hours_Repository constructor.
		 *
		 * @param Options_Repository $options Options.
		 */
		public function __construct( Options_Repository $options ) {
			$this->run();
			$this->options = $options;

			$this->options->initialize();
		}

		/**
		 * Runs default actions when instantiating the class.
		 *
		 * @return void
		 */
		public function run() {
			$this->set_days();
		}

		/**
		 * Determines whether the opening hours are empty.
		 *
		 * @param array $opening_hours The opening hours to check.
		 *
		 * @return bool Whether or not there are opening hours defined.
		 */
		public function has_empty_opening_hours( array $opening_hours ) {
			return empty( $opening_hours['_from'] ) && empty( $opening_hours['_to'] );
		}

		/**
		 * Set property Days.
		 *
		 * @return void
		 */
		private function set_days() {
			$day_labels = [
				'sunday'    => __( 'Sunday', 'yoast-local-seo' ),
				'monday'    => __( 'Monday', 'yoast-local-seo' ),
				'tuesday'   => __( 'Tuesday', 'yoast-local-seo' ),
				'wednesday' => __( 'Wednesday', 'yoast-local-seo' ),
				'thursday'  => __( 'Thursday', 'yoast-local-seo' ),
				'friday'    => __( 'Friday', 'yoast-local-seo' ),
				'saturday'  => __( 'Saturday', 'yoast-local-seo' ),
			];

			$days       = new ArrayIterator( $day_labels );
			$days       = new InfiniteIterator( $days );
			$this->days = new LimitIterator( $days, get_option( 'start_of_week' ), 7 );
		}

		/**
		 * Returns an array of days.
		 *
		 * @return array
		 */
		public function get_days() {
			return iterator_to_array( $this->days );
		}

		/**
		 * @todo Passing through the $post_id should be solved in a nicer way,
		 *       since when using a single-location setup, it doesn't need a post ID.
		 *
		 * @param string          $day        Lowercase key of the day (in english).
		 * @param int|string|null $post_id    Use 'option' when using single-location setup.
		 *                                    Use the Post ID (int) when using multiple locations setup.
		 * @param array           $options    Optional options array.
		 * @param bool|null       $format_24h Whether or not 24-hour time format should be used.
		 *
		 * @return array Array of opening hours in all needed formats.
		 */
		public function get_opening_hours( $day, $post_id = null, $options = [], $format_24h = null ) {
			if ( $this->options->use_multiple_locations() ) {
				$opening_hours = $this->get_opening_hours_for_multiple_locations( $day, $post_id, $options );
			}
			else {
				$opening_hours = $this->get_opening_hours_for_single_location( $day, $options );
			}

			// Format opening hours.
			if ( $format_24h !== true ) {
				$opening_hours = $this->format_opening_hours_to_12h( $opening_hours );
			}

			return $opening_hours;
		}

		/**
		 * Gets the opening hours for a specific location, when dealing with multiple locations.
		 *
		 * @param string   $day     The day to get the opening hours for.
		 * @param int|null $post_id The post ID of the location.
		 * @param array    $options The generic options array.
		 *
		 * @return array The opening hours for multiple locations.
		 */
		public function get_opening_hours_for_multiple_locations( $day, $post_id = null, $options = [] ) {
			if ( $post_id === null ) {
				$post_id = get_the_ID();
			}

			// Location and day-specific opening hours.
			$opening_hours = $this->get_opening_hours_for_day_from_meta( $day, $post_id );
			$is_overridden = false;

			if ( $this->options->use_shared_opening_hours() ) {
				$is_overridden = get_post_meta( $post_id, '_wpseo_opening_hours_' . $day . '_override', true );

				$opening_hours = $this->apply_shared_hours_properties( $day, $opening_hours, wpseo_check_falses( $is_overridden ) );
			}

			$use_multiple_times = $this->use_multiple_opening_hours( $post_id );

			if ( wpseo_check_falses( $opening_hours['_24h'] ) || $this->has_empty_opening_hours( $opening_hours ) ) {
				$opening_hours = $this->set_default_hours( $opening_hours );
			}

			return [
				'value'                       => $day,
				'value_abbr'                  => substr( $day, 0, 2 ),
				'value_from'                  => $opening_hours['_from'],
				'value_to'                    => $opening_hours['_to'],
				'value_from_formatted'        => $opening_hours['_from'],
				'value_to_formatted'          => $opening_hours['_to'],
				'value_second_from'           => $opening_hours['_second_from'],
				'value_second_to'             => $opening_hours['_second_to'],
				'value_second_to_formatted'   => $opening_hours['_second_to'],
				'value_second_from_formatted' => $opening_hours['_second_from'],
				'open_24h'                    => $opening_hours['_24h'],
				'is_overridden'               => $is_overridden,
				'use_multiple_times'          => $use_multiple_times,
			];
		}

		/**
		 * Gets the opening hours for a single location.
		 *
		 * @param string $day     The day to get the opening hours for.
		 * @param array  $options The generic options array.
		 *
		 * @return array The opening hours for multiple locations.
		 */
		public function get_opening_hours_for_single_location( $day, $options = [] ) {
			$opening_hours = $this->get_opening_hours_for_day( $day, $options );

			if ( $this->has_empty_opening_hours( $opening_hours ) || wpseo_check_falses( $opening_hours['_24h'] ) ) {
				$opening_hours = $this->set_default_hours( $opening_hours );
			}

			return [
				'value'                       => $day,
				'value_abbr'                  => substr( $day, 0, 2 ),
				'value_from'                  => $opening_hours['_from'],
				'value_to'                    => $opening_hours['_to'],
				'value_from_formatted'        => $opening_hours['_from'],
				'value_to_formatted'          => $opening_hours['_to'],
				'value_second_from'           => $opening_hours['_second_from'],
				'value_second_to'             => $opening_hours['_second_to'],
				'value_second_to_formatted'   => $opening_hours['_second_to'],
				'value_second_from_formatted' => $opening_hours['_second_from'],
				'open_24h'                    => $opening_hours['_24h'],
				'is_overridden'               => false,
				'use_multiple_times'          => $this->options->get( 'multiple_opening_hours' ) === 'on',
			];
		}

		/**
		 * Formats the passed opening hours data to ensure they contain a 12 hour formatted version.
		 *
		 * @param array $opening_hours The opening hours data to format.
		 *
		 * @return array The opening hours, including the formatted opening hours.
		 */
		public function format_opening_hours_to_12h( $opening_hours ) {
			$formatted = [];

			foreach ( $opening_hours as $key => $time ) {
				if ( ! in_array( $key, [ 'value_from', 'value_to', 'value_second_from', 'value_second_to' ], true ) ) {
					continue;
				}

				$formatted[ $key . '_formatted' ] = gmdate( 'g:i A', strtotime( $time ) );
			}

			return array_merge( $opening_hours, $formatted );
		}

		/**
		 * Sets the default opening hours.
		 *
		 * @param array $opening_hours The opening hours data to change.
		 *
		 * @return array The default opening hours.
		 */
		public function set_default_hours( $opening_hours ) {
			return array_merge(
				$opening_hours,
				[
					'_from'        => '09:00',
					'_to'          => '17:00',
					'_second_from' => false,
					'_second_to'   => false,
				]
			);
		}

		/**
		 * Gets the opening hours for the specified day, from the stored post meta.
		 *
		 * @param string $day     The day to get the metadata for.
		 * @param int    $post_id The post to get the metadata for.
		 *
		 * @return array The retrieved opening hours data.
		 */
		public function get_opening_hours_for_day_from_meta( $day, $post_id ) {
			$field_name = '_wpseo_opening_hours_' . $day;
			$result     = [];

			foreach ( $this->hours_keys as $key ) {
				$result[ $key ] = get_post_meta( $post_id, $field_name . $key, true );
			}

			return $result;
		}

		/**
		 * Gets the opening hours data for the specified day.
		 *
		 * @param string $day     The day to get the options for.
		 * @param array  $options The options to retrieve the options from.
		 *
		 * @return array The opening hours data for the passed day.
		 */
		public function get_opening_hours_for_day( $day, $options ) {
			return $this->get_opening_hours_from_options_for_field( 'opening_hours_' . $day, $options );
		}

		/**
		 * Gets the opening toggle from the stored post meta.
		 *
		 * @param int|null $post_id The post to get the metadata for.
		 *
		 * @return array The retrieved opening hour toggles.
		 */
		public function get_opening_toggles_from_meta( $post_id = null ) {
			if ( $post_id === null ) {
				$post_id = get_the_ID();
			}

			$result = [];

			foreach ( $this->toggle_keys as $key ) {
				$result[ $key ] = get_post_meta( $post_id, '_wpseo_' . $key, true );
			}

			return $result;
		}

		/**
		 * Gets the opening hour toggle values based on the passed location ID and whether shared opening hours are used.
		 *
		 * @param int $location_id The location ID to retrieve the meta data for.
		 *
		 * @return array The toggle values.
		 */
		public function get_opening_hours_toggle_values( $location_id ) {
			// Get from meta by default.
			$opening_hours_toggles = $this->get_opening_toggles_from_meta( $location_id );

			if ( $this->options->use_shared_opening_hours() ) {
				$opening_hours_toggles = $this->apply_shared_toggle_properties( $location_id, $opening_hours_toggles );
			}

			return $opening_hours_toggles;
		}

		/**
		 * Gets the opening hours data based on the passed field.
		 *
		 * @param string $field   The field to retrieve.
		 * @param array  $options The options to retrieve the field from.
		 *
		 * @return array The opening hours data.
		 */
		protected function get_opening_hours_from_options_for_field( $field, $options ) {
			$result = [];

			foreach ( $this->hours_keys as $key ) {
				$result[ $key ] = isset( $options[ $field . $key ] ) ? esc_attr( $options[ $field . $key ] ) : '';
			}

			return $result;
		}

		/**
		 * Applies the shared properties to the opening hours, if the current day isn't overridden.
		 *
		 * @param string $day           The day.
		 * @param array  $opening_hours The current opening hours data.
		 * @param false  $is_overridden Whether or not the overridden meta is set for this day.
		 *
		 * @return array The opening hours with the applied, shared properties.
		 */
		protected function apply_shared_hours_properties( $day, $opening_hours, $is_overridden = false ) {
			if ( $is_overridden ) {
				return $opening_hours;
			}

			// Loop through opening hours and remove empty ones.
			$opening_hours = array_filter(
				$opening_hours,
				static function ( $value ) {
					return ! empty( $value );
				}
			);

			return array_merge( $opening_hours, $this->get_shared_opening_hours_for_day( $day ) );
		}

		/**
		 * Applies the shared properties to the toggles.
		 *
		 * @param int   $location_id       The location ID to get the override meta value for.
		 * @param array $toggle_properties The current opening hour toggles properties.
		 *
		 * @return array The opening hour toggles with the applied, shared properties.
		 */
		protected function apply_shared_toggle_properties( $location_id, $toggle_properties ) {
			$shared = $this->get_shared_opening_toggles();

			foreach ( $toggle_properties as $key => $value ) {
				$is_overridden = get_post_meta( $location_id, '_wpseo_' . $key . '_override', true );

				if ( ! $is_overridden && ( empty( $value ) || $value === 'off' ) ) {
					$toggle_properties[ $key ] = $shared[ $key ];
					continue;
				}

				if ( $is_overridden && empty( $value ) ) {
					$toggle_properties[ $key ] = 'off';
					continue;
				}

				$toggle_properties[ $key ] = $value;
			}

			return $toggle_properties;
		}

		/**
		 * Gets the shared opening hours information for the passed day.
		 *
		 * @param string $day The day to get the opening hours for.
		 *
		 * @return array The shared opening hours.
		 */
		protected function get_shared_opening_hours_for_day( $day ) {
			$shared_opening_hours = [];

			foreach ( $this->hours_keys as $key ) {
				$shared_opening_hours[ $key ] = $this->options->get( 'opening_hours_' . $day . $key );
			}

			return $shared_opening_hours;
		}

		/**
		 * Gets the shared opening hour toggles' options.
		 *
		 * @return array The shared opening toggles' options.
		 */
		protected function get_shared_opening_toggles() {
			$shared_opening_hour_toggles = [];

			foreach ( $this->toggle_keys as $key ) {
				// Because the option name isn't the same as the meta field, we need to rename this key.
				$option = ( $key === 'format_24h' ) ? 'opening_hours_24h' : $key;

				$shared_opening_hour_toggles[ $key ] = $this->options->get( $option );
			}

			return $shared_opening_hour_toggles;
		}

		/**
		 * Determines whether multiple opening hours should be used for the passed location.
		 *
		 * @param int $location_id The location's ID.
		 *
		 * @return bool Whether to use multiple opening hours.
		 */
		protected function use_multiple_opening_hours( $location_id ) {
			// Check for multiple opening hours.
			$use_multiple_times_meta     = get_post_meta( $location_id, '_wpseo_multiple_opening_hours', true );
			$use_multiple_times_override = get_post_meta( $location_id, '_wpseo_multiple_opening_hours', true ) === 'on';

			if ( ! $this->options->use_shared_opening_hours() ) {
				return $use_multiple_times_meta;
			}

			if ( ! $use_multiple_times_override ) {
				return $this->options->get( 'multiple_opening_hours' ) === 'on';
			}

			return $use_multiple_times_meta === 'on';
		}
	}
}
