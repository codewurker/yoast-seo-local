<?php

namespace Yoast\WP\Local\Repositories;

use WP_Query;
use WPSEO_Options;
use WPSEO_Primary_Term;
use WPSEO_Taxonomy_Meta;
use WPSEO_Utils;
use Yoast\WP\Local\Conditionals\No_Conditionals;
use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\SEO\Context\Meta_Tags_Context;
use Yoast\WP\SEO\Initializers\Initializer_Interface;

/**
 * Class Locations_Repository
 *
 * This class handles the querying of all locations
 */
class Locations_Repository implements Initializer_Interface {

	/**
	 * This trait is always required.
	 */
	use No_Conditionals;

	/**
	 * Stores the last executed query.
	 *
	 * @var WP_Query
	 */
	public $query;

	/**
	 * This array determines where the implementing code needs to fetch
	 * the meta values from. Should the repository query a location from
	 * the posts table, the `postmeta` value of this array is used to get a
	 * value from the post_meta table. When querying from the options table
	 * for a single location, the `option` value is used to get a value
	 * from the options table with that key.
	 *
	 * @var array<array<string,string>>
	 */
	public $map = [
		'business_type'                => [
			'postmeta' => '_wpseo_business_type',
			'option'   => 'business_type',
		],
		'business_address'             => [
			'postmeta' => '_wpseo_business_address',
			'option'   => 'location_address',
		],
		'business_address_2'           => [
			'postmeta' => '_wpseo_business_address_2',
			'option'   => 'location_address_2',
		],
		'business_city'                => [
			'postmeta' => '_wpseo_business_city',
			'option'   => 'location_city',
		],
		'business_state'               => [
			'postmeta' => '_wpseo_business_state',
			'option'   => 'location_state',
		],
		'business_zipcode'             => [
			'postmeta' => '_wpseo_business_zipcode',
			'option'   => 'location_zipcode',
		],
		'business_country'             => [
			'postmeta' => '_wpseo_business_country',
			'option'   => 'location_country',
		],
		'global_location_number'       => [
			'postmeta' => '_wpseo_global_location_number',
			'option'   => 'global_location_number',
		],
		'business_phone'               => [
			'postmeta' => '_wpseo_business_phone',
			'option'   => 'location_phone',
		],
		'business_phone_2nd'           => [
			'postmeta' => '_wpseo_business_phone_2nd',
			'option'   => 'location_phone_2nd',
		],
		'business_fax'                 => [
			'postmeta' => '_wpseo_business_fax',
			'option'   => 'location_fax',
		],
		'business_email'               => [
			'postmeta' => '_wpseo_business_email',
			'option'   => 'location_email',
		],
		'business_contact_email' => [
			'postmeta' => '_wpseo_business_contact_email',
			'option'   => 'location_contact_email',
		],
		'business_contact_phone' => [
			'postmeta' => '_wpseo_business_contact_phone',
			'option'   => 'location_contact_phone',
		],
		'business_price_range'         => [
			'postmeta' => '_wpseo_business_price_range',
			'option'   => 'location_price_range',
		],
		'business_currencies_accepted' => [
			'postmeta' => '_wpseo_business_currencies_accepted',
			'option'   => 'location_currencies_accepted',
		],
		'business_payment_accepted'    => [
			'postmeta' => '_wpseo_business_payment_accepted',
			'option'   => 'location_payment_accepted',
		],
		'business_area_served'         => [
			'postmeta' => '_wpseo_business_area_served',
			'option'   => 'location_area_served',
		],
		'business_coc'                 => [
			'postmeta' => '_wpseo_business_coc_id',
			'option'   => 'location_coc_id',
		],
		'business_tax'                 => [
			'postmeta' => '_wpseo_business_tax_id',
			'option'   => 'location_tax_id',
		],
		'business_vat'                 => [
			'postmeta' => '_wpseo_business_vat_id',
			'option'   => 'location_vat_id',
		],
	];

