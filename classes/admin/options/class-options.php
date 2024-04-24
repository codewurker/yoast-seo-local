<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Admin\Options
 * @since   12.3
 */

/**
 * Overall option management class for Yoast SEO: Local.
 *
 * Instantiates all the options and offers a number of utility methods to work with the options.
 */
class WPSEO_Local_Option extends WPSEO_Option {

	/**
	 * @var string The option name used in the Yoast SEO: Local plugin.
	 */
	public $option_name = 'wpseo_local';

	/**
	 * @var array The default values for our settings.
	 */
	protected $defaults = [];

	/**
	 * Class constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_defaults();
	}

	/**
	 * Get the singleton instance of WPSEO_Local_Option.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set Yoast SEO: Local default option values.
	 *
	 * @return void
	 */
	private function set_defaults() {
		$this->defaults = [
			'use_multiple_locations'                     => 'off',
			'multiple_locations_same_organization'       => 'off',
			'multiple_locations_primary_location'        => '',
			'multiple_locations_shared_business_info'    => 'off',
			'multiple_locations_shared_opening_hours'    => 'off',
			'locations_taxo_slug'                        => '',
			'business_type'                              => '',
			'location_address'                           => '',
			'location_address_2'                         => '',
			'location_city'                              => '',
			'location_state'                             => '',
			'location_zipcode'                           => '',
			'location_country'                           => '',
			'global_location_number'                     => '',
			'location_phone'                             => '',
			'location_phone_2nd'                         => '',
			'location_fax'                               => '',
			'location_email'                             => '',
			'location_contact_email'                     => '',
			'location_contact_phone'                     => '',
			'location_url'                               => ( class_exists( 'WPSEO_Sitemaps_Router' ) ? WPSEO_Sitemaps_Router::get_base_url( '' ) : '' ),
			'location_vat_id'                            => '',
			'location_tax_id'                            => '',
			'location_coc_id'                            => '',
			'location_price_range'                       => '',
			'location_currencies_accepted'               => '',
			'location_payment_accepted'                  => '',
			'location_area_served'                       => '',
			'location_coords_lat'                        => '',
			'location_coords_long'                       => '',
			'locations_slug'                             => 'locations',
			'locations_label_singular'                   => __( 'Location', 'yoast-local-seo' ),
			'locations_label_plural'                     => __( 'Locations', 'yoast-local-seo' ),
			'sl_num_results'                             => 10,
			'closed_label'                               => '',
			'hide_opening_hours'                         => 'off',
			'open_247'                                   => 'off',
			'multiple_opening_hours'                     => 'off',
			'open_247_label'                             => '',
			'open_24h_label'                             => '',
			'opening_hours_24h'                          => 'off',
			'opening_hours_monday_from'                  => '09:00',
			'opening_hours_monday_to'                    => '17:00',
			'opening_hours_monday_second_from'           => '09:00',
			'opening_hours_monday_second_to'             => '17:00',
			'opening_hours_tuesday_from'                 => '09:00',
			'opening_hours_tuesday_to'                   => '17:00',
			'opening_hours_tuesday_second_from'          => '09:00',
			'opening_hours_tuesday_second_to'            => '17:00',
			'opening_hours_wednesday_from'               => '09:00',
			'opening_hours_wednesday_to'                 => '17:00',
			'opening_hours_wednesday_second_from'        => '09:00',
			'opening_hours_wednesday_second_to'          => '17:00',
			'opening_hours_thursday_from'                => '09:00',
			'opening_hours_thursday_to'                  => '17:00',
			'opening_hours_thursday_second_from'         => '09:00',
			'opening_hours_thursday_second_to'           => '17:00',
			'opening_hours_friday_from'                  => '09:00',
			'opening_hours_friday_to'                    => '17:00',
			'opening_hours_friday_second_from'           => '09:00',
			'opening_hours_friday_second_to'             => '17:00',
			'opening_hours_saturday_from'                => '09:00',
			'opening_hours_saturday_to'                  => '17:00',
			'opening_hours_saturday_second_from'         => '09:00',
			'opening_hours_saturday_second_to'           => '17:00',
			'opening_hours_sunday_from'                  => '09:00',
			'opening_hours_sunday_to'                    => '17:00',
			'opening_hours_sunday_second_from'           => '09:00',
			'opening_hours_sunday_second_to'             => '17:00',
			'opening_hours_monday_24h'                   => 'off',
			'opening_hours_tuesday_24h'                  => 'off',
			'opening_hours_wednesday_24h'                => 'off',
			'opening_hours_thursday_24h'                 => 'off',
			'opening_hours_friday_24h'                   => 'off',
			'opening_hours_saturday_24h'                 => 'off',
			'opening_hours_sunday_24h'                   => 'off',
			'location_timezone'                          => '',
			'unit_system'                                => 'METRIC',
			'map_view_style'                             => 'HYBRID',
			'address_format'                             => 'address-state-postal',
			'default_country'                            => '',
			'show_route_label'                           => '',
			'detect_location'                            => 'off',
			'local_custom_marker'                        => '',
			'local_api_key_browser'                      => '',
			'local_api_key'                              => '',
			'googlemaps_api_key'                         => '',
			'local_enhanced_search'                      => 'no',
			'local_version'                              => '0',
			'woocommerce_local_pickup_setting'           => 'no',
			'dismiss_local_pickup_notice'                => false,
		];
	}

