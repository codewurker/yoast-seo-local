<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO\Admin\OnPage
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;

/**
 * Represents an implementation of the WPSEO_Endpoint interface to register one or multiple endpoints.
 */
class WPSEO_Local_Endpoint_Locations implements WPSEO_Endpoint {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	public const REST_NAMESPACE = 'yoast/v1';

	/**
	 * REST API endpoint name.
	 *
	 * @var string
	 */
	public const ENDPOINT_RETRIEVE = 'wpseo_locations';

	/**
	 * End-user capability required for the endpoint to give a response.
	 *
	 * @var string
	 */
	public const CAPABILITY_RETRIEVE = 'read';

	/**
	 * Constructs the WPSEO_Local_Endpoint_Locations class and sets the service to use.
	 */
	public function __construct() {
	}

	/**
	 * Registers the REST routes that are available on the endpoint.
	 *
	 * @return void
	 */
	public function register() {
		$args = [
			'methods'             => 'GET',
			'callback'            => [
				$this,
				'get_data',
			],
			'permission_callback' => [
				$this,
				'can_retrieve_data',
			],
		];

		// Register fetch config.
		register_rest_route( self::REST_NAMESPACE, self::ENDPOINT_RETRIEVE, $args );
	}

	/**
	 * Get location data.
	 *
	 * @return WP_REST_Response
	 */
	public function get_data() {
		$locations_repository_builder = new Locations_Repository_Builder();
		$location_repository          = $locations_repository_builder->get_locations_repository();
		$locations                    = $location_repository->get( [], false );

		$data = [];

		foreach ( $locations as $location ) {
			$data[] = [
				'ID'    => $location,
				'label' => ( get_the_title( $location ) !== '' ) ? get_the_title( $location ) : __( 'No title', 'yoast-local-seo' ),
			];
		}

		return new WP_REST_Response( $data );
	}

	/**
	 * Determines whether or not data can be retrieved for the registered endpoints.
	 *
	 * @return bool Whether or not data can be retrieved.
	 */
	public function can_retrieve_data() {
		return current_user_can( self::CAPABILITY_RETRIEVE );
	}
}
