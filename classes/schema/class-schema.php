<?php
/**
 * @package WPSEO_Local\Frontend\Schema
 */

use Yoast\WP\SEO\Context\Meta_Tags_Context;

/**
 * Class WPSEO_Local_JSON_LD.
 *
 * Manages the Schema.
 *
 * @property Meta_Tags_Context $context A value object with context variables.
 * @property array             $options Local SEO options.
 */
class WPSEO_Local_Schema {

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
	private $context;

	/**
	 * WPSEO_Local_JSON_LD constructor.
	 */
	public function __construct() {
		$this->options = get_option( 'wpseo_local' );

		add_filter( 'wpseo_schema_graph_pieces', [ $this, 'add_graph_piece' ], 11, 2 );
	}

	/**
	 * Adds the graph pieces to the Schema Graph.
	 *
	 * @param array             $pieces  Array of Graph pieces.
	 * @param Meta_Tags_Context $context A value object with context variables.
	 *
	 * @return array Array of Graph pieces.
	 */
	public function add_graph_piece( $pieces, Meta_Tags_Context $context ) {
		$this->context = $context;

		$pieces[] = new WPSEO_Local_Postal_Address( $context );
		$pieces[] = new WPSEO_Local_Postal_Address_Branch( $context );
		$pieces[] = new WPSEO_Local_Organization( $context );
		$pieces[] = new WPSEO_Local_Organization_Branch( $context );
		$pieces[] = new WPSEO_Local_Organization_List( $context );
		$pieces[] = new WPSEO_Local_Organization_List( $context );
		$pieces[] = new WPSEO_Local_Logo_Image_Object( $context );
		$pieces[] = new WPSEO_Local_Logo_Image_Object_Branch( $context );

		return $pieces;
	}
}
