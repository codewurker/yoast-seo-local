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
 * Manages the Schema for a Logo Image Object.
 *
 * @property Meta_Tags_Context $context A value object with context variables.
 * @property array             $options Local SEO options.
 */
class WPSEO_Local_Logo_Image_Object extends Abstract_Schema_Piece {

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
		return ( WPSEO_Local_Organization::should_filter_existing_organization() && ( ! wpseo_has_multiple_locations() || wpseo_has_primary_location() || wpseo_has_location_acting_as_primary() ) );
	}

	/**
	 * Generates JSON+LD output for locations.
	 *
	 * @return false|array Array with Image Object schema data. Returns false no valid location is found.
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
	 * Given an array of locations returns Image Object Schema data for the first.
	 *
	 * @param object $location Data object of the related location.
	 *
	 * @return array|false Place Schema data.
	 */
	public function get_data( $location ) {
		$schema_id = $this->context->canonical . WPSEO_Local_Schema_IDs::MAIN_ORGANIZATION_LOGO;

		if ( $this->is_branch() ) {
			$schema_id = $this->context->canonical . WPSEO_Local_Schema_IDs::BRANCH_ORGANIZATION_LOGO;
		}

		$logo_id = $location->business_logo;

		if ( empty( $logo_id ) ) {

			$logo_id = $this->context->company_logo_id;
		}

		$caption = $this->context->company_name;

		if ( $this->is_branch() ) {
			$caption = $location->business_name;
		}

		$data = [
			'logo' => $this->helpers->schema->image->generate_from_attachment_id( $schema_id, $logo_id, $caption ),
		];

		return $data;
	}

	/**
	 * Determines whether the schema piece is an organization branch node.
	 *
	 * @return bool Value that indicates whether or not the schema piece is an organization branch node.
	 */
	public function is_branch() {
		return false;
	}

	/**
	 * Gets the desired ID of the schema node.
	 *
	 * @return string ID of the schema node.
	 */
	public function get_schema_id() {
		return WPSEO_Local_Schema_IDs::MAIN_ORGANIZATION_LOGO;
	}
}
