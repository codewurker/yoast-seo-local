<?php
/**
 * @package WPSEO_Local\Frontend\Schema
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\SEO\Context\Meta_Tags_Context;

/**
 * Class WPSEO_Local_JSON_LD
 *
 * Manages the Schema for a branch Postal Address.
 *
 * @property Meta_Tags_Context $context A value object with context variables.
 * @property array             $options Local SEO options.
 */
class WPSEO_Local_Postal_Address_Branch extends WPSEO_Local_Postal_Address {

	/**
	 * Determines whether or not this piece should be added to the graph.
	 *
	 * @return bool
	 */
	public function is_needed() {
		return wpseo_schema_will_have_branch_organization( $this->context->site_represents === 'company' );
	}

	/**
	 * Generates JSON+LD output for locations.
	 *
	 * @return false|array Array with Postal Address schema data. Returns false no valid location is found.
	 */
	public function generate() {
		$locations_repository_builder = new Locations_Repository_Builder();
		$repository                   = $locations_repository_builder->get_locations_repository();
		$location                     = $repository->for_current_page();

		// Bail if the $location object is empty.
		if ( ! $location ) {
			return false;
		}

		return $this->get_data( $location );
	}

	/**
	 * Gets the desired ID of the schema node.
	 *
	 * @return string ID of the schema node.
	 */
	public function get_schema_id() {
		return WPSEO_Local_Schema_IDs::BRANCH_ADDRESS_ID;
	}
}
