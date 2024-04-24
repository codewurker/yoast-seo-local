<?php
/**
 * @package WPSEO_Local\Frontend\Schema
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Class WPSEO_Local_Organization_List.
 *
 * Manages the Schema for an Organization List.
 *
 * @property Meta_Tags_Context $context A value object with context variables.
 * @property array             $options Local SEO options.
 */
class WPSEO_Local_Organization_List extends Abstract_Schema_Piece {

	/**
	 * A value object with context variables.
	 *
	 * @var Meta_Tags_Context
	 */
	public $context;

	/**
	 * Stores the options for this plugin.
	 *
	 * @var array
	 */
	public $options = [];

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
		$post_type = new PostType();
		$post_type->initialize();

		return is_post_type_archive( $post_type->get_post_type() );
	}

	/**
	 * Generates JSON+LD output for locations.
	 *
	 * @return bool|array Array with Place schema data. Returns false when no valid location is found.
	 */
	public function generate() {
		$locations_repository_builder = new Locations_Repository_Builder();
		$repo                         = $locations_repository_builder->get_locations_repository();
		$locations                    = $repo->get();

		if ( count( $locations ) === 0 ) {
			return false;
		}

		$data = [
			'@type'            => 'ItemList',
			'@id'              => $this->context->canonical . WPSEO_Local_Schema_IDs::LIST_ID,
			'mainEntityOfPage' => [ '@id' => $this->context->main_schema_id ],
		];

		$i = 0;
		foreach ( $locations as $location ) {
			++$i;
			$data['itemListElement'][] = [
				'@type'    => 'ListItem',
				'position' => $i,
				'url'      => get_permalink( $location['post_id'] ),
			];
		}

		return $data;
	}
}
