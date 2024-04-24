<?php
/**
 * Yoast SEO: Local for WooCommerce plugin file.
 *
 * @package YoastSEO_Local_WooCommerce
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;

if ( ! function_exists( 'yoast_seo_local_woocommerce_get_address_for_method_id' ) ) {

	function yoast_seo_local_woocommerce_get_address_for_method_id( $method_id ) {

		// Only alter the shipping address when local shipping has been selected.
		if ( strpos( $method_id, 'yoast_wcseo_local_pickup' ) === false ) {
			return '';
		}

		// Get the specific post id for this location.
		$location_id = (int) str_replace( 'yoast_wcseo_local_pickup_', '', $method_id );
		$id          = 0;

		if ( ! empty( $location_id ) ) {
			$id = $location_id;
		}

		$args = [ 'id' => $id ];

		$locations_repository_builder = new Locations_Repository_Builder();
		$repository                   = $locations_repository_builder->get_locations_repository();
		$locations                    = $repository->get( $args );

		$location = $locations[ $id ];

		// Store the specs we want as an array.
		$address_array = [
			$location['business_address'],
			$location['business_zipcode'],
			$location['business_city'],
			$location['business_country'],
		];

		// Clear empty values.
		$address_array = array_filter( $address_array );

		// Return as a comma separated string.
		return implode( ', ', $address_array );
	}
}
