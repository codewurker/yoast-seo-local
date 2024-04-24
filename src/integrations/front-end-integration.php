<?php

namespace Yoast\WP\Local\Integrations;

use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\Local\Presenters\Geo\Placename_Presenter;
use Yoast\WP\Local\Presenters\Geo\Position_Presenter;
use Yoast\WP\Local\Presenters\Geo\Region_Presenter;
use Yoast\WP\Local\Repositories\Locations_Repository;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Integrations\Integration_Interface;

/**
 * Class Front_End_Integration.
 */
class Front_End_Integration implements Integration_Interface {

	/**
	 * The Location repository.
	 *
	 * @var Locations_Repository
	 */
	private $locations;

	/**
	 * Front_End_Integration constructor.
	 *
	 * @param Locations_Repository $locations The Location repository.
	 * @param PostType             $post_type The PostType object. For BC.
	 */
	public function __construct( Locations_Repository $locations, PostType $post_type ) {
		$this->locations = $locations;
	}

	/**
	 * @inheritDoc
	 */
	public function register_hooks() {
		\add_filter( 'wpseo_frontend_presenters', [ $this, 'add_presenters' ], 10, 2 );
	}

	/**
	 * @inheritDoc
	 */
	public static function get_conditionals() {
		return [];
	}

	/**
	 * Adds needed presenters for Local SEO.
	 *
	 * @param array             $presenters The array of presenters.
	 * @param Meta_Tags_Context $context    The meta tags context.
	 *
	 * @return array The array of presenters.
	 */
	public function add_presenters( $presenters, $context ) {
		// Get presenters every time as they may be for a different location.
		return \array_merge( $presenters, $this->get_geo_presenters( $context ) );
	}

	/**
	 * Adds the GEO presenters if they're needed.
	 *
	 * @param Meta_Tags_Context $context The meta tags context.
	 *
	 * @return array
	 */
	private function get_geo_presenters( $context ) {
		$location = $this->locations->for_context( $context );

		$needed_presenters = [];

		$needed_presenters[] = new Placename_Presenter( $location );
		$needed_presenters[] = new Position_Presenter( $location );
		$needed_presenters[] = new Region_Presenter( $location );

		return $needed_presenters;
	}
}