	/**
	 * Validate and sanitize the updated setting values before saving them.
	 *
	 * @param array $dirty New value for the option.
	 * @param array $clean Clean value for the option, normally the defaults.
	 * @param array $old   Old value of the option.
	 *
	 * @return array Sanitized setting values.
	 */
	protected function validate_option( $dirty, $clean, $old ) {
		foreach ( $clean as $key => $value ) {
			switch ( $key ) {
				/* Text fields. */
				case 'business_type':
				case 'location_address':
				case 'location_address_2':
				case 'location_city':
				case 'location_state':
				case 'location_zipcode':
				case 'location_country':
				case 'global_location_number':
				case 'location_phone':
				case 'location_phone_2nd':
				case 'location_fax':
				case 'location_contact_phone':
				case 'location_vat_id':
				case 'location_tax_id':
				case 'location_coc_id':
				case 'location_price_range':
				case 'location_currencies_accepted':
				case 'location_payment_accepted':
				case 'location_area_served':
				case 'locations_slug':
				case 'locations_taxo_slug':
				case 'locations_label_singular':
				case 'locations_label_plural':
				case 'closed_label':
				case 'open_247_label':
				case 'open_24h_label':
				case 'opening_hours_monday_from':
				case 'opening_hours_monday_to':
				case 'opening_hours_monday_second_from':
				case 'opening_hours_monday_second_to':
				case 'opening_hours_tuesday_from':
				case 'opening_hours_tuesday_to':
				case 'opening_hours_tuesday_second_from':
				case 'opening_hours_tuesday_second_to':
				case 'opening_hours_wednesday_from':
				case 'opening_hours_wednesday_to':
				case 'opening_hours_wednesday_second_from':
				case 'opening_hours_wednesday_second_to':
				case 'opening_hours_thursday_from':
				case 'opening_hours_thursday_to':
				case 'opening_hours_thursday_second_from':
				case 'opening_hours_thursday_second_to':
				case 'opening_hours_friday_from':
				case 'opening_hours_friday_to':
				case 'opening_hours_friday_second_from':
				case 'opening_hours_friday_second_to':
				case 'opening_hours_saturday_from':
				case 'opening_hours_saturday_to':
				case 'opening_hours_saturday_second_from':
				case 'opening_hours_saturday_second_to':
				case 'opening_hours_sunday_from':
				case 'opening_hours_sunday_to':
				case 'opening_hours_sunday_second_from':
				case 'opening_hours_sunday_second_to':
				case 'location_timezone':
				case 'unit_system':
				case 'map_view_style':
				case 'address_format':
				case 'default_country':
				case 'show_route_label':
				case 'local_api_key':
				case 'local_api_key_browser':
				case 'local_version':
				case 'woocommerce_local_pickup_setting':
					if ( isset( $dirty[ $key ] ) && $dirty[ $key ] !== '' ) {
						$clean[ $key ] = WPSEO_Utils::sanitize_text_field( $dirty[ $key ] );
					}
					break;
				case 'googlemaps_api_key':
					if ( isset( $dirty[ $key ] ) && $dirty[ $key ] !== '' ) {
						$clean[ $key ] = WPSEO_Utils::sanitize_text_field( $dirty[ $key ] );

						// Set a transient if the API key has changed to trigger the updated notification.
						if ( $dirty[ $key ] !== $old[ $key ] ) {
							set_transient( 'wpseo_local_api_key_changed', true );
						}
					}
					break;
				/* E-mail address */
				case 'location_contact_email':
				case 'location_email':
					if ( isset( $dirty[ $key ] ) && $dirty[ $key ] !== '' ) {
						$clean[ $key ] = sanitize_email( $dirty[ $key ] );
					}
					break;
				/* Toggle and checkbox fields */
				case 'use_multiple_locations':
				case 'multiple_locations_same_organization':
				case 'multiple_locations_shared_business_info':
				case 'multiple_locations_shared_opening_hours':
				case 'hide_opening_hours':
				case 'opening_hours_24h':
				case 'open_247':
				case 'multiple_opening_hours':
				case 'opening_hours_monday_24h':
				case 'opening_hours_tuesday_24h':
				case 'opening_hours_wednesday_24h':
				case 'opening_hours_thursday_24h':
				case 'opening_hours_friday_24h':
				case 'opening_hours_saturday_24h':
				case 'opening_hours_sunday_24h':
				case 'detect_location':
				case 'local_enhanced_search':
					if ( isset( $dirty[ $key ] ) && $dirty[ $key ] !== '' && in_array( $dirty[ $key ], [ 'on', 'off' ], true ) ) {
						$clean[ $key ] = $dirty[ $key ];
					}
					break;
				/* URL fields */
				case 'location_url':
					if ( isset( $dirty[ $key ] ) && $dirty[ $key ] !== '' ) {
						$clean[ $key ] = WPSEO_Utils::sanitize_url( $dirty[ $key ] );
					}
					break;
				/* Integers */
				case 'sl_num_results':
				case 'multiple_locations_primary_location':
				case 'local_custom_marker':
					if ( isset( $dirty[ $key ] ) && $dirty[ $key ] !== '' && WPSEO_Utils::validate_int( $dirty[ $key ] ) ) {
						$clean[ $key ] = $dirty[ $key ];
					}
					break;
				/* Floats */
				case 'location_coords_lat':
				case 'location_coords_long':
					if ( isset( $dirty[ $key ] ) && $dirty[ $key ] !== '' ) {
						$value = $dirty[ $key ];
						// Remove all characters except digits, minus, plus, and point.
						$clean_value = preg_replace( '/[^0-9\.\-\+]/', '', $value );

						if ( is_numeric( $clean_value ) ) {
							$clean[ $key ] = $clean_value;
						}
					}
					break;
				/* Bool values */
				default:
					$clean[ $key ] = ( isset( $dirty[ $key ] ) ? WPSEO_Utils::validate_bool( $dirty[ $key ] ) : false );
					break;
			}
		}

		return $clean;
	}
}
