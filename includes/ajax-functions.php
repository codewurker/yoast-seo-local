<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Admin\Ajax
 */

/**
 * Copies location data for further use.
 *
 * @return array<bool,array<>>|void
 */
function wpseo_copy_location_callback() {

	$days      = [
		'monday'    => __( 'Monday', 'yoast-local-seo' ),
		'tuesday'   => __( 'Tuesday', 'yoast-local-seo' ),
		'wednesday' => __( 'Wednesday', 'yoast-local-seo' ),
		'thursday'  => __( 'Thursday', 'yoast-local-seo' ),
		'friday'    => __( 'Friday', 'yoast-local-seo' ),
		'saturday'  => __( 'Saturday', 'yoast-local-seo' ),
		'sunday'    => __( 'Sunday', 'yoast-local-seo' ),
	];
	$ret_array = [
		'success'  => true,
		'location' => [],
	];

	check_ajax_referer( 'wpseo-local-secnonce', 'security', false );

	if ( empty( $_POST['location_id'] ) ) {
		return $ret_array;
	}

	$location_id = absint( $_POST['location_id'] );

	$location = [
		'business_type'          => get_post_meta( $location_id, '_wpseo_business_type', true ),
		'business_address'       => get_post_meta( $location_id, '_wpseo_business_address', true ),
		'business_city'          => get_post_meta( $location_id, '_wpseo_business_city', true ),
		'business_state'         => get_post_meta( $location_id, '_wpseo_business_state', true ),
		'business_zipcode'       => get_post_meta( $location_id, '_wpseo_business_zipcode', true ),
		'business_country'       => get_post_meta( $location_id, '_wpseo_business_country', true ),
		'business_phone'         => get_post_meta( $location_id, '_wpseo_business_phone', true ),
		'business_phone_2nd'     => get_post_meta( $location_id, '_wpseo_business_phone_2nd', true ),
		'business_fax'           => get_post_meta( $location_id, '_wpseo_business_fax', true ),
		'business_email'         => get_post_meta( $location_id, '_wpseo_business_email', true ),
		'business_contact_email' => get_post_meta( $location_id, '_wpseo_business_contact_email', true ),
		'business_contact_phone' => get_post_meta( $location_id, '_wpseo_business_contact_phone', true ),
		'business_vat_id'        => get_post_meta( $location_id, '_wpseo_business_vat_id', true ),
		'business_tax_id'        => get_post_meta( $location_id, '_wpseo_business_tax_id', true ),
		'business_coc_id'        => get_post_meta( $location_id, '_wpseo_business_coc_id', true ),
		'coordinates_lat'        => get_post_meta( $location_id, '_wpseo_coordinates_lat', true ),
		'coordinates_long'       => get_post_meta( $location_id, '_wpseo_coordinates_long', true ),
		'is_postal_address'      => get_post_meta( $location_id, '_wpseo_is_postal_address', true ),
		'multiple_opening_hours' => get_post_meta( $location_id, '_wpseo_multiple_opening_hours', true ),
	];

	foreach ( $days as $key => $day ) {
		$field_name = '_wpseo_opening_hours_' . $key;
		$value_from = get_post_meta( $location_id, $field_name . '_from', true );
		if ( ! $value_from ) {
			$value_from = '09:00';
		}
		$value_to = get_post_meta( $location_id, $field_name . '_to', true );
		if ( ! $value_to ) {
			$value_to = '17:00';
		}
		$value_second_from = get_post_meta( $location_id, $field_name . '_second_from', true );
		if ( ! $value_second_from ) {
			$value_second_from = '09:00';
		}
		$value_second_to = get_post_meta( $location_id, $field_name . '_second_to', true );
		if ( ! $value_second_to ) {
			$value_second_to = '17:00';
		}

		$location[ $field_name . '_from' ]        = $value_from;
		$location[ $field_name . '_to' ]          = $value_to;
		$location[ $field_name . '_second_from' ] = $value_second_from;
		$location[ $field_name . '_second_to' ]   = $value_second_to;
	}

	$ret_array['location'] = $location;

	// phpcs:ignore WordPress.Security.EscapeOutput -- WPCS bug: methods can't be globally ignored yet.
	die( WPSEO_Utils::format_json_encode( $ret_array ) );
}

/**
 * Callback function to get address data.
 *
 * @return void
 */
function wpseo_local_show_address_ajax_cb() {
	$atts   = [
		'id'                 => $_POST['id'],
		'hide_name'          => $_POST['hideName'],
		'hide_address'       => $_POST['hideCompanyAddress'],
		'oneline'            => $_POST['showOnOneLine'],
		'show_state'         => $_POST['showState'],
		'show_country'       => $_POST['showCountry'],
		'show_phone'         => $_POST['showPhone'],
		'show_phone_2'       => $_POST['showPhone2nd'],
		'show_fax'           => $_POST['showFax'],
		'show_email'         => $_POST['showEmail'],
		'show_url'           => $_POST['showURL'],
		'show_logo'          => $_POST['showLogo'],
		'show_vat'           => $_POST['showVatId'],
		'show_tax'           => $_POST['showTaxId'],
		'show_coc'           => $_POST['showCocId'],
		'show_price_range'   => $_POST['showPriceRange'],
		'show_opening_hours' => $_POST['showOpeningHours'],
		'hide_closed'        => $_POST['hideClosedDays'],
		'is_preview'         => $_POST['isPreview'],
		'hide_json_ld'       => true,
	];
	$return = wpseo_local_show_address( $atts );
	wp_send_json( $return );
}

/**
 * Callback function to get address data.
 *
 * @return void
 */
function wpseo_local_show_map_ajax_cb() {
	$atts   = [
		'id'                      => $_POST['id'],
		'term_id'                 => '',
		'center'                  => '',
		'max_number'              => '',
		'width'                   => 400,
		'height'                  => 300,
		'zoom'                    => -1,
		'show_route'              => false,
		'show_state'              => $_POST['showState'],
		'show_country'            => $_POST['showCountry'],
		'show_url'                => $_POST['showURL'],
		'show_email'              => $_POST['showEmail'],
		'default_show_infowindow' => false,
		'map_style'               => ( isset( $options['map_view_style'] ) ) ? $options['map_view_style'] : 'ROADMAP',
		'scrollable'              => true,
		'draggable'               => true,
		'marker_clustering'       => false,
		'show_route_label'        => ( isset( $options['show_route_label'] ) && ! empty( $options['show_route_label'] ) ) ? $options['show_route_label'] : __( 'Show route', 'yoast-local-seo' ),
		'from_sl'                 => false,
		'show_category_filter'    => false,
		'hide_json_ld'            => true,
		'echo'                    => false,
		'show_phone'              => $_POST['showPhone'],
		'show_phone_2'            => $_POST['showPhone2nd'],
		'show_fax'                => $_POST['showFax'],
		'show_opening_hours'      => false,
		'hide_closed'             => false,
	];
	$return = wpseo_local_show_map( $atts );
	wp_send_json( $return );
}

/**
 * Callback function to get location opening hours data.
 *
 * @return void
 */
function wpseo_local_show_opening_hours_ajax_cb() {
	$atts = [
		'id'              => $_POST['id'],
		'show_days'       => $_POST['showDays'],
		'show_open_label' => $_POST['showOpenLabel'],
		'comment'         => $_POST['extraComment'],
		'hide_json_ld'    => true,
	];

	$return = wpseo_local_show_opening_hours( $atts );

	wp_send_json( $return );
}