	/**
	 * Mapping of location attributes to process callbacks.
	 *
	 * The following callback methods are defined on this class. The methods
	 * are called based on what meta key is required by the array of meta keys
	 * passed to this repository when querying locations. If the key isn't passed
	 * to that array, the callback should not be called.
	 *
	 * @var array<array<string>>
	 */
	protected $custom_map = [
		'business_name'        => [
			'postmeta_cb' => 'cb_postmeta_name',
			'options_cb'  => 'cb_options_name',
		],
		'business_url'         => [
			'postmeta_cb' => 'cb_postmeta_url',
			'options_cb'  => 'cb_options_url',
		],
		'business_description' => [
			'postmeta_cb' => 'cb_postmeta_description',
			'options_cb'  => 'cb_options_description',
		],
		'coords'               => [
			'postmeta_cb' => 'cb_postmeta_coords',
			'options_cb'  => 'cb_options_coords',
		],
		'business_timezone'    => [
			'postmeta_cb' => 'cb_postmeta_timezone',
			'options_cb'  => 'cb_options_timezone',
		],
		'post_id'              => [
			'postmeta_cb' => 'cb_postmeta_id',
			'options_cb'  => 'cb_options_id',
		],
		'is_postal_address'    => [
			'postmeta_cb' => 'cb_postmeta_postal',
			'options_cb'  => 'cb_options_postal',
		],
		'business_type'        => [
			'postmeta_cb' => 'cb_postmeta_type',
			'options_cb'  => 'cb_options_type',
		],
		'custom_marker'        => [
			'postmeta_cb' => 'cb_postmeta_custom_marker',
			'options_cb'  => 'cb_options_custom_marker',
		],
		'business_logo'        => [
			'postmeta_cb' => 'cb_postmeta_logo',
			'options_cb'  => 'cb_options_logo',
		],
		'business_image'       => [
			'postmeta_cb' => 'cb_postmeta_image',
			'options_cb'  => 'cb_options_image',
		],
		'format_24h'           => [
			'postmeta_cb' => 'cb_postmeta_format_24h',
			'options_cb'  => 'cb_options_format_24h',
		],
	];

	/**
	 * Stores the options repository.
	 *
	 * @var Options_Repository
	 */
	protected $options;

	/**
	 * Stores the options from WPSEO Local.
	 *
	 * @var array<string|int|bool>
	 */
	private $local_options;

	/**
	 * Stores the options from WPSEO.
	 *
	 * @var array<int|bool|string|array<string|bool|array<array<array<string>>>>>
	 */
	private $wpseo_options;

	/**
	 * Stores the post type object.
	 *
	 * @var PostType
	 */
	private $post_type;

	/**
	 * Locations_Repository constructor.
	 *
	 * @param PostType           $post_type The post type object as a dependency.
	 * @param Options_Repository $options   Options.
	 */
	public function __construct( PostType $post_type, Options_Repository $options ) {
		$this->post_type = $post_type;
		$this->options   = $options;
	}

	/**
	 * The init function for the Locations_Repository class.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->local_options = \get_option( 'wpseo_local' );
		$this->wpseo_options = \get_option( 'wpseo' );
		$this->options->initialize();
	}

	/**
	 * Get the location details, automatically populated with meta fields
	 * Can be loaded from post meta or options table, based on multiple location setting
	 *
	 * @param array<string> $arguments   Arguments to filter the query.
	 * @param bool          $load_meta   Automatically load all meta fields after querying.
	 * @param array<string> $meta_fields Specify what meta fields need to be loaded.
	 *
	 * @return array<array<string|int|array<float|string>>> containing the queried locations
	 */
	public function get( $arguments = [], $load_meta = true, $meta_fields = [] ) {
		global $pagenow;

		$locations   = [];
		$meta_fields = $this->prepare_meta_fields( $meta_fields );

		if ( ! empty( $arguments['id'] ) && ! \is_array( $arguments['id'] ) ) {
			$arguments['id'] = (array) $arguments['id'];
		}

		// Don't return anything in case of Block preview.
		if ( isset( $arguments['id'][0] ) && $arguments['id'][0] === 'preview' ) {
			return [];
		}

		// If single location, load from options.
		if ( ! $this->options->use_multiple_locations() ) {
			$locations[] = $this->load_meta_from_options( $meta_fields );

			return $locations;
		}

		\add_filter( 'posts_where', [ $this, 'filter_where' ] );
		$this->query = $this->get_filter_locations( $arguments );
		$post_ids    = $this->query->posts;
		\remove_filter( 'posts_where', [ $this, 'filter_where' ] );

		if ( ! $load_meta ) {
			return $post_ids;
		}

		foreach ( $post_ids as $post_id ) {
			$locations[ $post_id ]          = $this->load_meta_from_meta( $post_id, $meta_fields );
			$locations[ $post_id ]['terms'] = $this->load_terms( $post_id );

			if ( $this->options->use_shared_business_information() ) {
				$locations[ $post_id ] = $this->apply_shared_properties( $locations[ $post_id ] );
			}
		}

		\wp_reset_postdata();

		/**
		 * As we cannot reliably determine the location ID for new locations, this code will attempt to add a dummy
		 * location ID based on get_the_ID(), so we can load the shared business information.
		 */
		if ( $pagenow === 'post-new.php' && $this->options->use_shared_business_information() ) {
			$new_post_id = \get_the_ID();

			if ( $new_post_id !== false ) {
				$locations[ $new_post_id ] = $this->apply_shared_properties( [] );
			}
		}

		return $locations;
	}

