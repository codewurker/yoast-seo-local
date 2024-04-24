<?php
/**
 * @package WPSEO_Local\Frontend\Schema
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Context\Meta_Tags_Context;

/**
 * Class WPSEO_Local_JSON_LD
 *
 * Manages the Schema for a branch Organization.
 *
 * @property Meta_Tags_Context $context A value object with context variables.
 * @property array             $options Local SEO options.
 */
class WPSEO_Local_Organization_Branch extends WPSEO_Local_Organization {

	/**
	 * Determines whether or not this piece should be added to the graph.
	 *
	 * @return bool
	 */
	public function is_needed() {
		// When changing these conditions also update class-organization.php will_have_branch_organization().
		return wpseo_schema_will_have_branch_organization( $this->context->site_represents === 'company' );
	}

	/**
	 * Generates JSON+LD output for a branch Organization.
	 *
	 * @return false|array Array with branch Organization schema data.
	 */
	public function generate() {
		$locations_repository_builder = new Locations_Repository_Builder();
		$repository                   = $locations_repository_builder->get_locations_repository();
		$location                     = $repository->for_current_page();

		// Bail if the $location object is empty.
		if ( ! $location ) {
			return false;
		}

		$location = $location;

		$data = [];

		return $this->get_data( $data, $location );
	}

	/**
	 * Generates data object for branch Organization.
	 *
	 * @param array|mixed $data     Data object of the current schema node.
	 * @param object      $location Data object of the related location.
	 *
	 * @return bool|array Array with branch Organization schema data.
	 */
	public function get_data( $data, $location ) {

		if ( ! empty( $data['@type'] ) && ! is_array( $data['@type'] ) ) {
			$data['@type'] = [ $data['@type'] ];
		}

		if ( empty( $data['@type'] ) ) {
			$data['@type'] = [ 'Organization', 'Place' ];
		}

		$data = parent::get_data( $data, $location );

		$data['@id']  = $this->context->canonical . $this->get_schema_id();
		$data['name'] = get_the_title();

		if ( wpseo_multiple_location_one_organization() ) {
			$data['parentOrganization'] = [ '@id' => $this->context->site_url . Schema_IDs::ORGANIZATION_HASH ];
		}

		return $data;
	}

	/**
	 * Gets the desired ID of the schema node.
	 *
	 * @return string ID of the schema node.
	 */
	public function get_schema_id() {
		return WPSEO_Local_Schema_IDs::BRANCH_ORGANIZATION_ID;
	}

	/**
	 * Gets the desired ID of the address schema node.
	 *
	 * @return string ID of the schema node.
	 */
	public function get_schema_address_id() {
		return WPSEO_Local_Schema_IDs::BRANCH_ADDRESS_ID;
	}

	/**
	 * Determines whether the schema piece should filter existing schema data.
	 *
	 * @return bool Value that indicates whether or not to filter existing schema data.
	 */
	public static function should_filter_existing_organization() {
		return false;
	}

	/**
	 * Determines whether the schema piece is an organization branch node.
	 *
	 * @return bool Value that indicates whether or not the schema piece is an organization branch node.
	 */
	public function is_branch() {
		return true;
	}
}
