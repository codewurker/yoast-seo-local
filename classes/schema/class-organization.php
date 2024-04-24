<?php
/**
 * Local SEO plugin file.
 *
 * @package WPSEO_Local\Frontend\Schema
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\Local\Repositories\Business_Types_Repository;
use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

/**
 * Class WPSEO_Local_Organization.
 *
 * Manages the Schema for an Organization.
 *
 * @property Meta_Tags_Context $context A value object with context variables.
 * @property array<string>     $options Local SEO options.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
 */
class WPSEO_Local_Organization extends Abstract_Schema_Piece {

	/**
	 * A value object with context variables.
	 *
	 * @var Meta_Tags_Context
	 */
	public $context;

	/**
	 * Stores the options for this plugin.
	 *
	 * @var array<string|int|bool>
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

		if ( $this->should_filter_existing_organization() ) {
			add_filter( 'wpseo_schema_organization', [ $this, 'filter_organization_data' ] );
		}
	}

	/**
	 * Determines whether or not this piece should be added to the graph.
	 *
	 * @return bool
	 */
	public function is_needed() {
		// For the main organization we're using the schema filter instead.
		if ( $this->should_filter_existing_organization() ) {
			return false;
		}

		return ( wpseo_has_multiple_locations() );
	}

	/**
	 * Determines whether the schema piece should filter existing schema data.
	 *
	 * @return bool Value that indicates whether or not to filter existing schema data.
	 */
	public static function should_filter_existing_organization() {
		return (
			! wpseo_has_multiple_locations()
			|| ( wpseo_may_use_multiple_locations_shared_business_info() || wpseo_may_use_multiple_locations_shared_opening_hours() )
			|| wpseo_has_primary_location()
			|| ! wpseo_multiple_location_one_organization()
			|| wpseo_has_location_acting_as_primary()
		);
	}

