<?php
/**
 * @package WPSEO_Local\Frontend\Schema
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\SEO\Context\Meta_Tags_Context;

/**
 * Class WPSEO_Local_JSON_LD
 *
 * Manages the Schema for a branch Logo Image Object.
 *
 * @property Meta_Tags_Context $context A value object with context variables.
 * @property array             $options Local SEO options.
 */
class WPSEO_Local_Logo_Image_Object_Branch extends WPSEO_Local_Logo_Image_Object {

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
	 * @return false|array Array with Image Object schema data. Returns false no valid location is found.
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
	 * Determines whether the schema piece is an organization branch node.
	 *
	 * @return bool Value that indicates whether or not the schema piece is an organization branch node.
	 */
	public function is_branch() {
		return true;
	}

	/**
	 * Gets the desired ID of the schema node.
	 *
	 * @return string ID of the schema node.
	 */
	public function get_schema_id() {
		return WPSEO_Local_Schema_IDs::BRANCH_ORGANIZATION_LOGO;
	}
}