	/**
	 * Applies shared properties to the passed location data.
	 *
	 * @param array<string|int|array<float|string>> $location The location data to apply the shared properties to.
	 *
	 * @return array<string|int|array<float|string>> The new location data.
	 */
	protected function apply_shared_properties( $location ) {
		// Get shared business information.
		$meta_fields       = $this->prepare_meta_fields( [] );
		$data_from_options = $this->load_meta_from_options( $meta_fields );

		// Business address data needs to be excluded from being shared.
		$data_from_options['business_address']       = '';
		$data_from_options['business_address_2']     = '';
		$data_from_options['business_city']          = '';
		$data_from_options['business_state']         = '';
		$data_from_options['business_zipcode']       = '';
		$data_from_options['business_country']       = '';
		$data_from_options['global_location_number'] = '';
		$data_from_options['coords']['lat']          = '';
		$data_from_options['coords']['long']         = '';

		// Loop through location fields and remove empty ones.
		$location = \array_filter(
			$location,
			static function ( $value ) {
				return ! empty( $value );
			}
		);

		// Set the override value to true for the custom fields.
		foreach ( $location as $key => $value ) {
			if ( $key === 'post_id' ) {
				continue;
			}

			$location[ 'is_overridden_' . $key ] = true;
		}

		// Merge the unique location values on top of the defaults.
		return \array_merge( $data_from_options, $location );
	}

	/**
	 * Load meta fields from post meta fields
	 *
	 * @param int           $location_id Id of specific location.
	 * @param array<string> $meta_fields Specify what meta fields need to be loaded.
	 *
	 * @return array<string|int|array<float|string>|null>
	 */
	public function load_meta_from_meta( $location_id, $meta_fields = [] ) {
		$data = [];

		foreach ( $this->map as $key => $value ) {
			if ( \in_array( $key, $meta_fields, true ) ) {
				$data[ $key ] = \get_post_meta( $location_id, $value['postmeta'], true );
			}
		}

		foreach ( $this->custom_map as $key => $value ) {
			if ( \in_array( $key, $meta_fields, true ) ) {
				$data[ $key ] = \call_user_func( [ $this, $value['postmeta_cb'] ], $location_id );
			}
		}

		return $data;
	}

	/**
	 * Load meta fields from options table
	 *
	 * @param array<string> $meta_fields Specify what meta fields need to be loaded.
	 *
	 * @return array<string|int|array<float|string>|null>
	 */
	public function load_meta_from_options( $meta_fields = [] ) {
		$data = [];

		foreach ( $this->map as $key => $value ) {
			if ( \in_array( $key, $meta_fields, true ) ) {
				$data[ $key ] = $this->options->get( $value['option'] );
			}
		}

		foreach ( $this->custom_map as $key => $value ) {
			if ( \in_array( $key, $meta_fields, true ) ) {
				$data[ $key ] = \call_user_func( [ $this, $value['options_cb'] ], $this->options );
			}
		}

		return $data;
	}

