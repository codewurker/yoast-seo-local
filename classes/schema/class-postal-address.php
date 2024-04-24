<?php
/**
 * @package WPSEO_Local\Frontend\Schema
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Class WPSEO_Local_JSON_LD
 *
 * Manages the Schema for a Postal Address.
 *
 * @property Meta_Tags_Context $context A value object with context variables.
 * @property array                $options Local SEO options.
 */
class WPSEO_Local_Postal_Address extends Abstract_Schema_Piece {

	/**
	 * Stores the options for this plugin.
	 *
	 * @var array
	 */
	public $options = [];

	/**
	 * A value object with context variables.
	 *
	 * @var Meta_Tags_Context
	 */
	public $context;

	/**
	 * Constructor.
	 *
	 * @param Meta_Tags_Context $context A value object with context variables.
	 */
	public function __construct( Meta_Tags_Context $context ) {
		$this->context = $context;
		$this->options = get_option( 'wpseo_local' );
	}

	/**
	 * Determines whether or not this piece should be added to the graph.
	 *
	 * @return bool
	 */
	public function is_needed() {
		return (
			$this->context->site_represents === 'company'
			&& ( ! wpseo_has_multiple_locations() || wpseo_has_primary_location() || wpseo_has_location_acting_as_primary() )
		);
	}

	/**
	 * Generates JSON+LD output for locations.
	 *
	 * @return false|array Array with Postal Address schema data. Returns false no valid location is found.
	 */
	public function generate() {
		$args = [];
		$id   = 0;

		$locations_repository_builder = new Locations_Repository_Builder();
		$repository                   = $locations_repository_builder->get_locations_repository();

		if ( wpseo_has_primary_location() ) {
			$id   = WPSEO_Options::get( 'multiple_locations_primary_location' );
			$args = [ 'id' => $id ];
		}
		elseif ( wpseo_has_location_acting_as_primary() ) {
			$loc  = $repository->get( [ 'post_status' => 'publish' ], false );
			$id   = reset( $loc );
			$args = [ 'id' => $id ];
		}

		$locations = $repository->get( $args );
		$location  = (object) $locations[ $id ];

		return $this->get_data( $location );
	}

	/**
	 * Given an array of locations returns Postal Address Schema data for the first.
	 *
	 * @param object $location Data object of the related location.
	 *
	 * @return array|false Place Schema data.
	 */
	public function get_data( $location ) {

		// Bail if the $location object is empty.
		if ( ! $this->has_required_properties( $location ) ) {
			return false;
		}

		// Add Address field.
		$business_address = [];
		if ( ! empty( $location->business_address ) ) {
			$business_address[] = $location->business_address;
		}
		if ( ! empty( $location->business_address_2 ) ) {
			$business_address[] = $location->business_address_2;
		}

		$data = [
			'@type'           => 'PostalAddress',
			'@id'             => $this->context->canonical . $this->get_schema_id(),
			'streetAddress'   => ( ! empty( $business_address ) ) ? implode( ', ', $business_address ) : '',
			'addressLocality' => ( ! empty( $location->business_city ) ) ? $location->business_city : '',
			'postalCode'      => ( ! empty( $location->business_zipcode ) ) ? $location->business_zipcode : '',
			'addressRegion'   => ( ! empty( $location->business_state ) ) ? $location->business_state : '',
			'addressCountry'  => ( ! empty( $location->business_country ) ) ? $location->business_country : '',
		];

		// Remove empty strings..
		$data = array_filter( $data );

		return $data;
	}

	/**
	 * Determines whether the location data object has the required properties to output a node.
	 *
	 * @param array|mixed $location Location data to check.
	 *
	 * @return bool Value indicating whether the required properties exist.
	 */
	public static function has_required_properties( $location ) {
		if ( empty( $location->business_address ) ) {
			return false;
		}

		if ( empty( $location->business_zipcode ) ) {
			return false;
		}

		if ( empty( $location->business_country ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the desired ID of the schema node.
	 *
	 * @return string ID of the schema node.
	 */
	public function get_schema_id() {
		return WPSEO_Local_Schema_IDs::MAIN_ADDRESS_ID;
	}
}