	/**
	 * Determines whether or not we should fill this piece with shared properties if we don't have any other usable data.
	 *
	 * @return bool Value indicating whether or not to fill with shared properties.
	 */
	private function should_fill_with_shared_properties() {
		return (
			wpseo_multiple_location_one_organization()
			&& ! wpseo_has_primary_location()
			&& ! wpseo_has_location_acting_as_primary()
			&& ( wpseo_may_use_multiple_locations_shared_business_info() || wpseo_may_use_multiple_locations_shared_opening_hours() )
		);
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
	 * Adds data to the Organization Schema output.
	 *
	 * @param array<string|array<string|int>> $data Organization Schema data.
	 *
	 * @return array<string|array<string|int|array<string|array<string>>>> Organization Schema data.
	 */
	public function filter_organization_data( $data ) {
		if ( ! empty( $data['@type'] ) && ! is_array( $data['@type'] ) ) {
			$data['@type'] = [ $data['@type'] ];
		}

		array_push( $data['@type'], 'Place' );

		if ( $data['@id'] === $this->context->canonical . WPSEO_Local_Schema_IDs::BRANCH_ORGANIZATION_ID ) {
			return $data;
		}

		if ( wpseo_has_multiple_locations() && ! wpseo_multiple_location_one_organization() ) {

			if ( ! $this->will_have_branch_organization() ) {
				$data['mainEntityOfPage'] = [ '@id' => $this->context->main_schema_id ];
			}

			return $data;
		}

		if ( $this->should_fill_with_shared_properties() ) {
			if ( wpseo_may_use_multiple_locations_shared_business_info() ) {
				$data = $this->fill_with_shared_business_info( $data );
			}
			if ( wpseo_may_use_multiple_locations_shared_opening_hours() ) {
				$data = $this->fill_with_shared_opening_hours( $data );
			}

			return $data;
		}

		$location = $this->get_related_location();
		$data     = $this->get_data( $data, $location );

		/**
		 * Filters the URL in the Organization piece in the Schema .
		 *
		 * @param string $site_url The homepage URL.
		 *
		 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		 */
		$data['url'] = apply_filters( 'yoast-local-seo-schema-organization-url', $this->context->site_url );

		return $data;
	}

	/**
	 * Gets the related location data object for schema generation.
	 *
	 * @return object Object containing location data.
	 */
	private function get_related_location() {
		$id = 0;

		if ( wpseo_has_primary_location() ) {
			$id = WPSEO_Options::get( 'multiple_locations_primary_location' );
		}
		elseif ( wpseo_has_location_acting_as_primary() ) {
			$locations_repository_builder = new Locations_Repository_Builder();
			$repo                         = $locations_repository_builder->get_locations_repository();
			$location                     = $repo->get( [ 'post_status' => 'publish' ], false );
			$id                           = reset( $location );
		}

		return $this->get_location_data( $id );
	}

	/**
	 * Gets the related location data object for schema generation.
	 *
	 * @param int $post_id Location post ID.
	 *
	 * @return object Object containing location data.
	 */
	private function get_location_data( $post_id ) {
		$args = [];
		$id   = $post_id;

		$locations_repository_builder = new Locations_Repository_Builder();
		$repository                   = $locations_repository_builder->get_locations_repository();
		$locations                    = $repository->get( $args );

		return (object) $locations[ $id ];
	}

	/**
	 * Generates JSON+LD output for a branch Organization.
	 *
	 * @return false This piece is already being generated by Yoast SEO.
	 */
	public function generate() {
		// This piece is already being generated by Yoast SEO and is skipped as determined in is_needed().

		return false;
	}

	/**
	 * Generates data object for Organization.
	 *
	 * @param array<int|string|array<string|array<string>>> $data     Data object of the current schema node.
	 * @param object                                        $location Data object of the related location.
	 *
	 * @return array<int|string|array<string|array<string>>> Array with Organization schema data.
	 */
	public function get_data( $data, $location ) {
		if ( ! empty( $location->business_type ) ) {
			array_push( $data['@type'], $location->business_type );
		}

		$data['@type'] = array_unique( $data['@type'] );

		if ( WPSEO_Local_Postal_Address::has_required_properties( $location ) ) {
			$data['address'] = [ '@id' => $this->context->canonical . $this->get_schema_address_id() ];
		}

		$data['@id'] = $this->context->site_url . $this->get_schema_id();

		if ( $this->should_output_main_entity_property() ) {
			$data['mainEntityOfPage'] = [ '@id' => $this->context->main_schema_id ];
		}

		$data = $this->add_logo( $data );
		$data = $this->add_organization_attributes( $data, $location );

		return $data;
	}

	/**
	 * Determines whether or not to output the mainEntityOfPage property.
	 *
	 * @return bool Value indicating whether or not to output the property.
	 */
	private function should_output_main_entity_property() {
		$post_type_instance = new PostType();
		$post_type_instance->initialize();

		if ( get_post_type() !== $post_type_instance->get_post_type() ) {
			return false;
		}

		if ( ! wpseo_has_multiple_locations() ) {
			return true;
		}

		if ( $this->should_filter_existing_organization() ) {

			if ( $this->will_have_branch_organization() ) {
				if ( wpseo_has_primary_location() && wpseo_is_current_location_identical_to_primary() ) {
					return true;
				}
			}
			elseif ( wpseo_is_current_location_identical_to_primary() ) {
				return true;
			}
			elseif ( ! wpseo_has_primary_location() && ! wpseo_has_location_acting_as_primary() ) {
				return true;
			}
		}
		elseif ( ! wpseo_is_current_location_identical_to_primary() ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines whether or not a branch organization schema node will be outputted.
	 *
	 * @return bool Value indicating whether or not a branch organisation will be outputted.
	 */
	private function will_have_branch_organization() {
		// When changing these conditions also update class-organization-branch.php is_needed().
		return wpseo_schema_will_have_branch_organization( $this->context->site_represents === 'company' );
	}

	/**
	 * Gets the desired ID of the schema node.
	 *
	 * @return string ID of the schema node.
	 */
	public function get_schema_id() {
		return Schema_IDs::ORGANIZATION_HASH;
	}

	/**
	 * Gets the desired ID of the address schema node.
	 *
	 * @return string ID of the schema node.
	 */
	public function get_schema_address_id() {
		return WPSEO_Local_Schema_IDs::MAIN_ADDRESS_ID;
	}

	/**
	 * Adds the logo for the current business.
	 *
	 * @param array<int|string|array<string|array<string>>> $data Array with Organization schema data.
	 *
	 * @return array<int|string|array<string|array<string>>> Array with Organization schema data.
	 */
	private function add_logo( $data ) {
		$schema_id = $this->context->canonical . WPSEO_Local_Schema_IDs::MAIN_ORGANIZATION_LOGO;

		if ( $this->is_branch() ) {
			$schema_id = $this->context->canonical . WPSEO_Local_Schema_IDs::BRANCH_ORGANIZATION_LOGO;
		}

		$data['logo']  = [ '@id' => $schema_id ];
		$data['image'] = [ '@id' => $schema_id ];

		return $data;
	}

	/**
	 * Adds attributes to the organization schema data.
	 *
	 * @param array<int|string|array<string|array<string>>> $data     Array with Organization schema data.
	 * @param object                                        $location Location data.
	 *
	 * @return array<int|string|array<string|array<string>>> Array with Organization schema data.
	 */
	private function add_organization_attributes( $data, $location ) {

		$organization_attributes = [
			'email'                => 'business_email',
			'faxNumber'            => 'business_fax',
			'areaServed'           => 'business_area_served',
			'vatID'                => 'business_vat',
			'taxID'                => 'business_tax',
			'url'                  => 'business_url',
			'globalLocationNumber' => 'global_location_number',
		];

		$business_types = new Business_Types_Repository();

		if ( $business_types->is_business_type_child_of( 'LocalBusiness', $location->business_type ) ) {
			$organization_attributes['priceRange']         = 'business_price_range';
			$organization_attributes['currenciesAccepted'] = 'business_currencies_accepted';
			$organization_attributes['paymentAccepted']    = 'business_payment_accepted';
		}

		// Add coordinates.
		if ( isset( $location->coords ) && ! empty( $location->coords['lat'] ) && ! empty( $location->coords['long'] ) ) {
			$data['geo'] = [
				'@type'     => 'GeoCoordinates',
				'latitude'  => $location->coords['lat'],
				'longitude' => $location->coords['long'],
			];
		}

		// Add telephone numbers.
		$phones = [];
		if ( $location->business_phone ) {
			$phones[] = $location->business_phone;
		}

		if ( $location->business_phone_2nd ) {
			$phones[] = $location->business_phone_2nd;
		}

		$data['telephone'] = $phones;

		if ( $location->business_contact_phone || $location->business_contact_email ) {
			$data['contactPoint'] = [
				'@type' => 'ContactPoint',
			];
			if ( $location->business_contact_phone ) {
				$data['contactPoint']['telephone'] = $location->business_contact_phone;
			}
			if ( $location->business_contact_email ) {
				$data['contactPoint']['email'] = $location->business_contact_email;
			}
		}

		// Add Opening Hours.
		$opening_hours                     = new WPSEO_Local_Opening_Hours( $location->post_id );
		$data['openingHoursSpecification'] = $opening_hours->generate_opening_hours();

		// Iterate over organization attributes and set corresponding data.
		foreach ( $organization_attributes as $attribute => $option_key ) {
			if ( ! empty( $location->$option_key ) ) {
				$data[ $attribute ] = $location->$option_key;
			}
		}

		return $data;
	}

	/**
	 * Returns the Organization Schema for a setup in which shared properties have been set and no primary location has been set.
	 *
	 * @param array<int|string|array<string|array<string>>> $data Organization Schema data.
	 *
	 * @return array<int|string|array<string|array<string>>> Organization Schema data.
	 */
	private function fill_with_shared_business_info( $data ) {
		$options = WPSEO_Options::get_all();

		if ( ! is_array( $data['@type'] ) ) {
			$data['@type'] = [ $data['@type'] ];
		}

		if ( ! empty( $options['business_type'] ) ) {
			array_push( $data['@type'], $options['business_type'] );
		}

		$data['@type'] = array_unique( $data['@type'] );

		if ( ( wpseo_has_primary_location() || wpseo_has_location_acting_as_primary() )
			&& WPSEO_Local_Postal_Address::has_required_properties( $this->get_related_location() )
		) {
			$data['address'] = [ '@id' => $this->context->canonical . $this->get_schema_address_id() ];
		}

		$data['@id'] = $this->context->site_url . $this->get_schema_id();

		if ( $this->should_output_main_entity_property() ) {
			$data['mainEntityOfPage'] = [ '@id' => $this->context->main_schema_id ];
		}

		$organization_attributes = [
			'email'     => 'location_email',
			'faxNumber' => 'location_fax',
			'vatID'     => 'location_vat_id',
			'taxID'     => 'location_tax_id',
			'url'       => 'location_url',
		];

		if ( ( ! wpseo_has_multiple_locations() || wpseo_has_primary_location() || wpseo_has_location_acting_as_primary() ) ) {
			$organization_attributes['areaServed'] = 'location_area_served';
		}

		$business_types = new Business_Types_Repository();
		if ( $business_types->is_business_type_child_of( 'LocalBusiness', $options['business_type'] ) ) {

			if ( ( ! wpseo_has_multiple_locations() || wpseo_has_primary_location() || wpseo_has_location_acting_as_primary() ) ) {
				$organization_attributes['priceRange'] = 'location_price_range';
			}

			$organization_attributes['currenciesAccepted'] = 'location_currencies_accepted';
			$organization_attributes['paymentAccepted']    = 'location_payment_accepted';
		}

		// Add telephone numbers.
		$phones = [];
		if ( $options['location_phone'] ) {
			$phones[] = $options['location_phone'];
		}

		if ( $options['location_phone_2nd'] ) {
			$phones[] = $options['location_phone_2nd'];
		}
		$data['telephone'] = $phones;

		if ( $options['location_contact_phone'] || $options['location_contact_email'] ) {
			$data['contactPoint'] = [
				'@type' => 'ContactPoint',
			];
			if ( $options['location_contact_phone'] ) {
				$data['contactPoint']['telephone'] = $options['location_contact_phone'];
			}
			if ( $options['location_contact_email'] ) {
				$data['contactPoint']['email'] = $options['location_contact_email'];
			}
		}

		// Iterate over organization attributes and set corresponding data.
		foreach ( $organization_attributes as $attribute => $option_key ) {
			if ( ! empty( $options[ $option_key ] ) ) {
				$data[ $attribute ] = $options[ $option_key ];
			}
		}

		return $data;
	}

	/**
	 * Returns the Organization Schema for a setup in which shared properties have been set and no primary location has been set.
	 *
	 * @param array<int|string|array<string|array<string>>> $data Organization Schema data.
	 *
	 * @return array<int|string|array<string|array<string>>> Organization Schema data.
	 */
	private function fill_with_shared_opening_hours( $data ) {
		$opening_hours                     = new WPSEO_Local_Opening_Hours( 0 );
		$data['openingHoursSpecification'] = $opening_hours->generate_opening_hours();

		return $data;
	}
}
