<?php

namespace Yoast\WP\Local\Tools;

use Yoast\WP\Local\Conditionals\Admin_Conditional;
use Yoast\WP\Local\Conditionals\Multiple_Locations_Conditional;
use Yoast\WP\Local\Repositories\Locations_Repository;

/**
 * Class that holds the functionality for the WPSEO Local Export function
 */
class Export extends Import_Export {

	/**
	 * Column headers to use when exporting to CSV.
	 *
	 * @var array
	 */
	protected $csv_column_headers = [
		'Name',
		'Address',
		'Second address',
		'City',
		'Zipcode',
		'State',
		'Country',
		'Main Phone',
		'Secondary Phone',
		'Fax',
		'Email',
		'Description',
		'Image',
		'Category',
		'URL',
		'VAT ID',
		'Tax ID',
		'Chamber of Commerce',
		'Business type',
		'Location logo',
		'Is Postal Address',
		'Custom Marker',
		'Has Multiple Opening Hours',
		'Opening hours monday from',
		'Opening hours monday to',
		'Opening hours monday second from',
		'Opening hours monday second to',
		'Opening hours tuesday from',
		'Opening hours tuesday to',
		'Opening hours tuesday second from',
		'Opening hours tuesday second to',
		'Opening hours wednesday from',
		'Opening hours wednesday to',
		'Opening hours wednesday second from',
		'Opening hours wednesday second to',
		'Opening hours thursday from',
		'Opening hours thursday to',
		'Opening hours thursday second from',
		'Opening hours thursday second to',
		'Opening hours friday from',
		'Opening hours friday to',
		'Opening hours friday second from',
		'Opening hours friday second to',
		'Opening hours saturday from',
		'Opening hours saturday to',
		'Opening hours saturday second from',
		'Opening hours saturday second to',
		'Opening hours sunday from',
		'Opening hours sunday to',
		'Opening hours sunday second from',
		'Opening hours sunday second to',
	];

	/**
	 * @var Locations_Repository
	 */
	protected $repository;

	public function __construct( Locations_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * @return array A list of conditionals that must be met to use the class
	 */
	public static function get_conditionals() {
		return [
			Admin_Conditional::class,
			Multiple_Locations_Conditional::class,
		];
	}

	/**
	 * Register hooks and filters
	 *
	 * @return void
	 */
	public function register_hooks() {
		parent::register_hooks();
		\add_action( 'wpseo_import_tab_content_inner', [ $this, 'output_export_html' ], 10 );
		\add_action( 'admin_init', [ $this, 'handle_csv_export' ], 11 );
	}

	/**
	 * Handles the CSV export
	 *
	 * @return void
	 */
	public function handle_csv_export() {
		if ( ! isset( $_POST['csv-export'] )
			|| \check_admin_referer( 'wpseo_local_export_nonce', 'wpseo_local_export_nonce_field' ) === false
		) {
			return;
		}

		$locations_arr = [];

		$repo = $this->repository;
		$repo->get();
		$locations = $repo->query;

		if ( ! $locations->have_posts() ) {
			$this->messages[] = [
				'type'    => 'error',
				'content' => \__( 'There were no locations found that met your criteria for exporting', 'yoast-local-seo' ),
			];

			return;
		}

		while ( $locations->have_posts() ) {
			$locations->the_post();

			// Get location categories.
			$terms = \get_the_terms( \get_the_ID(), 'wpseo_locations_category' );

			// And put them in a comma separated list.
			$categories = '';
			if ( ! empty( $terms ) && ! \is_wp_error( $terms ) ) {
				$category_arr = [];

				foreach ( $terms as $term ) {
					$category_arr[] = $term->slug;
				}
				$categories = \implode( ',', $category_arr );
			}

			$locations_arr[] = [
				\get_the_title(),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_address', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_address_2', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_city', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_zipcode', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_state', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_country', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_phone', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_phone_2nd', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_fax', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_email', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_contact_email', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_contact_phone', true ) ),
				\get_the_content(),
				( \has_post_thumbnail( \get_the_ID() ) ? \get_the_post_thumbnail_url( \get_the_ID() ) : '' ),
				$categories,
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_url', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_vat_id', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_tax_id', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_coc_id', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_type', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_location_logo', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_is_postal_address', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_business_location_custom_marker', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_multiple_opening_hours', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_monday_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_monday_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_monday_second_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_monday_second_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_tuesday_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_tuesday_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_tuesday_second_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_tuesday_second_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_wednesday_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_wednesday_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_wednesday_second_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_wednesday_second_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_thursday_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_thursday_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_thursday_second_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_thursday_second_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_friday_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_friday_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_friday_second_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_friday_second_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_saturday_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_saturday_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_saturday_second_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_saturday_second_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_sunday_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_sunday_to', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_sunday_second_from', true ) ),
				\esc_attr( \get_post_meta( \get_the_ID(), '_wpseo_opening_hours_sunday_second_to', true ) ),
			];
		}
		\wp_reset_postdata();

		if ( empty( $locations_arr ) ) {
			return;
		}

		\header( 'Content-type: text/csv' );
		\header( 'Content-Disposition: attachment; filename="' . \sanitize_title_with_dashes( \get_bloginfo( 'name' ) ) . '-yoast-local-seo.csv"' );

		$output = \fopen( 'php://output', 'w' );
		\fputcsv( $output, $this->csv_column_headers );
		foreach ( $locations_arr as $location ) {
			\fputcsv( $output, $location );
		}

		\fpassthru( $output );
		\fclose( $output );
		exit;
	}

	/**
	 * Output HTML for exporting WPSEO Local locations as .csv
	 *
	 * @since 3.9
	 *
	 * @return void
	 */
	public function output_export_html() {
		echo '<h2>' . \esc_html__( 'Export', 'yoast-local-seo' ) . '</h2>';
		/* translators: %s extends to <code>.csv</code> */
		echo '<p>' . \sprintf( \esc_html__( 'Export all your locations to a %s file', 'yoast-local-seo' ), '<code>.csv</code>' ) . '</p>';
		echo '<form action="" method="post">';
		// Add a NONCE field.
		\wp_nonce_field( 'wpseo_local_export_nonce', 'wpseo_local_export_nonce_field' );
		echo '<input type="submit" class="button button-primary" name="csv-export" value="' . \esc_attr__( 'Download .csv file', 'yoast-local-seo' ) . '" />';
		echo '</form>';
	}
}
