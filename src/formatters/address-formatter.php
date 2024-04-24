<?php

namespace Yoast\WP\Local\Formatters;

if ( ! \class_exists( Address_Formatter::class ) ) {

	/**
	 * Class Address_Formatter
	 *
	 * This class handles the formatting of address output.
	 */
	class Address_Formatter {

		/**
		 * Possible address formats.
		 *
		 * @var array
		 */
		protected $address_formats = [
			'',
			'address-state-postal',
			'address-state-postal-comma',
			'address-postal-city-state',
			'address-postal',
			'address-postal-comma',
			'address-city',
		];

		/**
		 * Subset of possible address formats with state field.
		 *
		 * @var array
		 */
		protected $address_formats_with_state = [
			'address-state-postal',
			'address-state-postal-comma',
			'address-postal-city-state',
		];

		/**
		 * Subset of possible address formats without zipcode.
		 *
		 * @var array
		 */
		protected $address_formats_no_zip = [
			'address-postal-city-state',
			'address-city',
		];

		/**
		 * Generates output for formatted address.
		 *
		 * @param string $address_format  Address format from the options for the current address.
		 * @param array  $address_details Array with all location data.
		 *
		 * @return string
		 */
		public function get_address_format( $address_format, $address_details ) {
			$output = '';

			$show_logo          = ( $address_details['show_logo'] ?? false );
			$hide_business_name = ( $address_details['hide_business_name'] ?? '' );
			$business_address   = $address_details['business_address'];
			$business_address_2 = ( $address_details['business_address_2'] ?? '' );
			$oneline            = $address_details['oneline'];
			$business_zipcode   = $address_details['business_zipcode'];
			$business_city      = $address_details['business_city'];
			$business_state     = $address_details['business_state'];
			$show_state         = $address_details['show_state'];
			$escape_output      = $address_details['escape_output'];
			$use_tags           = $address_details['use_tags'];

			$tag_name = ( $oneline ) ? 'span' : 'div';

			$business_city_string = $business_city;
			if ( $use_tags ) {
				$business_city_string = '<span class="locality"> ' . \esc_html( $business_city ) . '</span>';
			}

			$business_state_string = $business_state;
			if ( $use_tags ) {
				$business_state_string = '<span  class="region">' . \esc_html( $business_state ) . '</span>';
			}

			$business_zipcode_string = $business_zipcode;
			if ( $use_tags ) {
				$business_zipcode_string = '<span class="postal-code">' . \esc_html( $business_zipcode ) . '</span>';
			}

			if ( \in_array( $address_format, $this->address_formats, true ) ) {
				if ( ! empty( $business_address ) ) {
					$output .= ( ( $oneline && ! $show_logo && ! $hide_business_name ) ? ', ' : '' );

					if ( $use_tags ) {
						$output .= '<' . $tag_name . ' class="street-address">';
						$output .= \esc_html( $business_address );
						if ( ! empty( $business_address_2 ) ) {
							$output .= ( ( $oneline ) ? ', ' : '<br>' ) . \esc_html( $business_address_2 ) . ', ';
						}
						$output .= '</' . $tag_name . '>';
					}
					else {
						$output .= \esc_html( $business_address ) . ' ';
						if ( ! empty( $business_address_2 ) ) {
							$output .= ( ( $oneline ) ? ', ' : '<br>' ) . \esc_html( $business_address_2 ) . ', ';
						}
					}
				}

				if ( $address_format === 'address-postal-city-state' && ! empty( $business_zipcode ) ) {
					$output .= ( ( $oneline ) ? ', ' : '' );
					$output .= $business_zipcode_string;
				}

				if ( ! empty( $business_city ) ) {
					$output .= ( ( $oneline ) ? ', ' : '' );
					$output .= $business_city_string;

					if ( \in_array( $address_format, $this->address_formats_with_state, true ) ) {
						if ( $show_state === true && ! empty( $business_state ) ) {
							$output .= ',';
						}
					}
				}

				if ( \in_array( $address_format, $this->address_formats_with_state, true ) ) {
					if ( $show_state && ! empty( $business_state ) ) {
						$output .= ' ' . $business_state_string;
					}
				}

				if ( ! empty( $business_zipcode_string )
					&& ! \in_array( $address_format, $this->address_formats_no_zip, true )
				) {
					$output .= ' ' . $business_zipcode_string;
				}
			}
			else {
				if ( ! empty( $business_zipcode ) ) {
					$output .= ( ( $oneline ) ? ', ' : '' );
					$output .= $business_zipcode_string;
				}
				if ( $show_state && ! empty( $business_state ) ) {
					$output .= ( ( $oneline ) ? ', ' : ' ' );
					$output .= $business_state_string;
				}
				if ( ! empty( $business_city ) ) {
					$output .= ( ( $oneline ) ? ' ' : '' );
					$output .= $business_city_string;
				}

				if ( ! empty( $business_address ) ) {
					$output .= ( ( $oneline ) ? ', ' : '' );

					if ( $use_tags ) {
						$output .= '<' . $tag_name . ' class="street-address">' . \esc_html( $business_address ) . '</' . $tag_name . '>';
					}
					else {
						$output .= \esc_html( $business_address );
					}
				}

				if ( ! empty( $business_address_2 ) ) {
					$output .= ( ( $oneline ) ? ', ' : '' );

					if ( $use_tags ) {
						$output .= '<' . $tag_name . ' class="street-address">' . \esc_html( $business_address_2 ) . '</' . $tag_name . '>';
					}
					else {
						$output .= \esc_html( $business_address_2 );
					}
				}
			}

			if ( $escape_output ) {
				$output = \addslashes( $output );
			}

			return $output;
		}
	}
}
