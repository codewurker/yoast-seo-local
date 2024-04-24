<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Frontend
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\Local\Formatters\Address_Formatter;
use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\Local\Repositories\Options_Repository;
use Yoast\WP\Local\Repositories\Timezone_Repository;
use Yoast\WP\SEO\Helpers\Indexable_Helper;
use Yoast\WP\SEO\Integrations\Watchers\Indexable_Permalink_Watcher;
use Yoast\WP\SEO\Repositories\Indexable_Repository;

/**
 * Address shortcode handler
 *
 * @since 0.1
 *
 * @param array $atts Array of shortcode parameters.
 *
 * @return string
 */
function wpseo_local_show_address( $atts ) {
	$defaults = [
		'id'                 => '',
		'term_id'            => '',
		'max_number'         => '',
		'hide_name'          => false,
		'hide_address'       => false,
		'show_state'         => true,
		'show_country'       => true,
		'show_phone'         => true,
		'show_phone_2'       => true,
		'show_fax'           => true,
		'show_email'         => true,
		'show_url'           => false,
		'show_vat'           => false,
		'show_tax'           => false,
		'show_coc'           => false,
		'show_price_range'   => false,
		'show_logo'          => false,
		'show_opening_hours' => false,
		'hide_closed'        => false,
		'oneline'            => false,
		'comment'            => '',
		'from_sl'            => false,
		'from_widget'        => false,
		'widget_title'       => '',
		'before_title'       => '',
		'after_title'        => '',
		'echo'               => false,
		'is_preview'         => false,
	];
	$atts     = wpseo_check_falses( shortcode_atts( $defaults, $atts, 'wpseo_local_show_address' ) );

	// Bail if no current location is chosen when using multiple location setup.
	if ( wpseo_has_multiple_locations() && empty( $atts['id'] ) ) {
		return '';
	}

	$options = get_option( 'wpseo_local' );
	if ( isset( $options['hide_opening_hours'] ) && $options['hide_opening_hours'] === 'on' ) {
		$atts['show_opening_hours'] = false;
	}

	$is_postal_address = false;
	$output            = '';

	/*
	 * This array can be used in a filter to change the order and the labels of contact details
	 */
	$business_contact_details = [
		[
			'key'   => 'phone',
			'label' => __( 'Phone', 'yoast-local-seo' ),
		],
		[
			'key'   => 'phone_2',
			'label' => __( 'Secondary phone', 'yoast-local-seo' ),
		],
		[
			'key'   => 'fax',
			'label' => __( 'Fax', 'yoast-local-seo' ),
		],
		[
			'key'   => 'email',
			'label' => __( 'Email', 'yoast-local-seo' ),
		],
		[
			'key'   => 'url',
			'label' => __( 'URL', 'yoast-local-seo' ),
		],
		[
			'key'   => 'vat',
			'label' => __( 'VAT ID', 'yoast-local-seo' ),
		],
		[
			'key'   => 'tax',
			'label' => __( 'Tax ID', 'yoast-local-seo' ),
		],
		[
			'key'   => 'coc',
			'label' => __( 'Chamber of Commerce ID', 'yoast-local-seo' ),
		],
		[
			'key'   => 'price_range',
			'label' => __( 'Price indication', 'yoast-local-seo' ),
		],
	];

	$business_contact_details = apply_filters( 'wpseo_local_contact_details', $business_contact_details );

	// Initiate the Locations repository and get query the locations.
	$locations_repository_builder = new Locations_Repository_Builder();
	$repo                         = $locations_repository_builder->get_locations_repository();
	$filter_args                  = [
		'id'          => explode( ',', $atts['id'] ),
		'category_id' => $atts['term_id'],
		'number'      => $atts['max_number'],
	];
	$locations                    = $repo->get( $filter_args );

	if ( empty( $locations ) && is_admin() ) {
		if ( $atts['is_preview'] ) {
			$locations[] = yoast_seo_local_dummy_address();
		}
	}

	foreach ( $locations as $location ) {
		$tag_title_open  = '';
		$tag_title_close = '';

		if ( ! $atts['oneline'] ) {

			if ( ! $atts['from_widget'] ) {
				$tag_name        = apply_filters( 'wpseo_local_location_title_tag_name', 'h3' );
				$tag_title_open  = '<' . esc_html( $tag_name ) . '>';
				$tag_title_close = '</' . esc_html( $tag_name ) . '>';
			}
			elseif ( $atts['from_widget'] && $atts['widget_title'] === '' ) {
				$tag_title_open  = wp_kses_post( $atts['before_title'] );
				$tag_title_close = wp_kses_post( $atts['after_title'] );
			}
		}

		$container_id = 'wpseo_location-' . esc_attr( $atts['id'] );
		$output       = '<div id="' . $container_id . '" class="wpseo-location">';

		if ( empty( $location['business_name'] ) && $atts['id'] === 'preview' ) {
			$location['business_name'] = esc_html__( 'Your company', 'yoast-local-seo' );
		}

		if ( $atts['hide_name'] == false ) {
			$post_type_instance = new PostType();
			$post_type_instance->initialize();
			$post_type = $post_type_instance->get_post_type();

			$pt_object = get_post_type_object( $post_type );
			$output   .= $tag_title_open . ( ( $atts['from_sl'] && $pt_object->public ) ? '<a href="' . esc_url( $location['business_url'] ) . '">' : '' ) . '<span class="wpseo-business-name">' . esc_html( $location['business_name'] ) . '</span>' . ( ( $atts['from_sl'] ) ? '</a>' : '' ) . $tag_title_close;
		}

		if ( $atts['show_logo'] && ! empty( $location['business_logo'] ) && is_numeric( $location['business_logo'] ) ) {
			$output .= '<figure>';
			$output .= wp_get_attachment_image( $location['business_logo'], apply_filters( 'yoast_seo_local_business_logo_size', 'full' ) );
			$output .= '</figure>';
		}
		$output .= '<' . ( ( $atts['oneline'] ) ? 'span' : 'div' ) . ' class="wpseo-address-wrapper">';

		// Output city/state/zipcode in right format.
		$address_format = ! empty( $options['address_format'] ) ? $options['address_format'] : 'address-state-postal';

		if ( empty( $location['business_address'] ) && empty( $location['business_address_2'] ) && empty( $location['business_zipcode'] ) && empty( $location['business_city'] ) && empty( $location['business_state'] ) && $atts['id'] === 'preview' ) {
			$location = yoast_seo_local_dummy_address();
		}

		if ( ! empty( $location['business_address'] ) || ! empty( $location['business_address_2'] ) || ! empty( $location['business_zipcode'] ) || ! empty( $location['business_city'] ) || ! empty( $location['business_state'] ) ) {
			$format                = new Address_Formatter();
			$address_details       = [
				'show_logo'          => ( ! empty( $business_location_logo ) ),
				'hide_business_name' => $atts['hide_name'],
				'business_address'   => $location['business_address'],
				'business_address_2' => $location['business_address_2'],
				'oneline'            => $atts['oneline'],
				'business_zipcode'   => $location['business_zipcode'],
				'business_city'      => $location['business_city'],
				'business_state'     => $location['business_state'],
				'show_state'         => $atts['show_state'],
				'escape_output'      => false,
				'use_tags'           => true,
			];
			$address_format_output = $format->get_address_format( $address_format, $address_details );

			if ( ! empty( $address_format_output ) && $atts['hide_address'] === false ) {
				$output .= $address_format_output;
			}

			if ( $atts['show_country'] && ! empty( $location['business_country'] ) && ! $atts['hide_address'] ) {
				$output .= ( $atts['oneline'] ) ? ', ' : ' ';
			}

			if ( $atts['show_country'] && ! empty( $location['business_country'] ) ) {
				$output .= '<' . ( ( $atts['oneline'] ) ? 'span' : 'div' ) . '  class="country-name">' . WPSEO_Local_Frontend::get_country( $location['business_country'] ) . '</' . ( ( $atts['oneline'] ) ? 'span' : 'div' ) . '>';
			}
		}

		$output        .= '</' . ( ( $atts['oneline'] ) ? 'span' : 'div' ) . '>';
		$details_output = '';

		foreach ( $business_contact_details as $order => $details ) {
			if ( $details['key'] === 'phone' && $atts['show_phone'] && ! empty( $location['business_phone'] ) ) {
				/* translators: %s extends to the label for phone */
				$details_output .= sprintf( '<span class="wpseo-phone">%s: <a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $location['business_phone'] ) ) . '" class="tel"><span>' . esc_html( $location['business_phone'] ) . '</span></a></span>' . ( ( $atts['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
			}

			if ( $details['key'] === 'phone_2' && $atts['show_phone_2'] && ! empty( $location['business_phone_2nd'] ) ) {
				/* translators: %s extends to the label for 2nd phone */
				$details_output .= sprintf( '<span class="wpseo-phone2nd">%s: <a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $location['business_phone_2nd'] ) ) . '" class="tel">' . esc_html( $location['business_phone_2nd'] ) . '</a></span>' . ( ( $atts['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
			}

			if ( $details['key'] === 'fax' && $atts['show_fax'] && ! empty( $location['business_fax'] ) ) {
				/* translators: %s extends to the label for fax */
				$details_output .= sprintf( '<span class="wpseo-fax">%s: <span class="tel">' . esc_html( $location['business_fax'] ) . '</span></span>' . ( ( $atts['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
			}

			if ( $details['key'] === 'email' && $atts['show_email'] && ! empty( $location['business_email'] ) ) {
				/* translators: %s extends to the label for e-mail */
				$details_output .= sprintf( '<span class="wpseo-email">%s: <a href="' . esc_url( 'mailto:' . antispambot( $location['business_email'] ) ) . '">' . antispambot( esc_html( $location['business_email'] ) ) . '</a></span>' . ( ( $atts['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
			}

			if ( $details['key'] === 'url' && $atts['show_url'] ) {
				/* translators: %s extends to the label for business url */
				$details_output .= sprintf( '<span class="wpseo-url">%s: <a href="' . esc_url( $location['business_url'] ) . '">' . esc_html( $location['business_url'] ) . '</a></span>' . ( ( $atts['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
			}

			if ( $details['key'] === 'vat' && $atts['show_vat'] && ! empty( $location['business_vat'] ) ) {
				/* translators: %s extends to the label for businss VAT number */
				$details_output .= sprintf( '<span class="wpseo-vat">%s: <span>' . esc_html( $location['business_vat'] ) . '</span></span>' . ( ( $atts['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
			}

			if ( $details['key'] === 'tax' && $atts['show_tax'] && ! empty( $location['business_tax'] ) ) {
				/* translators: %s extends to the label for business tax number */
				$details_output .= sprintf( '<span class="wpseo-tax">%s: <span>' . esc_html( $location['business_tax'] ) . '</span></span>' . ( ( $atts['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
			}

			if ( $details['key'] === 'coc' && $atts['show_coc'] && ! empty( $location['business_coc'] ) ) {
				/* translators: %s extends to the label for business COC number*/
				$details_output .= sprintf( '<span class="wpseo-coc">%s: ' . esc_html( $location['business_coc'] ) . '</span>' . ( ( $atts['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
			}

			if ( $details['key'] === 'price_range' && $atts['show_price_range'] && ! empty( $location['business_price_range'] ) ) {
				/* translators: %s extends to the label for business Price Range */
				$details_output .= sprintf( '<span class="wpseo-price-range">%s: ' . esc_html( $location['business_price_range'] ) . '</span>' . ( ( $atts['oneline'] ) ? ' ' : '<br/>' ), esc_html( $details['label'] ) );
			}
		}

		if ( $details_output !== '' && $atts['oneline'] == true ) {
			$output .= ' - ';
		}

		$output .= $details_output;
		if ( $atts['show_opening_hours'] ) {
			$args    = [
				'id'          => ( wpseo_has_multiple_locations() ) ? $atts['id'] : '',
				'hide_closed' => $atts['hide_closed'],
			];
			$output .= '<br/>' . wpseo_local_show_opening_hours( $args, false ) . '<br/>';
		}
		$output .= '</div>';

		$output = apply_filters( 'wpseo_show_address_after', $output, $atts['id'], $container_id );
	}

	if ( $atts['comment'] != '' ) {
		$output .= '<div class="wpseo-extra-comment">' . wpautop( html_entity_decode( $atts['comment'], ENT_COMPAT, get_bloginfo( 'charset' ) ) ) . '</div>';
	}

	if ( $atts['echo'] ) {
		echo $output;
	}

	return $output;
}

/**
 * Shortcode for showing all locations at once. May come in handy for "office overview" pages.
 *
 * @since 1.1.7
 *
 * @param array $atts Array of shortcode parameters.
 *
 * @return string
 */
function wpseo_local_show_all_locations( $atts ) {
	$defaults = [
		'number'             => -1,
		'term_id'            => '',
		'orderby'            => 'menu_order title',
		'order'              => 'ASC',
		'show_state'         => true,
		'show_country'       => true,
		'show_phone'         => true,
		'show_phone_2'       => true,
		'show_fax'           => true,
		'show_email'         => true,
		'show_url'           => false,
		'show_logo'          => false,
		'show_opening_hours' => false,
		'hide_closed'        => false,
		'oneline'            => false,
		'echo'               => false,
		'comment'            => '',
	];
	$atts     = wpseo_check_falses( shortcode_atts( $defaults, $atts, 'wpseo_local_show_all_locations' ) );

	// Don't show any data when post_type is not activated. This function/shortcode makes no sense for single location.
	if ( ! wpseo_has_multiple_locations() ) {
		return '';
	}

	$output                       = '';
	$locations_repository_builder = new Locations_Repository_Builder();
	$repo                         = $locations_repository_builder->get_locations_repository();
	$filter_args                  = [
		'number'  => $atts['number'],
		'orderby' => $atts['orderby'],
		'order'   => $atts['order'],
	];

	if ( $atts['term_id'] != '' ) {
		$filter_args['category_id'] = $atts['term_id'];
	}
	$locations = $repo->get( $filter_args, false );

	if ( count( $locations ) > 0 ) {
		$output .= '<div class="wpseo-all-locations">';
		foreach ( $locations as $location_id ) {

			$address_atts = [
				'id'                 => $location_id,
				'show_state'         => $atts['show_state'],
				'show_country'       => $atts['show_country'],
				'show_phone'         => $atts['show_phone'],
				'show_phone_2'       => $atts['show_phone_2'],
				'show_fax'           => $atts['show_fax'],
				'show_email'         => $atts['show_email'],
				'show_url'           => $atts['show_url'],
				'show_logo'          => $atts['show_logo'],
				'show_opening_hours' => $atts['show_opening_hours'],
				'hide_closed'        => $atts['hide_closed'],
				'oneline'            => $atts['oneline'],
				'echo'               => false,
			];
			$location     = apply_filters( 'wpseo_all_locations_location', wpseo_local_show_address( $address_atts ) );

			$output .= $location;
		}

		if ( $atts['comment'] != '' ) {
			$output .= '<div class="wpseo-extra-comment">' . wpautop( html_entity_decode( $atts['comment'], ENT_COMPAT, get_bloginfo( 'charset' ) ) ) . '</div>';
		}

		$output .= '</div>';
	}
	else {
		echo '<p>' . esc_html__( 'There are no locations to show.', 'yoast-local-seo' ) . '</p>';
	}

	if ( $atts['echo'] ) {
		echo $output;
	}

	return $output;
}

/**
 * Maps shortcode handler
 *
 * @since 0.1
 *
 * @param array $atts Array of shortcode parameters.
 *
 * @return string
 */
function wpseo_local_show_map( $atts ) {
	global $map_counter, $wpseo_enqueue_geocoder, $wpseo_map;

	$options = get_option( 'wpseo_local' );

	// Define all used variables.
	$location_array     = [];
	$lats               = [];
	$longs              = [];
	$all_categories     = [];
	$location_array_str = [];
	$map                = '';

	// Backwards compatibility for scrollable / zoomable functions.
	if ( is_array( $atts ) && ! array_key_exists( 'zoomable', $atts ) ) {
		$atts['zoomable'] = ( isset( $atts['scrollable'] ) ) ? $atts['scrollable'] : true;
	}

	$defaults = [
		'id'                      => '',
		'term_id'                 => '',
		'center'                  => '',
		'max_number'              => '',
		'width'                   => 400,
		'height'                  => 300,
		'zoom'                    => -1,
		'show_route'              => true,
		'show_state'              => true,
		'show_country'            => false,
		'show_url'                => false,
		'show_email'              => false,
		'default_show_infowindow' => false,
		'map_style'               => ( isset( $options['map_view_style'] ) ) ? $options['map_view_style'] : 'ROADMAP',
		'scrollable'              => true,
		'draggable'               => true,
		'marker_clustering'       => false,
		'show_route_label'        => ( isset( $options['show_route_label'] ) && ! empty( $options['show_route_label'] ) ) ? $options['show_route_label'] : __( 'Show route', 'yoast-local-seo' ),
		'from_sl'                 => false,
		'show_category_filter'    => false,
		'hide_json_ld'            => ( wpseo_has_multiple_locations() ? false : true ),
		'echo'                    => false,
		'show_phone'              => false,
		'show_phone_2'            => false,
		'show_fax'                => false,
		'show_opening_hours'      => false,
		'hide_closed'             => false,
	];
	$atts     = wpseo_check_falses( shortcode_atts( $defaults, $atts, 'wpseo_local_show_map' ) );

	// Bail if no current location is chosen when using multiple location setup.
	if ( wpseo_has_multiple_locations() && empty( $atts['id'] ) ) {
		return '';
	}

	if ( ! isset( $map_counter ) ) {
		$map_counter = 0;
	}
	else {
		++$map_counter;
	}
	// Check if zoom is set to true or false by the wpseo_check_falses function. If so, turn them back into 0 or 1.
	if ( $atts['zoom'] === true ) {
		$atts['zoom'] = 1;
	}
	elseif ( $atts['zoom'] === false ) {
		$atts['zoom'] = 0;
	}

	// Initiate the Locations repository and get query the locations.
	$locations_repository_builder = new Locations_Repository_Builder();
	$repo                         = $locations_repository_builder->get_locations_repository();
	$filter_args                  = [
		'id'          => explode( ',', $atts['id'] ),
		'category_id' => $atts['term_id'],
		'number'      => ( isset( $atts['max_number'] ) && ! empty( $atts['max_number'] ) ? $atts['max_number'] : -1 ),
	];
	$locations                    = $repo->get( $filter_args );

	add_action( 'wp_footer', 'wpseo_enqueue_geocoder' );
	add_action( 'admin_footer', 'wpseo_enqueue_geocoder' );

	$asset_manager = new WPSEO_Local_Admin_Assets();
	$asset_manager->enqueue_script( 'google-maps' );

	$post_type_instance = new PostType();
	$post_type_instance->initialize();
	$post_type = $post_type_instance->get_post_type();

	$noscript_output = '<ul>';
	foreach ( $locations as $location_key => $location ) {
		$terms = [];
		if ( isset( $location['terms'] ) && ! empty( $location['terms'] ) ) {
			foreach ( $location['terms'] as $key => $term ) {
				$terms[ $term->slug ]          = $term->name;
				$all_categories[ $term->slug ] = $term->name;
			}
		}

		if ( ( post_type_exists( $post_type ) && get_post_type_object( $post_type )->public === true ) ) {
			$self_url = get_permalink( $location['post_id'] );
		}
		else {
			$self_url = '';
		}

		// Allow the option for a user to alter the URL that shows in the maps infowindow box.
		$self_url = apply_filters( 'yoast_seo_local_change_map_location_url', $self_url, $location['post_id'] );

		if ( $location['coords']['lat'] !== '' && $location['coords']['long'] !== '' ) {
			$address_atts = [
				'id'                 => ( $location['post_id'] ?? '' ),
				'hide_name'          => true,
				'business_address'   => wpseo_cleanup_string( $location['business_address'] ),
				'business_address_2' => wpseo_cleanup_string( $location['business_address_2'] ),
				'business_zipcode'   => $location['business_zipcode'],
				'business_city'      => $location['business_city'],
				'business_state'     => $location['business_state'],
				'show_state'         => $atts['show_state'],
				'show_country'       => $atts['show_country'],
				'show_email'         => $atts['show_email'],
				'show_url'           => $atts['show_url'],
				'escape_output'      => true,
				'use_tags'           => true,
				'hide_json_ld'       => true,
				// Don't show JSON+LD in the infoWindow, since Google cannot parse it here.
				'show_phone'         => $atts['show_phone'],
				'show_phone_2'       => $atts['show_phone_2'],
				'show_fax'           => $atts['show_fax'],
				'show_opening_hours' => false,
				// Does not fit in infowindow, requires extra element below map element.
				'oneline'            => false,
				// Does not fit in infowindow, requires extra element below map element.
				'hide_closed'        => false,
				// Opening hours not yet supported.
			];
			$full_address = wpseo_local_show_address( $address_atts );

			$location_array_str[] = "{
				'name': '" . wpseo_cleanup_string( $location['business_name'] ) . "',
				'url': '" . wpseo_cleanup_string( $location['business_url'] ) . "',
				'address': " . WPSEO_Utils::format_json_encode( $full_address ) . ",
				'country': '" . WPSEO_Local_Frontend::get_country( $location['business_country'] ) . "',
				'show_country': " . ( ( $atts['show_country'] ) ? 'true' : 'false' ) . ",
				'url': '" . esc_url( $location['business_url'] ) . "',
				'show_url': " . ( ( $atts['show_url'] ) ? 'true' : 'false' ) . ",
				'email': '" . antispambot( $location['business_email'] ) . "',
				'show_email': " . ( ( $atts['show_email'] ) ? 'true' : 'false' ) . ",
				'phone': '" . wpseo_cleanup_string( $location['business_phone'] ) . "',
				'phone_2nd': '" . wpseo_cleanup_string( $location['business_phone_2nd'] ) . "',
				'fax': '" . wpseo_cleanup_string( $location['business_fax'] ) . "',
				'lat': " . wpseo_cleanup_string( $location['coords']['lat'] ) . ",
				'long': " . wpseo_cleanup_string( $location['coords']['long'] ) . ",
				'custom_marker': '" . wpseo_cleanup_string( $location['custom_marker'] ) . "',
				'categories': " . WPSEO_Utils::format_json_encode( $terms ) . ",
				'self_url': '" . $self_url . "',
			}";
		}
		$noscript_output .= '<li>';
		if ( $location['business_url'] !== get_permalink() ) {
			$noscript_output .= '<a href="' . esc_url( $location['business_url'] ) . '">';
		}
		$noscript_output .= esc_html( $location['business_name'] );
		if ( $location['business_url'] !== get_permalink() ) {
			$noscript_output .= '</a>';
		}
		$noscript_output .= '</li>';

		$full_address                                    = $location['business_address'] . ', ' . $location['business_city'] . ( ( strtolower( $location['business_country'] ) === 'us' ) ? ', ' . $location['business_state'] : '' ) . ', ' . $location['business_zipcode'] . ', ' . WPSEO_Local_Frontend::get_country( $location['business_country'] );
		$location_array[ $location_key ]['full_address'] = $full_address;

		// Add coordinates to lats and longs arrays to use in centering.
		if ( ! empty( $location['coords']['lat'] ) && is_numeric( $location['coords']['lat'] ) ) {
			$lats[] = $location['coords']['lat'];
		}
		if ( ! empty( $location['coords']['long'] ) && is_numeric( $location['coords']['long'] ) ) {
			$longs[] = $location['coords']['long'];
		}
	}
	$noscript_output .= '</ul>';

	$map                    = '';
	$wpseo_enqueue_geocoder = true;

	if ( ! is_array( $lats ) || empty( $lats ) || ! is_array( $longs ) || empty( $longs ) ) {
		return;
	}

	if ( $atts['center'] === '' ) {
		$center_lat  = ( min( $lats ) + ( ( max( $lats ) - min( $lats ) ) / 2 ) );
		$center_long = ( min( $longs ) + ( ( max( $longs ) - min( $longs ) ) / 2 ) );
	}
	else {
		$center_lat  = get_post_meta( $atts['center'], '_wpseo_coordinates_lat', true );
		$center_long = get_post_meta( $atts['center'], '_wpseo_coordinates_long', true );
	}

	// Default to zoom 10 if there's only one location as a center + bounds would zoom in far too much.
	if ( $atts['zoom'] == -1 && count( $location_array ) === 1 ) {
		$atts['zoom'] = 10;
	}

	$marker_clustering = ( $atts['marker_clustering'] ) ? 1 : '';

	if ( count( $location_array_str ) > 0 ) {
		if ( $map_counter === 0 ) {
			$wpseo_map .= '<script type="text/javascript">
				window.wpseoMapOptions = {};
			</script>' . PHP_EOL;
		}
		$wpseo_map .= '<script type="text/javascript">
			wpseoMapOptions[ ' . $map_counter . ' ] = {
				location_data: [],
				mapVars: [
					' . WPSEO_Utils::format_json_encode( $center_lat ) . ',
					' . WPSEO_Utils::format_json_encode( $center_long ) . ',
					' . (int) $atts['zoom'] . ',
					' . WPSEO_Utils::format_json_encode( $atts['map_style'] ) . ',
					"' . (int) $atts['scrollable'] . '",
					"' . (int) $atts['draggable'] . '",
					"' . (int) $atts['default_show_infowindow'] . '",
					"' . is_admin() . '",
					"' . $marker_clustering . '",
				],
				directionVars: [
					"' . (int) $atts['show_route'] . '",
				],
			};' . PHP_EOL;
		foreach ( $location_array_str as $location ) {
			$wpseo_map .= "wpseoMapOptions[$map_counter].location_data.push($location);" . PHP_EOL;
		}

		$wpseo_map .= '</script>' . PHP_EOL;

		// Override(reset) the setting for images inside the map.
		$map .= '<div id="map_canvas' . ( ( $map_counter !== 0 ) ? '_' . $map_counter : '' ) . '" class="wpseo-map-canvas" style="max-width: 100%; width: ' . esc_attr( $atts['width'] ) . 'px; height: ' . esc_attr( $atts['height'] ) . 'px;">' . $noscript_output . '</div>';

		$route_tag   = apply_filters( 'wpseo_local_location_route_title_name', 'h3' );
		$route_label = apply_filters( 'wpseo_local_location_route_label', __( 'Route', 'yoast-local-seo' ) );

		/**
		 * Show the route planner. Only do so when 'show_route' is set to true and the number of locations is equal to 1.
		 * Also show it when it's the store locator.
		 */
		if ( $atts['show_route'] && ( count( $locations ) === 1 || $atts['from_sl'] === true ) ) {
			$location = reset( $locations );
			$map     .= '<div id="wpseo-directions-wrapper"' . ( ( $atts['from_sl'] ) ? ' style="display: none;"' : '' ) . '>';
			if ( ! empty( $route_label ) ) {
				$map .= '<' . esc_html( $route_tag ) . ' id="wpseo-directions" class="wpseo-directions-heading">' . $route_label . '</' . esc_html( $route_tag ) . '>';
			}
			$map .= '<form action="" method="post" class="wpseo-directions-form" id="wpseo-directions-form' . ( ( $map_counter !== 0 ) ? '_' . $map_counter : '' ) . '" onsubmit="wpseo_calculate_route( wpseoMapOptions[' . $map_counter . '].map, wpseoMapOptions[' . $map_counter . '].directionsDisplay, wpseoMapOptions[' . $map_counter . '].mapVars[0], wpseoMapOptions[' . $map_counter . '].mapVars[1], ' . $map_counter . '); return false;">';
			$map .= '<p>';
			$map .= __( 'Your location', 'yoast-local-seo' ) . ': <input type="text" size="20" id="origin' . ( ( $map_counter !== 0 ) ? '_' . $map_counter : '' ) . '" value="' . ( ! empty( $_REQUEST['wpseo-sl-search'] ) ? esc_attr( $_REQUEST['wpseo-sl-search'] ) : '' ) . '" />';
			// Show icon for retrieving current location.
			if ( wpseo_may_use_current_location() === true ) {
				$map .= ' <a href="javascript:" class="wpseo_use_current_location" data-target="origin' . ( ( $map_counter !== 0 ) ? '_' . $map_counter : '' ) . '"><img src="' . plugins_url( 'images/location-icon.svg', WPSEO_LOCAL_FILE ) . '" class="wpseo_use_current_location_image" height="24" width="24" alt="' . __( 'Use my current location', 'yoast-local-seo' ) . '" data-loading-text="' . __( 'Determining current location', 'yoast-local-seo' ) . '"></a> ';
				$map .= '<br>';
			}
			$map .= '<input type="submit" class="wpseo-directions-submit" value="' . esc_attr( $atts['show_route_label'] ) . '">';
			$map .= '<span id="wpseo-noroute" style="display: none;">' . __( 'No route could be calculated.', 'yoast-local-seo' ) . '</span>';
			$map .= '</p>';
			$map .= '</form>';
			$map .= '<div id="directions' . ( ( $map_counter !== 0 ) ? '_' . $map_counter : '' ) . '"></div>';
			$map .= '</div>';
		}

		// Show the filter if categories are set, there's more than 1 and if the filter is enabled.
		if ( isset( $all_categories ) && count( $all_categories ) > 1 && $atts['show_category_filter'] ) {
			$map .= '<select id="filter-by-location-category-' . $map_counter . '" class="location-category-filter" onchange="filterMarkers(this.value, ' . $map_counter . ')">';
			$map .= '<option value="">' . __( 'All categories', 'yoast-local-seo' ) . '</option>';
			foreach ( $all_categories as $category_slug => $category_name ) {
				$map .= '<option value="' . $category_slug . '">' . $category_name . '</option>';
			}
			$map .= '</select>';
		}
	}

	if ( $atts['echo'] ) {
		echo $map;
	}

	return $map;
}

/**
 * Opening hours shortcode handler, for not breaking backwards compatibility
 *
 * @param array $atts Array of shortcode attributes.
 *
 * @return string
 */
function wpseo_local_show_openinghours_shortcode_cb( $atts ) {
	return wpseo_local_show_opening_hours( $atts );
}

/**
 * Function for displaying opening hours
 *
 * @since 0.1
 *
 * @param array $atts       Array of shortcode parameters.
 * @param bool  $standalone Whether the opening hours are used stand alone or part of another function (like address).
 *
 * @return string|false Opening hours HTML display string or an empty string if the location is unclear
 *                      or false when opening hours are hidden.
 */
function wpseo_local_show_opening_hours( $atts, $standalone = true ) {
	$opening_hours_repo = new WPSEO_Local_Opening_Hours_Repository(
		new Options_Repository()
	);
	$defaults           = [
		'id'              => '',
		'term_id'         => '',
		'hide_closed'     => false,
		'echo'            => false,
		'comment'         => '',
		'show_days'       => array_keys( $opening_hours_repo->get_days() ),
		'show_open_label' => false,
	];
	$atts               = wpseo_check_falses( shortcode_atts( $defaults, $atts, 'wpseo_local_opening_hours' ) );

	// Bail if no current location is chosen when using multiple location setup.
	if ( wpseo_has_multiple_locations() && empty( $atts['id'] ) ) {
		return '';
	}

	$options        = get_option( 'wpseo_local' );
	$open_24h_label = ( ! empty( $options['open_24h_label'] ) ? $options['open_24h_label'] : __( 'Open 24 hours', 'yoast-local-seo' ) );
	$open_247_label = ( ! empty( $options['open_247_label'] ) ? $options['open_247_label'] : __( 'Open 24/7', 'yoast-local-seo' ) );

	if ( isset( $options['hide_opening_hours'] ) && $options['hide_opening_hours'] === 'on' ) {
		return false;
	}

	// Initiate the Locations repository and get query the locations.
	$locations_repository_builder = new Locations_Repository_Builder();
	$repo                         = $locations_repository_builder->get_locations_repository();
	$filter_args                  = [
		'id'          => explode( ',', $atts['id'] ),
		'category_id' => $atts['term_id'],
	];
	$locations                    = $repo->get( $filter_args );
	$container_id                 = 'wpseo-opening-hours-' . esc_attr( $atts['id'] );
	$output                       = '';
	foreach ( $locations as $location ) {
		if ( $location['business_type'] == '' ) {
			$location['business_type'] = 'LocalBusiness';
		}
		// Output meta tags with required address information when using this as stand alone.
		if ( $standalone === true ) {
			$output .= '<div class="wpseo-opening-hours-wrapper">';
		}
		$output .= '<table class="wpseo-opening-hours" id ="' . $container_id . '">';

		// Check if the location is open 24/7.
		if ( yoast_is_open_247( $location['post_id'], isset( $options['open_247'] ) && $options['open_247'] === 'on' ) ) {
			$output .= '<tr>';
			$output .= '<td>' . $open_247_label . '</td>';
			$output .= '</tr>';
		}
		else {
			$days = $opening_hours_repo->get_days();

			$timezone_repository = new Timezone_Repository();
			$timezone_repository->initialize();
			$location_datetime = $timezone_repository->get_location_datetime( $location['post_id'] );
			$format_24h        = yoast_should_use_24h_format( $location['post_id'], $location['format_24h'] );

			if ( ! is_array( $atts['show_days'] ) ) {
				$show_days = explode( ',', $atts['show_days'] );
			}
			else {
				$show_days = (array) $atts['show_days'];
			}

			// Loop through the days array where start_of_week is the first key, with a max of 7.
			if ( ! $show_days == 0 ) {
				foreach ( $days as $key => $day ) {

					// Check if the opening hours for this     day should be shown.
					if ( is_array( $show_days ) && ! empty( $show_days ) && ! in_array( $key, $show_days, true ) ) {
						continue;
					}

					$oh_post_id    = ( wpseo_has_multiple_locations() === true ) ? $location['post_id'] : 'options';
					$opening_hours = $opening_hours_repo->get_opening_hours( $key, $oh_post_id, $options, $format_24h );

					// Skip when it's closed on this day.
					if ( ( $opening_hours['value_from'] === 'closed' || $opening_hours['value_to'] === 'closed' ) && $atts['hide_closed'] ) {
						continue;
					}

					$output .= '<tr>';
					$output .= '<td class="day">' . $day . '</td>';
					$output .= '<td class="time">';

					$output_time = '';
					if ( $opening_hours['value_from'] !== 'closed' && $opening_hours['value_to'] !== 'closed' && $opening_hours['open_24h'] !== 'on' ) {
						$output_time .= '<span>' . $opening_hours['value_from_formatted'] . ' - ' . $opening_hours['value_to_formatted'] . '</span>';
					}
					elseif ( $opening_hours['open_24h'] === 'on' ) {
						$output_time .= '<span>' . ( $open_24h_label ) . '</span>';
					}
					else {
						$output_time .= ( ! empty( $options['closed_label'] ) ? $options['closed_label'] : __( 'Closed', 'yoast-local-seo' ) );
					}

					if ( $opening_hours['use_multiple_times'] && $opening_hours['open_24h'] !== 'on' ) {
						if ( $opening_hours['value_from'] !== 'closed' && $opening_hours['value_to'] !== 'closed' && $opening_hours['value_second_from'] !== 'closed' && $opening_hours['value_second_to'] !== 'closed' ) {
							$output_time .= '<span class="openingHoursAnd"> ' . __( 'and', 'yoast-local-seo' ) . ' </span> ';
							$output_time .= '<span>' . $opening_hours['value_second_from_formatted'] . ' - ' . $opening_hours['value_second_to_formatted'] . '</span>';
						}
					}

					$output_time         = apply_filters( 'wpseo_opening_hours_time', $output_time, $day, $opening_hours['value_from'], $opening_hours['value_to'], $atts );
					$show_open_now_label = apply_filters( 'wpseo_local_show_open_now_label', $atts['show_open_label'] );
					$location_open       = $timezone_repository->is_location_open( $location['post_id'] );

					$output .= $output_time;

					if ( ! empty( $location_datetime ) && $key === strtolower( $location_datetime->format( 'l' ) ) && ( ! is_wp_error( $location_open ) && ! empty( $location_open ) ) && $show_open_now_label ) {
						$output .= ' <strong>' . __( 'Open now', 'yoast-local-seo' ) . '</strong>';
					}

					$output .= '</td>';
					$output .= '</tr>';
				}
			}
		}

		$output .= '</table>';

		if ( $standalone === true ) {
			$output .= '</div>'; // .wpseo-opening-hours-wrapper
		}

		if ( $atts['comment'] != '' ) {
			$output .= '<div class="wpseo-extra-comment">' . wpautop( html_entity_decode( $atts['comment'], ENT_COMPAT, get_bloginfo( 'charset' ) ) ) . '</div>';
		}
	}

	// Add filter to add optional output.
	if ( $standalone !== false ) {
		$output = apply_filters( 'wpseo_show_opening_hours_after', $output, $atts['id'], $container_id );
	}

	if ( $atts['echo'] ) {
		echo $output;
	}

	return $output;
}

/**
 * Checks whether website uses multiple location (Custom Post Type) or not (info from options).
 *
 * @return bool Multiple locations enabled or not.
 */
function wpseo_has_multiple_locations() {
	$options = get_option( 'wpseo_local' );

	return isset( $options['use_multiple_locations'] ) && $options['use_multiple_locations'] === 'on';
}

/**
 * Checks whether website uses shared business info properties.
 *
 * Shared business info properties are in use when:
 * - website uses multiple locations (Custom Post Type)
 * - locations are all in the same organization
 * - the option "Locations inherit shared business info" is set to Yes
 *
 * @return bool Whether website uses shared business info properties.
 */
function wpseo_may_use_multiple_locations_shared_business_info() {
	$options = get_option( 'wpseo_local' );

	if ( ! wpseo_has_multiple_locations() ) {
		return false;
	}

	if ( wpseo_multiple_location_one_organization() == false ) {
		return false;
	}

	return isset( $options['multiple_locations_shared_business_info'] ) && $options['multiple_locations_shared_business_info'] === 'on';
}

/**
 * Checks whether website uses shared opening hours properties.
 *
 * Shared opening hours properties are in use when:
 * - website uses multiple locations (Custom Post Type)
 * - locations are all in the same organization
 * - the option "Locations inherit shared opening hours" is set to Yes
 *
 * @return bool Whether website uses shared opening hours properties.
 */
function wpseo_may_use_multiple_locations_shared_opening_hours() {
	$options = get_option( 'wpseo_local' );

	if ( ! wpseo_has_multiple_locations() ) {
		return false;
	}

	if ( wpseo_multiple_location_one_organization() == false ) {
		return false;
	}

	return isset( $options['multiple_locations_shared_opening_hours'] ) && $options['multiple_locations_shared_opening_hours'] === 'on';
}

/**
 * Checks whether website uses multiple location (Custom Post Type) or not (info from options) and they're all in the
 * same organization.
 *
 * @return bool Multiple locations, same organization enabled or not.
 */
function wpseo_multiple_location_one_organization() {
	$options = get_option( 'wpseo_local' );

	if ( isset( $options['use_multiple_locations'] ) && $options['use_multiple_locations'] === 'on' ) {
		return isset( $options['multiple_locations_same_organization'] ) && $options['multiple_locations_same_organization'] === 'on';
	}

	return false;
}

/**
 * Checks whether website has primary locations enabled and has a primary location set.
 *
 * @return bool
 */
function wpseo_has_primary_location() {
	$location_id = WPSEO_Options::get( 'multiple_locations_primary_location' );
	$active      = false;
	if ( $location_id ) {
		$location = get_post( $location_id );
		$active   = $location->post_status === 'publish';
	}
	return ( wpseo_multiple_location_one_organization() && $location_id && $active );
}

/**
 * Checks whether website has primary locations enabled, has a primary location set and checks what page we are on.
 *
 * @return bool
 */
function wpseo_has_usable_primary_location() {
	return ( wpseo_has_primary_location() && ! is_singular( 'wpseo_locations' ) );
}

/**
 * Checks whether website has only one published location item.
 *
 * @return bool
 */
function wpseo_has_location_acting_as_primary() {
	if ( wpseo_multiple_location_one_organization() ) {
		$locations_repository_builder = new Locations_Repository_Builder();
		$repo                         = $locations_repository_builder->get_locations_repository();
		$locations                    = $repo->get( [ 'post_status' => 'publish' ], false );

		return ( count( $locations ) === 1 );
	}

	return false;
}

/**
 * Check whether the usage of current location for Map routes is allowed.
 *
 * @return bool Is allowed to use current location or not.
 */
function wpseo_may_use_current_location() {
	$options = get_option( 'wpseo_local' );

	return isset( $options['detect_location'] ) && $options['detect_location'] === 'on';
}

/**
 * Determines whether the current location is identical to the primary location set.
 *
 * @return bool Is current page identical to primary location or not.
 */
function wpseo_is_current_location_identical_to_primary() {
	$locations_repository_builder = new Locations_Repository_Builder();
	$repository                   = $locations_repository_builder->get_locations_repository();
	$place_data                   = $repository->for_current_page();
	if ( wpseo_has_primary_location() ) {
		return WPSEO_Options::get( 'multiple_locations_primary_location' ) == $place_data->post_id;
	}
	elseif ( wpseo_has_location_acting_as_primary() ) {
		$location = $repository->get( [ 'post_status' => 'publish' ], false );
		$id       = reset( $location );

		return $id == $place_data->post_id;
	}

	return false;
}

/**
 * Determines whether the schema will contain a branch organization.
 *
 * @param bool $is_company True if environment is for a company.
 *
 * @return bool Will schema contain branch organization
 */
function wpseo_schema_will_have_branch_organization( $is_company = false ) {
	$post_type_instance = new PostType();
	$post_type_instance->initialize();

	return ( $is_company && ( is_singular( $post_type_instance->get_post_type() ) && ! wpseo_is_current_location_identical_to_primary() ) );
}

/**
 * @param bool   $use_24h  True if time should be displayed in 24 hours. False if time should be displayed in AM/PM
 *                         mode.
 * @param string $selected Optional. Selected time for dropdown.
 *                         Defaults to "09:00".
 *
 * @return string Complete dropdown with all options.
 */
function wpseo_show_hour_options( $use_24h = false, $selected = '09:00' ) {
	$options = get_option( 'wpseo_local' );
	$output  = '<option value="closed">';
	$output .= ( ! empty( $options['closed_label'] ) ? esc_html( $options['closed_label'] ) : esc_html__( 'Closed', 'yoast-local-seo' ) );
	$output .= '</option>';

	/*
	 * These are hard-coded times not affected by timezone. Using gmdate()
	 * would _make_ them affected by timezone, which would make the list start at
	 * the local version of GMT midnight, instead of the "local" midnight time,
	 * so using date() is correct in this case.
	 *
	 * @phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date
	 */
	for ( $i = 0; $i < 24; $i++ ) {
		$time                = strtotime( sprintf( '%1$02d', $i ) . ':00' );
		$time_quarter        = strtotime( sprintf( '%1$02d', $i ) . ':15' );
		$time_half           = strtotime( sprintf( '%1$02d', $i ) . ':30' );
		$time_threequarters  = strtotime( sprintf( '%1$02d', $i ) . ':45' );
		$value               = date( 'H:i', $time );
		$value_quarter       = date( 'H:i', $time_quarter );
		$value_half          = date( 'H:i', $time_half );
		$value_threequarters = date( 'H:i', $time_threequarters );

		$time_12h_value               = date( 'g:i A', $time );
		$time_12h_quarter_value       = date( 'g:i A', $time_quarter );
		$time_12h_half_value          = date( 'g:i A', $time_half );
		$time_12h_threequarters_value = date( 'g:i A', $time_threequarters );

		$time_24h_value               = date( 'H:i', $time );
		$time_24h_quarter_value       = date( 'H:i', $time_quarter );
		$time_24h_half_value          = date( 'H:i', $time_half );
		$time_24h_threequarters_value = date( 'H:i', $time_threequarters );

		$text_time_value               = $time_12h_value;
		$text_time_quarter_value       = $time_12h_quarter_value;
		$text_time_half_value          = $time_12h_half_value;
		$text_time_threequarters_value = $time_12h_threequarters_value;

		if ( $use_24h ) {
			$text_time_value               = $time_24h_value;
			$text_time_quarter_value       = $time_24h_quarter_value;
			$text_time_half_value          = $time_24h_half_value;
			$text_time_threequarters_value = $time_24h_threequarters_value;
		}

		$output .= '<option value="' . esc_attr( $value ) . '"' . selected( $value, $selected, false ) . ' data-time-format-12-hours="' . esc_attr( $time_12h_value ) . '" data-time-format-24-hours="' . esc_attr( $time_24h_value ) . '">' . esc_html( $text_time_value ) . '</option>';
		$output .= '<option value="' . esc_attr( $value_quarter ) . '" ' . selected( $value_quarter, $selected, false ) . ' data-time-format-12-hours="' . esc_attr( $time_12h_quarter_value ) . '" data-time-format-24-hours="' . esc_attr( $time_24h_quarter_value ) . '">' . esc_html( $text_time_quarter_value ) . '</option>';
		$output .= '<option value="' . esc_attr( $value_half ) . '" ' . selected( $value_half, $selected, false ) . ' data-time-format-12-hours="' . esc_attr( $time_12h_half_value ) . '" data-time-format-24-hours="' . esc_attr( $time_24h_half_value ) . '">' . esc_html( $text_time_half_value ) . '</option>';
		$output .= '<option value="' . esc_attr( $value_threequarters ) . '" ' . selected( $value_threequarters, $selected, false ) . ' data-time-format-12-hours="' . esc_attr( $time_12h_threequarters_value ) . '" data-time-format-24-hours="' . esc_attr( $time_24h_threequarters_value ) . '">' . esc_html( $text_time_threequarters_value ) . '</option>';
	}

	// phpcs:enable WordPress.DateTime.RestrictedFunctions.date_date

	return $output;
}

/**
 * Checks whether array values are meant to mean false but aren't set to false.
 *
 * @param array|string $input Array or string to check.
 *
 * @return array|bool
 */
function wpseo_check_falses( $input ) {
	$atts = [];
	if ( ! is_array( $input ) ) {
		$atts[] = $input;
	}
	else {
		$atts = $input;
	}

	foreach ( $atts as $key => $value ) {
		if ( $value === 'false' || $value === 'off' || $value === 'no' || $value === '0' ) {
			$atts[ $key ] = false;
		}
		elseif ( $value === 'true' || $value === 'on' || $value === 'yes' || $value === '1' ) {
			$atts[ $key ] = true;
		}
	}

	if ( ! is_array( $input ) ) {
		return $atts[0];
	}

	return $atts;
}

/**
 * Places scripts in footer for Google Maps use.
 *
 * @return void
 */
function wpseo_enqueue_geocoder() {
	global $wpseo_map;

	$options         = get_option( 'wpseo_local' );
	$detect_location = isset( $options['detect_location'] ) && $options['detect_location'] === 'on';
	$default_country = ( $options['default_country'] ?? '' );
	if ( $default_country != '' ) {
		$default_country = WPSEO_Local_Frontend::get_country( $default_country );
	}

	// Load frontend scripts.
	$asset_manager = new WPSEO_Local_Admin_Assets();
	$asset_manager->register_assets();

	$asset_manager->enqueue_script( 'frontend' );

	$localization_data = [
		'ajaxurl'                   => 'admin-ajax.php',
		'adminurl'                  => admin_url(),
		'has_multiple_locations'    => wpseo_has_multiple_locations(),
		'unit_system'               => ! empty( $options['unit_system'] ) ? $options['unit_system'] : 'METRIC',
		'default_country'           => $default_country,
		'detect_location'           => $detect_location,
		'marker_cluster_image_path' => apply_filters(
			'wpseo_local_marker_cluster_image_path',
			esc_url( plugins_url( 'images/m', WPSEO_LOCAL_FILE ) )
		),
	];

	wp_localize_script(
		WPSEO_Local_Admin_Assets::PREFIX . 'frontend',
		'wpseo_local_data',
		$localization_data
	);

	echo '<style type="text/css">.wpseo-map-canvas img { max-width: none !important; }</style>' . PHP_EOL;

	echo $wpseo_map;
}

/**
 * This function will clean up the given string and remove all unwanted characters.
 *
 * @uses wpseo_unicode_to_utf8() to convert the unicode array back to a regular string.
 * @uses wpseo_utf8_to_unicode() to convert string to array of unicode characters.
 *
 * @param string $input_string String that has to be cleaned.
 *
 * @return string The clean string.
 */
function wpseo_cleanup_string( $input_string ) {
	$input_string = esc_attr( $input_string );

	// First generate array of all unicodes of this string.
	$unicode_array = wpseo_utf8_to_unicode( $input_string );
	foreach ( $unicode_array as $key => $unicode_item ) {
		// Remove unwanted unicode characters.
		if ( in_array( $unicode_item, [ 8232 ], true ) ) {
			unset( $unicode_array[ $key ] );
		}
	}

	// Revert back to normal string.
	$input_string = wpseo_unicode_to_utf8( $unicode_array );

	return $input_string;
}

/**
 * Converts a string to array of unicode characters.
 *
 * @param string $str String that has to be converted to unicde array.
 *
 * @return array Array of unicode characters.
 */
function wpseo_utf8_to_unicode( $str ) {
	$unicode     = [];
	$values      = [];
	$looking_for = 1;
	$strlen      = strlen( $str );

	for ( $i = 0; $i < $strlen; $i++ ) {
		$this_value = ord( $str[ $i ] );

		if ( $this_value < 128 ) {
			$unicode[] = $this_value;
		}
		else {
			if ( count( $values ) === 0 ) {
				$looking_for = ( $this_value < 224 ) ? 2 : 3;
			}

			$values[] = $this_value;
			if ( count( $values ) === $looking_for ) {
				$number = ( $looking_for === 3 ) ? ( ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ) ) : ( ( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 ) );

				$unicode[]   = $number;
				$values      = [];
				$looking_for = 1;
			}
		}
	}

	return $unicode;
}

/**
 * Converts unicode character array back to regular string.
 *
 * @param array $string_array Array of unicode characters.
 *
 * @return string Converted string.
 */
function wpseo_unicode_to_utf8( $string_array ) {
	$utf8 = '';

	foreach ( $string_array as $unicode ) {
		if ( $unicode < 128 ) {
			$utf8 .= chr( $unicode );
		}
		elseif ( $unicode < 2048 ) {
			$utf8 .= chr( 192 + ( ( $unicode - ( $unicode % 64 ) ) / 64 ) );
			$utf8 .= chr( 128 + ( $unicode % 64 ) );
		}
		else {
			$utf8 .= chr( 224 + ( ( $unicode - ( $unicode % 4096 ) ) / 4096 ) );
			$utf8 .= chr( 128 + ( ( ( $unicode % 4096 ) - ( $unicode % 64 ) ) / 64 ) );
			$utf8 .= chr( 128 + ( $unicode % 64 ) );
		}
	}

	return $utf8;
}

/**
 * Run the upgrade procedures.
 *
 * @param array $options Options from database to check with.
 *
 * @return void
 */
function wpseo_local_do_upgrade( $options ) {

	if ( ! is_array( $options ) ) {
		return;
	}

	$db_version = WPSEO_Local_Core::get_db_version( $options );

	if ( version_compare( $db_version, '1.3.1', '<' ) ) {
		$options_to_convert = [
			'use_multiple_locations',
			'opening_hours_24h',
			'multiple_opening_hours',
		];

		// Convert checkbox values from "1" to "on".
		foreach ( $options as $key => $value ) {
			if ( ! in_array( $key, $options_to_convert, true ) ) {
				continue;
			}

			if ( $value == '1' ) {
				WPSEO_Options::set( $key, 'on' );
			}
		}
	}

	if ( version_compare( $db_version, '3.4', '<=' ) ) {
		// Update businesstypes from Attorneys to LegalServices if upgrading from version 3.4 or below.
		yoast_wpseo_local_update_business_type();
	}
	if ( version_compare( $db_version, '11.0', '<' ) ) {
		if ( class_exists( 'Yoast_Notification_Center' ) ) {
			$notification_center = Yoast_Notification_Center::get();
			$notification        = $notification_center->get_notification_by_id( 'PersonOrCompanySettingError' );
			if ( $notification instanceof Yoast_Notification ) {
				$notification_center->remove_notification( $notification );
			}
		}
	}
	if ( version_compare( $db_version, '11.9', '<' ) ) {
		if ( class_exists( 'Yoast_Notification_Center' ) ) {
			$notification_center = Yoast_Notification_Center::get();
			$notification        = $notification_center->get_notification_by_id( 'LocalSEOServerKey' );
			if ( $notification instanceof Yoast_Notification ) {
				$notification_center->remove_notification( $notification );
			}
		}
	}

	if ( version_compare( $db_version, '12.1.1', '<' ) ) {
		// In some situations a wrong value was stored into the database, which is being cleaned here.
		if ( isset( $options['location_timezone'] ) && is_wp_error( $options['location_timezone'] ) === true ) {
			WPSEO_Options::set( 'location_timezone', '' );
		}
	}

	if ( version_compare( $db_version, '12.8', '<' ) ) {
		if ( class_exists( 'woocommerce' ) ) {
			$local_pickup_settings = get_option( 'woocommerce_yoast_wcseo_local_pickup_settings' );

			if ( isset( $local_pickup_settings['enabled'] ) ) {
				$value = $local_pickup_settings['enabled'];

				if ( $local_pickup_settings['enabled'] === 'yes' ) {
					$value = 0;
					foreach ( $local_pickup_settings['location_specific'] as $location ) {
						if ( isset( $location['allowed'] ) && $location['allowed'] === 'yes' ) {
							++$value;
						}
					}
				}

				WPSEO_Options::set( 'woocommerce_local_pickup_setting', $value );
			}
		}
	}

	/**
	 * Several options are being prefixed to prevent ambiguity.
	 *
	 * @since 12.3
	 */
	if ( version_compare( $db_version, '12.3', '<' ) ) {
		$api_key_browser = WPSEO_Options::get( 'api_key_browser' );
		$api_key_server  = WPSEO_Options::get( 'api_key' );
		$custom_marker   = WPSEO_Options::get( 'custom_marker' );
		$enhanced_search = WPSEO_Options::get( 'enhanced_search' );

		if ( ! empty( $api_key_browser ) ) {
			WPSEO_Options::set( 'local_api_key_browser', $api_key_browser );
		}

		if ( ! empty( $api_key_server ) ) {
			WPSEO_Options::set( 'local_api_key', $api_key_server );
		}

		if ( ! empty( $custom_marker ) ) {
			WPSEO_Options::set( 'local_custom_marker', $custom_marker );
		}

		if ( ! empty( $enhanced_search ) ) {
			WPSEO_Options::set( 'local_enhanced_search', $enhanced_search );
		}
	}

	/**
	 * We need to reindex our location URL's.
	 */
	if ( version_compare( $db_version, '13.1', '<' ) ) {
		if ( ! wpseo_has_multiple_locations() ) {
			return;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- YoastSEO Free hook.
		$shutdown_limit = apply_filters( 'wpseo_shutdown_indexation_limit', 25 );

		$indexables_repository = YoastSEO()->classes->get( Indexable_Repository::class );
		$indexed_locations     = $indexables_repository->query()
			->where( 'object_type', 'post' )
			->where( 'object_sub_type', 'wpseo_locations' )
			->limit( $shutdown_limit + 1 )
			->find_many();
		$indexed_terms         = $indexables_repository->query()
			->where( 'object_type', 'term' )
			->where( 'object_sub_type', 'wpseo_locations_category' )
			->limit( $shutdown_limit + 1 )
			->find_many();

		/**
		 * Check the locations.
		 */
		if ( count( $indexed_locations ) <= $shutdown_limit ) {
			foreach ( $indexed_locations as $location ) {
				$location->permalink = get_permalink( $location->object_id );
				$location->save();
			}
		}

		if ( count( $indexed_locations ) > $shutdown_limit ) {
			/**
			 * Represents the Indexable_Permalink_Watcher.
			 *
			 * @var Indexable_Permalink_Watcher $watcher
			 */
			$watcher = YoastSEO()->classes->get( Indexable_Permalink_Watcher::class );
			$watcher->reset_permalinks_post_type( 'wpseo_locations' );
		}

		/**
		 * Check the WPSEO Location taxonomy terms.
		 */
		if ( count( $indexed_terms ) <= $shutdown_limit ) {
			foreach ( $indexed_terms as $term ) {
				$term->permalink = get_term_link( $term->object_id );
				$term->save();
			}
		}

		if ( count( $indexed_terms ) > $shutdown_limit ) {
			/**
			 * Represents the Indexable_Helper.
			 *
			 * @var Indexable_Helper $helper
			 */
			$helper = YoastSEO()->classes->get( Indexable_Helper::class );
			$helper->reset_permalink_indexables( 'term', 'wpseo_locations_category' );
		}
	}

	if ( version_compare( $db_version, '15.1', '<' ) ) {
		require_once ABSPATH . 'wp-admin/includes/admin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$upgrader       = new WP_Upgrader();
		$upgrader->skin = new Automatic_Upgrader_Skin();
		Language_Pack_Upgrader::async_upgrade( $upgrader );
	}
}

/**
 * Retrieves excerpt from specific post.
 *
 * @param int $post_id The post ID of which the excerpt should be retrieved.
 *
 * @return string
 *
 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Variable gets overruled, but reset straight after.
 */
function wpseo_local_get_excerpt( $post_id ) {
	global $post;

	$original_post = $post;
	$post          = get_post( $post_id );
	setup_postdata( $post );

	$output = get_the_excerpt();

	// Set back original $post;.
	$post = $original_post;
	wp_reset_postdata();

	// phpcs:enable

	return $output;
}

/**
 * Create an upload field for an image
 *
 * @return string
 */
function wpseo_local_upload_image() {

	$output = '';

	$output = '<p class="desc label" style="border:none; margin-bottom: 0;">' . __( 'If you want the map to display a custom marker pin for your locations, please upload it here.', 'yoast-local-seo' ) . '</p>';

	$output .= '<label for="upload_image">';
	$output .= '<input id="upload_image" type="text" size="36" name="ad_image" value="http://" /> ';
	$output .= '<input id="upload_image_button" class="button" type="button" value="Upload Image" />';
	$output .= '<br />Enter a URL or upload an image';
	$output .= '</label>';
	$output .= '<br class="clear"/>';

	return $output;
}

/**
 * @param string $value The value of the Business types array.
 *
 * @return void
 */
function wpseo_local_sanitize_business_types( &$value ) {
	$value = str_replace( '&mdash;', '', $value );
	$value = trim( $value );
}

/**
 * @param array $atts Attributes array for the logo shortcode.
 *
 * @return string
 */
function wpseo_local_show_logo( $atts ) {
	$defaults = [
		'id' => get_the_ID(),
	];
	$atts     = wpseo_check_falses( shortcode_atts( $defaults, $atts ) );

	$post_type_instance = new PostType();
	$post_type_instance->initialize();
	$post_type = $post_type_instance->get_post_type();

	$output = '';

	if ( get_post_type( $atts['id'] ) !== $post_type ) {
		return '';
	}

	$location_logo = get_post_meta( $atts['id'], '_wpseo_business_location_logo', true );

	if ( $location_logo === '' ) {
		$wpseo_options = get_option( 'wpseo' );
		$location_logo = $wpseo_options['company_logo'];
	}

	if ( $location_logo !== '' ) {
		$output = '<img src="' . esc_url( $location_logo ) . '" alt="' . esc_attr( get_post_meta( yoast_wpseo_local_get_attachment_id_from_src( $location_logo ), '_wp_attachment_image_alt', true ) ) . '"/>';
	}

	if ( ! empty( $output ) ) {
		return $output;
	}
}

/**
 * Return the ID of an image by src.
 *
 * @param string $src The image src.
 *
 * @return string|null ID.
 */
function yoast_wpseo_local_get_attachment_id_from_src( $src ) {
	global $wpdb;

	return $wpdb->get_var(
		$wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid = %s", [ $src ] )
	);
}

/**
 * Update business type from Attorney to Legal Service
 *
 * @return void
 */
function yoast_wpseo_local_update_business_type() {
	if ( wpseo_has_multiple_locations() ) {
		$post_type_instance = new PostType();
		$post_type_instance->initialize();

		$locations_args = [
			'post_type'  => $post_type_instance->get_post_type(),
			'nopaging'   => true,
			'meta_query' => [
				[
					'key'     => '_wpseo_business_type',
					'value'   => 'Attorney',
					'compare' => '=',
				],
			],
		];

		$locations = new WP_Query( $locations_args );

		if ( $locations->have_posts() ) {
			while ( $locations->have_posts() ) {
				$locations->the_post();
				update_post_meta( get_the_ID(), '_wpseo_business_type', 'LegalService' );
			}
		}
	}
	else {
		$options = get_option( 'wpseo_local' );
		if ( isset( $options['business_type'] ) && $options['business_type'] === 'Attorney' ) {
			$options['business_type'] = 'LegalService';

			update_option( 'wpseo_local', $options );
		}
	}
}

/**
 * Wrapper function to check whether a location is currently open or closed.
 *
 * @since 4.2
 *
 * @param WP_Post|int|null $post A post ID.
 *
 * @return bool|WP_Error
 */
function yoast_seo_local_is_location_open( $post = null ) {
	$timezone_repository = new Timezone_Repository();
	$timezone_repository->initialize();

	return $timezone_repository->is_location_open( $post );
}

/**
 * Flattens a version number for use in a filename
 *
 * @param string $version The original version number.
 *
 * @return string The flattened version number.
 */
function yoast_seo_local_flatten_version( $version ) {
	$parts = explode( '.', $version );

	if ( count( $parts ) === 2 && preg_match( '/^\d+$/', $parts[1] ) === 1 ) {
		$parts[] = '0';
	}

	return implode( '', $parts );
}

/**
 * Fill an array with dummy address data.
 *
 * @return array
 */
function yoast_seo_local_dummy_address() {
	return [
		'business_name'      => esc_html_x( 'Your company', 'The company name for use in the dummy address in the block preview', 'yoast-local-seo' ),
		'business_address'   => esc_html_x( 'Streetname 1', 'The street name for use in the dummy address in the block preview', 'yoast-local-seo' ),
		'business_address_2' => '',
		'business_zipcode'   => esc_html_x( '1234', 'The postal code for use in the dummy address in the block preview', 'yoast-local-seo' ),
		'business_city'      => esc_html_x( 'Your city', 'The city name for use in the dummy address in the block preview', 'yoast-local-seo' ),
		'business_state'     => '',
		'business_phone'     => esc_html_x( '1234567890', 'The phone number for use in the dummy address in the block preview', 'yoast-local-seo' ),
		'business_email'     => esc_html_x( 'info@youremail.com', 'The email address for use in the dummy address in the block preview', 'yoast-local-seo' ),
	];
}

/**
 * Determines whether to use the 24h format.
 *
 * @param int  $location_id         The location ID to get the meta for.
 * @param bool $format_option_value The 24h format option's value.
 *
 * @return bool Whether to use the 24h format.
 */
function yoast_should_use_24h_format( $location_id, $format_option_value ) {

	if ( $location_id === '' ) {
		return ( $format_option_value === 'on' );
	}

	$use_24h       = get_post_meta( $location_id, '_wpseo_format_24h', true ) === 'on';
	$is_overridden = get_post_meta( $location_id, '_wpseo_format_24h_override', true ) === 'on';

	// Default to the meta value when on a single location.
	if ( ! wpseo_may_use_multiple_locations_shared_opening_hours() ) {
		return $use_24h;
	}

	if ( ! $is_overridden ) {
		return $format_option_value;
	}

	return $use_24h;
}

/**
 * Determines whether this location is open 24/7.
 *
 * @param int  $location_id     The location ID to get the meta for.
 * @param bool $open_247_option The 24/7 open option's value.
 *
 * @return bool Whether this location is open 24/7.
 */
function yoast_is_open_247( $location_id, $open_247_option ) {

	if ( $location_id === '' ) {
		return $open_247_option;
	}

	$open_247      = get_post_meta( $location_id, '_wpseo_open_247', true ) === 'on';
	$is_overridden = get_post_meta( $location_id, '_wpseo_open_247_override', true ) === 'on';

	// Default to the meta value when on a single location.
	if ( ! wpseo_may_use_multiple_locations_shared_opening_hours() ) {
		return $open_247;
	}

	if ( ! $is_overridden ) {
		return $open_247_option;
	}

	return $open_247;
}
