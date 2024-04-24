<?php
/**
 * @package WPSEO_Local\Frontend\Schema
 */

use Yoast\WP\Local\Repositories\Options_Repository;

/**
 * Class WPSEO_Local_Opening_Hours.
 *
 * Manages the Schema output for opening hours.
 *
 * @property array $options     Local SEO options.
 * @property int   $location_id A variable containing the location ID.
 */
class WPSEO_Local_Opening_Hours {

	/**
	 * Stores the options for this plugin.
	 *
	 * @var array
	 */
	public $options = [];

	/**
	 * Stores the location ID.
	 *
	 * @var int
	 */
	public $location_id = 0;

	/**
	 * Constructor.
	 *
	 * @param int $location_id A variable containing the location ID.
	 */
	public function __construct( $location_id = 0 ) {
		$this->options = get_option( 'wpseo_local' );

		$this->location_id = $location_id;
	}

	/**
	 * Calculates the opening hours schema for a location.
	 *
	 * @link https://developers.google.com/search/docs/data-types/local-business
	 * @link https://schema.org/OpeningHoursSpecification
	 *
	 * @return array Array with openingHoursSpecification data.
	 */
	public function generate_opening_hours() {
		if ( $this->should_generate_opening_hours() ) {
			// Force all days to show 24h opening times.
			if ( $this->is_open_247() ) {
				return $this->opening_hours_247();
			}

			return $this->specific_opening_hours();
		}

		return [];
	}

	/**
	 * Function to determine whether opening hours should be generated.
	 *
	 * @return bool Value that indicates whether or not to generate opening hours.
	 */
	private function should_generate_opening_hours() {
		return ( ! isset( $this->options['hide_opening_hours'] ) || ( isset( $this->options['hide_opening_hours'] ) && $this->options['hide_opening_hours'] !== 'on' ) );
	}

	/**
	 * Function to determine whether a location is open 24/7 or not.
	 *
	 * @return bool False when location is not open 24/7, true when it is.
	 */
	private function is_open_247() {
		$is_overridden = get_post_meta( $this->location_id, '_wpseo_open_247_override', true ) === 'on';
		if ( wpseo_has_multiple_locations() && $this->location_id && $is_overridden ) {
			$open_247 = get_post_meta( $this->location_id, '_wpseo_open_247', true );

			return ( $open_247 === 'on' );
		}

		$open_247 = ( $this->options['open_247'] ?? '' );

		return ( $open_247 === 'on' );
	}

	/**
	 * Returns 24/7 opening hours Schema.
	 *
	 * @return array Array with openingHoursSpecification data.
	 */
	private function opening_hours_247() {
		return [
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ],
			'opens'     => '00:00',
			'closes'    => '23:59',
		];
	}

	/**
	 * Returns 24/7 opening hours Schema.
	 *
	 * @return array Array with openingHoursSpecification data.
	 */
	private function specific_opening_hours() {
		$output                 = [];
		$opening_hours_repo     = new WPSEO_Local_Opening_Hours_Repository( new Options_Repository() );
		$days                   = $opening_hours_repo->get_days();
		$location_opening_hours = [];

		foreach ( $days as $key => $day ) {
			$opening_hours = $opening_hours_repo->get_opening_hours( $key, ( ! empty( $this->location_id ) ? $this->location_id : 'options' ), $this->options, true );

			$opens  = $opening_hours['value_from'];
			$closes = 'closed';

			if ( $opens !== 'closed' ) {
				$closes = ( ( $opening_hours['value_second_to'] !== 'closed' && $opening_hours['use_multiple_times'] === true ) ? $opening_hours['value_second_to'] : $opening_hours['value_to'] );
			}

			if ( $opening_hours['open_24h'] === 'on' ) {
				$location_opening_hours['open_24h']['days'][] = $this->get_day_of_week( $opening_hours['value_abbr'] );
			}

			if ( isset( $location_opening_hours[ $opens . $closes ] ) && $opening_hours['open_24h'] !== 'on' ) {
				$location_opening_hours[ $opens . $closes ]['days'][] = $this->get_day_of_week( $opening_hours['value_abbr'] );
			}

			if ( ! isset( $location_opening_hours[ $opens . $closes ] ) && $opening_hours['open_24h'] !== 'on' ) {
				$location_opening_hours[ $opens . $closes ] = [
					'opens'  => $opens,
					'closes' => $closes,
					'days'   => [
						$this->get_day_of_week( $opening_hours['value_abbr'] ),
					],
				];
			}
		}

		foreach ( $location_opening_hours as $key => $value ) {
			$day = [
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => $value['days'],
			];
			if ( isset( $value['opens'] ) && $value['opens'] == 'closed' ) {
				$day['opens']  = '00:00';
				$day['closes'] = '00:00';
			}
			elseif ( $key === 'open_24h' ) {
				$day['opens']  = '00:00';
				$day['closes'] = '23:59';
			}
			else {
				$day['opens']  = $value['opens'];
				$day['closes'] = $value['closes'];
			}

			$output[] = $day;
		}

		return $output;
	}

	/**
	 * Returns long day name based on our shortened days of week.
	 *
	 * @param string $day_abbr Day of week in short notation.
	 *
	 * @return string Day of week.
	 */
	private function get_day_of_week( $day_abbr ) {
		$day_abbr = strtolower( $day_abbr );

		switch ( $day_abbr ) {
			case 'mo':
				return 'Monday';
			case 'tu':
				return 'Tuesday';
			case 'we':
				return 'Wednesday';
			case 'th':
				return 'Thursday';
			case 'fr':
				return 'Friday';
			case 'sa':
				return 'Saturday';
			case 'su':
			default:
				return 'Sunday';
		}
	}
}