	/**
	 * Load wpseo_locations_category terms.
	 *
	 * @param string|int $location_id Id of the location to get the location categories from.
	 *
	 * @return array<string> The wpseo_locations_category terms.
	 */
	public function load_terms( $location_id ) {
		// Put all categories in an array, to be passed on to the map later on and for the categories filter.
		$terms = \get_the_terms( $location_id, 'wpseo_locations_category' );
		if ( \is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		return $terms;
	}

	/**
	 * Returns the location data in context for the current page.
	 *
	 * @return object|null The location data for the current page.
	 */
	public function for_current_page() {
		if ( ! $this->options->use_multiple_locations() ) {
			return (object) $this->get( [ 'id' => null ] )[0];
		}

		// Singular.
		if ( \is_singular( $this->post_type->get_post_type() ) ) {

			$location = $this->get( [ 'id' => \get_the_ID() ] );

			return (object) \reset( $location );
		}

		// Non-singular, primary location set.
		if ( $this->options->has_primary_location() ) {
			$location = $this->get(
				[
					'id'          => $this->options->get_primary_location(),
					'post_status' => 'publish',
				]
			);

			$location = \reset( $location );

			// If the primary location has been deleted, see if a single location exists.
			if ( $location !== false ) {
				return (object) $location;
			}
		}

		// Non-singular, just one location existing.
		$location = $this->get( [ 'post_status' => 'publish' ] );
		if ( \count( $location ) === 1 ) {
			return (object) \reset( $location );
		}

		return null;
	}

	/**
	 * This method retrieves location based on given parameters. Possible parameters (with their defaults):
	 * number: -1 (amount of locations)
	 * orderby: title
	 * order: ASC
	 * fields: ids
	 * category_id: 0 (term_id of the wpseo-location-category taxonomy)
	 * location_ids: array() (array of location Ids to retrieve)
	 *
	 * @param array<int|string> $arguments Arguments for getting filtered locations.
	 *
	 * @return WP_Query The locations.
	 */
	public function get_filter_locations( $arguments = [] ) {
		$arguments = $this->prepare_arguments( $arguments );

		$location_args = [
			'post_type'      => $this->post_type->get_post_type(),
			'posts_per_page' => ( empty( $arguments['id'] ) || $arguments['id'][0] === 'all' ) ? $arguments['number'] : \count( $arguments['id'] ),
			'orderby'        => $arguments['orderby'],
			'order'          => $arguments['order'],
			'fields'         => $arguments['fields'],
		];

		/**
		 * A user that can edit posts should be able to get data from any post status.
		 * Failing to do so will result in for example scheduled posts to not show any location meta data.
		 */
		if ( \current_user_can( 'edit_posts' ) ) {
			$location_args['post_status'] = 'any';
		}

		if ( isset( $arguments['post_status'] ) ) {
			$location_args['post_status'] = $arguments['post_status'];
		}

		$tax_query = [];

		if ( ! empty( $arguments['category_id'] ) && \is_numeric( $arguments['category_id'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'wpseo_locations_category',
				'field'    => 'term_id',
				'terms'    => $arguments['category_id'],
			];

			$location_args['tax_query'] = $tax_query;
		}

		// Check the possible array of ID's that has to be passed.
		if ( ! empty( $arguments['id'] ) ) {
			$location_args = \array_merge( $location_args, $this->get_filtered_location_ids( $arguments ) );
		}

		return new WP_Query( $location_args );
	}

	/**
	 * Gets the filtered location IDs.
	 *
	 * @param array<int|string> $arguments The arguments to use to get the IDs.
	 *
	 * @return array<int> The filtered location IDs.
	 */
	protected function get_filtered_location_ids( $arguments ) {
		$location_args = [];

		// Force arguments to be an array.
		$arguments['id'] = ( \is_array( $arguments['id'] ) ) ? $arguments['id'] : (array) $arguments['id'];

		if ( \count( $arguments['id'] ) !== 1 ) {
			$location_args['post__in'] = $arguments['id'];

			return $location_args;
		}

		if ( \is_numeric( $arguments['id'][0] ) ) {
			$location_args['p'] = (int) $arguments['id'][0];
		}

		if ( $arguments['id'][0] === 'current' && \is_singular( $this->post_type->get_post_type() ) ) {
			$location_args['p'] = \get_queried_object_id();
		}

		return $location_args;
	}

	/**
	 * Queries all locations that have 'Attorney' as business type
	 * for the 3.4 or below update routine
	 *
	 * @return WP_Query
	 */
	public function get_attorney_locations() {
		$locations_args = [
			'post_type'  => $this->post_type->get_post_type(),
			'nopaging'   => true,
			'meta_query' => [
				[
					'key'     => '_wpseo_business_type',
					'value'   => 'Attorney',
					'compare' => '=',
				],
			],
		];

		return new WP_Query( $locations_args );
	}

	/**
	 * Retrieves the location name.
	 *
	 * @param int $location_id Id of the location to get the title from.
	 *
	 * @return string
	 */
	public function cb_postmeta_name( $location_id ) {
		return \get_the_title( $location_id );
	}

	/**
	 * Retrieve the location description.
	 *
	 * @param int $location_id Id of the location to get the description from.
	 *
	 * @return string
	 */
	public function cb_postmeta_description( $location_id ) {
		return \wpseo_local_get_excerpt( $location_id );
	}

	/**
	 * Retrieves the location url.
	 *
	 * @param int $location_id Id of the location to get the url from.
	 *
	 * @return false|string
	 */
	public function cb_postmeta_url( $location_id ) {
		$url = \get_post_meta( $location_id, '_wpseo_business_url', true );

		$post_type_object = \get_post_type_object( $this->post_type->get_post_type() );
		if ( empty( $url ) && $post_type_object->public ) {
			$url = \get_permalink( $location_id );
		}

		return $url;
	}

	/**
	 * Returns the location coordinates.
	 *
	 * @param int $location_id Id of the location to get the coords from.
	 *
	 * @return array<float>
	 */
	public function cb_postmeta_coords( $location_id ) {
		return [
			'lat'  => \str_replace( ',', '.', \get_post_meta( $location_id, '_wpseo_coordinates_lat', true ) ),
			'long' => \str_replace( ',', '.', \get_post_meta( $location_id, '_wpseo_coordinates_long', true ) ),
		];
	}

	/**
	 * Returns the location timezone.
	 *
	 * @param int $location_id Id of the location to get the coords from.
	 *
	 * @return false|string
	 */
	public function cb_postmeta_timezone( $location_id ) {
		$value = \get_post_meta( $location_id, '_wpseo_business_timezone', true );

		if ( \is_wp_error( $value ) === true ) {
			$value = '';
		}

		return $value;
	}

	/**
	 * Returns the location ID.
	 *
	 * @param int $location_id Id of the location to get the id from.
	 *
	 * @return int
	 */
	public function cb_postmeta_id( $location_id ) {
		return $location_id;
	}

	/**
	 * Returns whether the location is a postal address.
	 *
	 * @param int $location_id Id of the location to get the postal address flag from.
	 *
	 * @return bool
	 */
	public function cb_postmeta_postal( $location_id ) {
		$is_postal_address = \get_post_meta( $location_id, '_wpseo_is_postal_address', true );

		return $is_postal_address === '1';
	}

	/**
	 * Returns the business type.
	 *
	 * @param int $location_id Id of the location to get the type from.
	 *
	 * @return false|string
	 */
	public function cb_postmeta_type( $location_id ) {
		return \get_post_meta( $location_id, '_wpseo_business_type', true );
	}

	/**
	 * Returns the location custom marker.
	 *
	 * @param int $location_id Id of the location to get the custom marker value from.
	 *
	 * @return false|string
	 */
	public function cb_postmeta_custom_marker( $location_id ) {
		$custom_marker = \get_post_meta( $location_id, '_wpseo_business_location_custom_marker', true );

		// If no custom marker for a location is set, check if there are custom markers for terms.
		if ( empty( $custom_marker ) ) {
			$custom_marker = $this->get_custom_marker_from_terms( $location_id );
		}

		// If no custom markers are set for a location or terms, fall back to custom marker set in WPSEO Local options.
		if ( empty( $custom_marker ) ) {
			$custom_marker = $this->cb_options_custom_marker( $this->options );
		}

		return $custom_marker;
	}

	/**
	 * Gets the custom marker from one of the associated terms.
	 *
	 * @param int $location_id The location ID.
	 *
	 * @return false|string|null The custom marker. Returns null if none can be found.
	 */
	protected function get_custom_marker_from_terms( $location_id ) {
		$terms = \get_the_terms( $location_id, 'wpseo_locations_category' );

		if ( empty( $terms ) || $terms === false || \is_wp_error( $terms ) ) {
			return null;
		}

		$terms = \wp_list_pluck( $terms, 'term_id' );
		$terms = \apply_filters( 'wpseo_local_custom_marker_order', $terms );

		if ( \class_exists( 'WPSEO_Primary_Term' ) ) {
			// Check if there's a primary term.
			$primary_term = new WPSEO_Primary_Term( 'wpseo_locations_category', $location_id );

			if ( \method_exists( $primary_term, 'get_primary_term ' ) ) {
				$primary_term = $primary_term->get_primary_term();

				// If there is a primary term, replace the term array with the primary term.
				$terms = ( ! empty( $primary_term ) ) ? [ $primary_term ] : $terms;
			}

			if ( \method_exists( 'WPSEO_Taxonomy_Meta', 'get_term_meta' ) ) {
				$tax_meta = WPSEO_Taxonomy_Meta::get_term_meta( (int) $terms[0], 'wpseo_locations_category' );
			}
		}

		if ( isset( $tax_meta['wpseo_local_custom_marker'] ) && ! empty( $tax_meta['wpseo_local_custom_marker'] ) ) {
			return \wp_get_attachment_url( $tax_meta['wpseo_local_custom_marker'] );
		}

		return null;
	}

	/**
	 * Returns the business logo.
	 *
	 * @param int $location_id Id of the location to get the logo from.
	 *
	 * @return int|false
	 */
	public function cb_postmeta_logo( $location_id ) {
		$logo = \get_post_meta( $location_id, '_wpseo_business_location_logo', true );

		if ( empty( $logo ) && ! \is_admin() ) {
			$logo = $this->cb_options_logo();
		}

		// Check if a number is returned. If not, get the ID from the src, otherwise, simply return the ID.
		return ( ! \is_numeric( $logo ) ? \yoast_wpseo_local_get_attachment_id_from_src( $logo ) : $logo );
	}

	/**
	 * Returns the business image.
	 *
	 * @param int $location_id Id of the location to get the image from.
	 *
	 * @return int|false
	 */
	public function cb_postmeta_image( $location_id ) {
		$business_image = \get_post_thumbnail_id( $location_id );

		if ( empty( $business_image ) ) {
			$business_image = $this->cb_options_image( $this->options );
		}

		return $business_image;
	}

	/**
	 * Returns whether the location opening hour is set to 24h format.
	 *
	 * @param int $location_id Id of the location to check.
	 *
	 * @return bool
	 */
	public function cb_postmeta_format_24h( $location_id ) {
		$format_24h_option = \wpseo_check_falses( $this->options->get( 'opening_hours_24h', false ) );
		$format_12h        = \wpseo_check_falses( \get_post_meta( $location_id, '_wpseo_format_12h', true ) );
		$format_24h        = \wpseo_check_falses( \get_post_meta( $location_id, '_wpseo_format_24h', true ) );

		// If options is set to 24 hours and the location is not set to 12 hours, return true.
		if ( ( $format_24h_option && ! $format_12h ) || ( ! $format_24h_option && $format_24h ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the company name from the Yoast SEO Settings.
	 *
	 * @return string
	 */
	public function cb_options_name() {
		if ( \class_exists( 'WPSEO_Options' ) ) {
			WPSEO_Options::get_instance();
			$company_name = WPSEO_Options::get( 'company_name' );
		}

		return ( $company_name ?? '' );
	}

	/**
	 * Get the site description as blog name - blog description.
	 *
	 * @return string
	 */
	public function cb_options_description() {
		return \get_option( 'blogname' ) . ' - ' . \get_option( 'blogdescription' );
	}

	/**
	 * Get location_url from options.
	 *
	 * @param WPSEO_Local_Option $options WPSEO Local options.
	 *
	 * @return string
	 */
	public function cb_options_url( $options ) {
		$url = $options->get( 'location_url', null );

		if ( $url !== null ) {
			return \esc_url( $url );
		}

		if ( \class_exists( 'WPSEO_Utils' ) === true ) {
			return WPSEO_Utils::home_url();
		}

		return \trailingslashit( \get_home_url() );
	}

	/**
	 * Get location coordinates from options.
	 *
	 * @param Options_Repository $options WPSEO Local options repository.
	 *
	 * @return array<float>
	 */
	public function cb_options_coords( $options ) {
		return [
			'lat'  => $options->get( 'location_coords_lat' ),
			'long' => $options->get( 'location_coords_long' ),
		];
	}

	/**
	 * Get location timezone from options.
	 *
	 * @return string
	 */
	public function cb_options_timezone() {
		return '';
	}

	/**
	 * Get location ID from options.
	 *
	 * @return string
	 */
	public function cb_options_id() {
		return '';
	}

	/**
	 * Get location postal address from options.
	 *
	 * @return string
	 */
	public function cb_options_postal() {
		return '';
	}

	/**
	 * Get location business type from options.
	 *
	 * @param Options_Repository $options WPSEO Local options repository.
	 *
	 * @return string
	 */
	public function cb_options_type( $options ) {
		return $options->get( 'business_type' );
	}

	/**
	 * Get location custom marker from options.
	 *
	 * @param Options_Repository $options WPSEO Local options array repository.
	 *
	 * @return false|string
	 */
	public function cb_options_custom_marker( $options ) {
		$marker = $options->get( 'local_custom_marker' );

		if ( isset( $marker ) && \intval( $marker ) ) {
			return \wp_get_attachment_url( $marker );
		}

		return '';
	}

	/**
	 * Retrieves the company logo ID.
	 *
	 * @return int|string ID of the company logo or an empty string.
	 */
	public function cb_options_logo() {
		$wpseo_titles = \get_option( 'wpseo_titles' );
		if ( ! isset( $wpseo_titles['company_logo_id'] ) ) {
			return '';
		}
		return $wpseo_titles['company_logo_id'];
	}

	/**
	 * Callback to retrieve the business image from the options.
	 *
	 * @param Options_Repository $options WPSEO Local options repository.
	 *
	 * @return false|string Return ID of the company logo.
	 */
	public function cb_options_image( $options ) {
		$image = $options->get( 'business_image' );

		if ( isset( $image ) && \intval( $image ) ) {
			return $image;
		}

		return '';
	}

	/**
	 * Get if location opening hours follow the 24h format from options.
	 *
	 * @param Options_Repository $options WPSEO Local options repository.
	 *
	 * @return string|null Return 'on' or null.
	 */
	public function cb_options_format_24h( $options ) {
		return $options->get( 'opening_hours_24h' );
	}

	/**
	 * Make sure password protected posts can not be found on maps or in location selectors.
	 *
	 * @param string $where Current query "where" clause.
	 *
	 * @return string
	 */
	public function filter_where( $where = '' ) {
		$where .= " AND post_password = ''";

		return $where;
	}

	/**
	 * Prepares arguments for retrieving data from multiple locations.
	 *
	 * @param array<int|string|array<int>> $arguments Array with (optional) arguments.
	 *
	 * @return array<int|string> Array with arguments, compared and parse with the defaults.
	 */
	private function prepare_arguments( $arguments ) {
		$defaults = [
			'number'      => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
			'fields'      => 'ids',
			'category_id' => 0,
			'id'          => [],
		];

		return \wp_parse_args( $arguments, $defaults );
	}

	/**
	 * Filters the meta fields to be returned.
	 *
	 * @todo Specify the default fields of the meta to be returned.
	 *
	 * @param array<string> $meta_fields Meta field keys provided to query.
	 *
	 * @return array<string>
	 */
	private function prepare_meta_fields( $meta_fields ) {
		$allowed_fields = \array_merge( \array_keys( $this->map ), \array_keys( $this->custom_map ) );

		if ( ! empty( $meta_fields ) ) {
			return \array_intersect_key( $allowed_fields, $meta_fields );
		}

		// No meta fields specified, so we return the defaults (full set of fields, for now).
		return $allowed_fields;
	}

	/**
	 * Gets the location by context object.
	 *
	 * @param Meta_Tags_Context $context The meta tags context.
	 *
	 * @return object|null The location.
	 */
	public function for_context( Meta_Tags_Context $context ) {
		if ( ! $this->options->use_multiple_locations() ) {
			return (object) $this->get( [ 'id' => null ] )[0];
		}

		// Singular.
		if ( $context->indexable->object_type === 'post' && $context->indexable->object_sub_type === $this->post_type->get_post_type() ) {

			$location = $this->get( [ 'id' => $context->indexable->object_id ] );

			return (object) \reset( $location );
		}

		// Non-singular, primary location set.
		if ( $this->options->has_primary_location() ) {
			$location = $this->get(
				[
					'id'          => $this->options->get_primary_location(),
					'post_status' => 'publish',
				]
			);

			$location = \reset( $location );

			// If the primary location has been deleted, see if a single location exists.
			if ( $location !== false ) {
				return (object) $location;
			}
		}

		// Non-singular, just one location existing.
		$location = $this->get( [ 'post_status' => 'publish' ] );
		if ( \count( $location ) === 1 ) {
			return (object) \reset( $location );
		}

		return null;
	}
}
