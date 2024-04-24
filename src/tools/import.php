<?php

namespace Yoast\WP\Local\Tools;

use WPSEO_Local_Admin_Wrappers;
use WPSEO_Local_Core;
use Yoast\WP\Local\Conditionals\Admin_Conditional;
use Yoast\WP\Local\Conditionals\Multiple_Locations_Conditional;
use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\Local\Repositories\Business_Types_Repository;

/**
 * Class that holds the functionality for the WPSEO Local Import function
 *
 * @since 3.9
 */
class Import extends Import_Export {

	/**
	 * @var PostType
	 */
	private $post_type;

	/**
	 * Contains the Business Types Repository.
	 *
	 * @var Business_Types_Repository
	 */
	private $business_types_repository;

	/**
	 * Import constructor.
	 *
	 * @param Business_Types_Repository $business_types_repository The business type repository as a dependency.
	 * @param PostType                  $post_type                 The post type object as a dependency.
	 */
	public function __construct( Business_Types_Repository $business_types_repository, PostType $post_type ) {
		$this->business_types_repository = $business_types_repository;
		$this->post_type                 = $post_type;
	}

	/**
	 * Returns a list of conditionals that must be met.
	 *
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
		\add_action( 'admin_init', [ $this, 'handle_csv_import' ], 11 );
		\add_action( 'wpseo_import_tab_content_inner', [ $this, 'import_html' ], 11 );
	}

	/**
	 * Handle the import of the uploaded .csv file.
	 *
	 * @return void
	 */
	public function handle_csv_import() {
		if ( ! isset( $_POST['csv-import'] )
			|| \check_admin_referer( 'wpseo_local_import_nonce', 'wpseo_local_import_nonce_field' ) === false
		) {
			return;
		}

		if ( ! isset( $_FILES['wpseo']['name']['csvuploadlocations'] )
			|| ! \is_string( $_FILES['wpseo']['name']['csvuploadlocations'] )
			|| ! isset( $_FILES['wpseo']['tmp_name']['csvuploadlocations'] )
		) {
			return;
		}

		/**
		 * Set max execution time to 3600 seconds (or 1 hour)
		 *
		 * @todo See if we can do in batches, to avoid tempering with execution times
		 */
		\set_time_limit( 3600 );

		$count = 0;

		$file_name     = \sanitize_file_name( \wp_unslash( $_FILES['wpseo']['name']['csvuploadlocations'] ) );
		$tmp_file_name = \wp_unslash( $_FILES['wpseo']['tmp_name']['csvuploadlocations'] );

		$csv_path = $this->wpseo_upload_dir . \basename( $file_name );

		if ( ! empty( $_FILES['wpseo'] ) && ! \move_uploaded_file( $tmp_file_name, $csv_path ) ) {
			$this->messages[] = [
				'type'    => 'error',
				'content' => \sprintf(
					/* translators: %s expands to the location of the WPSEO upload dir. */
					\esc_html__( 'Sorry, there was an error while uploading the CSV file.<br>Please make sure the %s directory is writable (chmod 777).', 'yoast-local-seo' ),
					$this->wpseo_upload_dir
				),
			];

			return;
		}

		$is_simplemap_import = ! empty( $_POST['is-simplemap-import'] ) && $_POST['is-simplemap-import'] == '1';

		$separator = ',';
		if ( ( ! empty( $_POST['csv_separator'] ) && $_POST['csv_separator'] === 'semicolon' ) && $is_simplemap_import === false ) {
			$separator = ';';
		}

		// Get location data from CSV.
		$column_names = $this->columns;

		// If a simplemap import is used, overwrite our WPSEO Local columns.
		if ( $is_simplemap_import ) {
			$column_names = [
				'name',
				'address',
				'address2',
				'city',
				'state',
				'zipcode',
				'country',
				'phone',
				'email',
				'fax',
				'url',
				'description',
				'special',
				'lat',
				'long',
				'pubdate',
				'category',
				'tag',
			];
		}

		$handle = \fopen( $csv_path, 'r' );

		if ( ! $handle ) {
			$this->messages[] = [
				'type'    => 'error',
				'content' => \esc_html__( 'There seems to be an error reading the uploaded file.', 'yoast-local-seo' ),
			];

			return;
		}

		$locations = [];
		$row       = 0;
		while ( ( $csvdata = \fgetcsv( $handle, 2000, $separator ) ) !== false ) {
			if ( $row > 0 ) {
				$tmp_location           = [];
				$column_name_count      = \count( $column_names );
				$simplemap_skip_columns = [
					'email',
					'url',
					'special',
					'pubdate',
					'tag',
				];

				for ( $i = 0; $i < $column_name_count; $i++ ) {

					// Skip columns for simplemap import.
					if ( $is_simplemap_import
						&& \in_array( $column_names[ $i ], $simplemap_skip_columns, true )
					) {
						continue;
					}

					if ( isset( $csvdata[ $i ] ) ) {
						$tmp_location[ $column_names[ $i ] ] = \addslashes( $csvdata[ $i ] );
					}
				}
				\array_push( $locations, $tmp_location );
			}
			++$row;
		}
		\fclose( $handle );

		$debug = false;

		/**
		 * The WPSEO Local Core class (TODO refactor out)
		 *
		 * @var WPSEO_Local_Core $wpseo_local_core
		 */
		global $wpseo_local_core;

		$business_types = $this->business_types_repository->get_business_types();
		\array_walk( $business_types, 'wpseo_local_sanitize_business_types' );

		foreach ( $locations as $location ) {
			// Create standard post data.
			$current_post                 = [];
			$current_post['ID']           = '';
			$current_post['post_title']   = ( $location['name'] ?? '' );
			$current_post['post_content'] = ( $location['description'] ?? '' );
			$current_post['post_status']  = 'publish';
			$current_post['post_type']    = $this->post_type->get_post_type();

			$post_id = \wp_insert_post( $current_post );

			if ( ! $debug ) {
				// Insert custom fields for location details.
				if ( ! empty( $post_id ) ) {
					\add_post_meta( $post_id, '_wpseo_business_name', isset( $location['name'] ) ? \sanitize_text_field( $location['name'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_address', isset( $location['address'] ) ? \sanitize_text_field( $location['address'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_address_2', isset( $location['address'] ) ? \sanitize_text_field( $location['address_2'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_city', isset( $location['city'] ) ? \sanitize_text_field( $location['city'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_state', isset( $location['state'] ) ? \sanitize_text_field( $location['state'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_zipcode', isset( $location['zipcode'] ) ? \sanitize_text_field( $location['zipcode'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_country', isset( $location['country'] ) ? \sanitize_text_field( $location['country'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_phone', isset( $location['phone'] ) ? \sanitize_text_field( $location['phone'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_fax', isset( $location['fax'] ) ? \sanitize_text_field( $location['fax'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_email', isset( $location['email'] ) ? \sanitize_email( $location['email'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_contact_email', isset( $location['contact_email'] ) ? \sanitize_email( \wp_unslash( $location['contact_email'] ) ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_contact_phone', isset( $location['contact_phone'] ) ? \sanitize_text_field( \wp_unslash( $location['contact_phone'] ) ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_vat_id', isset( $location['vat_id'] ) ? \sanitize_text_field( $location['vat_id'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_tax_id', isset( $location['tax_id'] ) ? \sanitize_text_field( $location['tax_id'] ) : '', true );
					\add_post_meta( $post_id, '_wpseo_business_coc_id', isset( $location['coc_id'] ) ? \sanitize_text_field( $location['coc_id'] ) : '', true );

					if ( isset( $location['phone_2nd'] ) ) {
						\add_post_meta( $post_id, '_wpseo_business_phone_2nd', $location['phone_2nd'], true );
					}

					if ( isset( $location['category'] ) ) {
						// Allow for a comma separated list to be used.
						$categories = \explode( ',', $location['category'] );

						// Remove possible spaces.
						\array_walk( $categories, 'trim' );

						// And finally set the terms in the locations category.
						\wp_set_object_terms( $post_id, $categories, 'wpseo_locations_category' );
					}

					if ( isset( $location['business_type'] ) ) {
						$business_type = $location['business_type'];
						if ( ! isset( $business_types[ $business_type ] ) ) {
							$business_type = \array_search( $business_type, $business_types, true );
						}

						\add_post_meta( $post_id, '_wpseo_business_type', $business_type, true );
					}

					if ( isset( $location['url'] ) ) {
						\add_post_meta( $post_id, '_wpseo_business_url', \sanitize_text_field( $location['url'] ), true );
					}

					// Replace comma's into points.
					if ( isset( $location['lat'] ) ) {
						$location['lat'] = \str_replace( ',', '.', $location['lat'] );
						\add_post_meta( $post_id, '_wpseo_coordinates_lat', \sanitize_text_field( $location['lat'] ), true );
					}
					if ( isset( $location['long'] ) ) {
						$location['long'] = \str_replace( ',', '.', $location['long'] );
						\add_post_meta( $post_id, '_wpseo_coordinates_long', \sanitize_text_field( $location['long'] ), true );
					}

					// If just a postal adddress, check box.
					if ( $location['is_postal_address'] == '1' ) {
						\update_post_meta( $post_id, '_wpseo_is_postal_address', '1' );
					}

					// Add image as post thumbnail.
					if ( ! empty( $location['image'] ) ) {
						$wpseo_local_core->insert_attachment( $post_id, $location['image'], true );
					}

					if ( ! empty( $location['location_logo'] ) ) {
						$logo_id = $wpseo_local_core->insert_attachment( $post_id, $location['location_logo'], false );
						\update_post_meta( $post_id, '_wpseo_business_location_logo', \wp_get_attachment_image_url( $logo_id, 'full' ) );
					}

					if ( ! empty( $location['custom_marker'] ) ) {
						$marker_id = $wpseo_local_core->insert_attachment( $post_id, $location['custom_marker'], false );
						\update_post_meta( $post_id, '_wpseo_business_location_custom_marker', \wp_get_attachment_image_url( $marker_id ) );
					}

					// Opening hours.
					foreach ( $wpseo_local_core->days as $key => $day ) {
						if ( isset( $location[ 'opening_hours_' . $key . '_from' ] ) && ! empty( $location[ 'opening_hours_' . $key . '_from' ] ) && isset( $location[ 'opening_hours_' . $key . '_to' ] ) && ! empty( $location[ 'opening_hours_' . $key . '_to' ] ) ) {
							if ( \strtolower( $location[ 'opening_hours_' . $key . '_from' ] ) === 'closed' || \strtolower( $location[ 'opening_hours_' . $key . '_to' ] ) === 'closed' ) {
								\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_from', 'closed', true );
							}
							else {
								$time_from = \strtotime( $location[ 'opening_hours_' . $key . '_from' ] );
								$time_to   = \strtotime( $location[ 'opening_hours_' . $key . '_to' ] );

								if ( $time_from !== false && $time_to !== false ) {
									\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_from', \gmdate( 'H:i', $time_from ), true );
									\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_to', \gmdate( 'H:i', $time_to ), true );
								}
								else {
									\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_from', 'closed', true );
									if ( $time_from === false ) {
										$this->messages[] = [
											'type'    => 'error',
											'content' => \sprintf(
												/* translators: %s extends to Location opening hours from per week day */
												\__( '%s is not a valid time notation', 'yoast-local-seo' ),
												$location[ 'opening_hours_' . $key . '_from' ]
											),
										];
									}
									elseif ( $time_to === false ) {
										$this->messages[] = [
											'type'    => 'error',
											'content' => \sprintf(
												/* translators: %s extends to Location opening hours to per week day */
												\__( '%s is not a valid time notation', 'yoast-local-seo' ),
												$location[ 'opening_hours_' . $key . '_to' ]
											),
										];
									}
								}

								if ( $location['multiple_opening_hours'] === 'on' ) {
									// Multiple openingtimes are set. Enable them in the backend.
									\update_post_meta( $post_id, '_wpseo_multiple_opening_hours', 'on', true );
								}

								if ( isset( $location[ 'opening_hours_' . $key . '_second_from' ] ) && ! empty( $location[ 'opening_hours_' . $key . '_second_from' ] ) && isset( $location[ 'opening_hours_' . $key . '_second_to' ] ) && ! empty( $location[ 'opening_hours_' . $key . '_second_to' ] ) ) {
									if ( isset( $location[ 'opening_hours_' . $key . '_second_from' ] ) && ! empty( $location[ 'opening_hours_' . $key . '_from' ] ) && isset( $location[ 'opening_hours_' . $key . '_second_to' ] ) && ! empty( $location[ 'opening_hours_' . $key . '_to' ] ) ) {
										if ( \strtolower( $location[ 'opening_hours_' . $key . '_second_from' ] ) === 'closed' || \strtolower( $location[ 'opening_hours_' . $key . '_second_to' ] ) === 'closed' ) {
											\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_second_from', 'closed', true );
										}
									}
									else {
										$time_second_from = \strtotime( $location[ 'opening_hours_' . $key . '_second_from' ] );
										$time_second_to   = \strtotime( $location[ 'opening_hours_' . $key . '_second_to' ] );

										if ( $time_second_from !== false && $time_second_to !== false ) {
											\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_second_from', \gmdate( 'H:i', $time_second_from ), true );
											\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_second_to', \gmdate( 'H:i', $time_second_to ), true );
										}
										else {
											\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_second_from', 'closed', true );
											if ( $time_second_from === false ) {
												$this->messages[] = [
													'type'    => 'error',
													'content' => \sprintf(
														/* translators: %s extends to Location second from opening hours per week day */
														\__( '%s is not a valid time notation', 'yoast-local-seo' ),
														$location[ 'opening_hours_' . $key . '_second_from' ]
													),
												];
											}
											elseif ( $time_second_to === false ) {
												$this->messages[] = [
													'type'    => 'error',
													'content' => \sprintf(
														/* translators: %s extends to Location second to opening hours per week day */
														\__( '%s is not a valid time notation', 'yoast-local-seo' ),
														$location[ 'opening_hours_' . $key . '_second_to' ]
													),
												];
											}
										}
									}
								}
								else {
									\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_second_from', 'closed', true );
								}
							}
						}
						else {
							\add_post_meta( $post_id, '_wpseo_opening_hours_' . $key . '_from', 'closed', true );
						}
					}

					++$count;
					$last_imported = $post_id;
				}
			}
		}

		if ( $count > 0 ) {
			$this->messages[] = [
				'type'    => 'success',
				'content' => \sprintf(
					/* translators: 1: nr of locations found; 2: link to post type overview; 3: link close tag. */
					\__( '%1$d locations found and successfully imported %2$shere%3$s', 'yoast-local-seo' ),
					$count,
					'<a href="' . \esc_url( \get_admin_url( null, 'edit.php?post_type=' . $this->post_type->get_post_type() ) ) . '">',
					'</a>'
				),
			];
		}
	}

	/**
	 * Builds the HTML for the import form.
	 *
	 * @return void
	 */
	public function import_html() {
		echo '<h2>' . \esc_html__( 'Import', 'yoast-local-seo' ) . '</h2>';

		/* translators: %1$s extends to link opening tag; %2$s closes the tag */
		echo '<p>' . \sprintf( \esc_html__( 'View the %1$sdocumentation%2$s to check what format of the CSV file should be.', 'yoast-local-seo' ), '<a href="https://yoast.com/question/csv-import-file-local-seo-look-like/" target="_blank">', '</a>' ) . '</p>';

		echo '<form action="" method="post" enctype="multipart/form-data">';
		WPSEO_Local_Admin_Wrappers::file_upload( 'csvuploadlocations', \__( 'Upload CSV', 'yoast-local-seo' ) );
		echo '<label for="csv_separator" class="checkbox">' . \esc_html__( 'Column separator', 'yoast-local-seo' ) . '</label>';
		echo '<select class="textinput" id="csv_separator" name="csv_separator">';
		echo '<option value="comma">' . \esc_html__( 'Comma', 'yoast-local-seo' ) . '</option>';
		echo '<option value="semicolon">' . \esc_html__( 'Semicolon', 'yoast-local-seo' ) . '</option>';
		echo '</select>';
		echo '<br class="clear">';
		echo '<p>';
		echo '<input class="checkbox double" id="is-simplemap-import" type="checkbox" name="is-simplemap-import" value="1"> ';
		echo '<label for="is-simplemap-import">' . \esc_html__( 'This CSV is exported by the SimpleMap plugin', 'yoast-local-seo' ) . '</label>';
		echo '</p>';
		echo '<br class="clear">';
		echo '<br/>';

		echo '<p><em>' . \esc_html__( 'Note', 'yoast-local-seo' ) . ': ' . \esc_html__( 'The Geocoding API is limited to 2,500 queries a day, so when you have large CSV files, with no coordinates, cut them in pieces of 2,500 rows and import them one a day. Indeed, it\'s not funny. It\'s reality.', 'yoast-local-seo' ) . '</em></p>';

		if ( ! \is_writable( $this->wpseo_upload_dir ) ) {
			/* translators: %s extends to the upload directory */
			echo '<p>' . \sprintf( \esc_html__( 'Make sure the %s directory is writeable.', 'yoast-local-seo' ), '<code>"' . \esc_html( $this->wpseo_upload_dir ) . '"</code>' ) . '</p>';
		}

		// Add a NONCE field.
		\wp_nonce_field( 'wpseo_local_import_nonce', 'wpseo_local_import_nonce_field' );

		echo '<input type="submit" class="button button-primary" name="csv-import" value="Import" ' . ( ! \is_writable( $this->wpseo_upload_dir ) ? ' disabled="disabled"' : '' ) . ' />';
		echo '</form>';
	}
}
