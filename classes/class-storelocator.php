<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Frontend
 */

use Yoast\WP\Local\PostType\PostType;

if ( ! class_exists( 'WPSEO_Local_Storelocator' ) ) {

	/**
	 * Class WPSEO_Local_Storelocator
	 *
	 * Adds all functionality for the store locator
	 */
	class WPSEO_Local_Storelocator {

		/**
		 * Stores the options for this plugin.
		 *
		 * @var array
		 */
		public $options = [];

		/**
		 * Admin Asset Manager object.
		 *
		 * @var WPSEO_Local_Admin_Assets
		 */
		private $asset_manager;

		/**
		 * Whether to load external stylesheet or not.
		 *
		 * @var bool
		 */
		public $load_styles = false;

		/**
		 * Default attributes for the `wpseo_storelocator` shortcode.
		 *
		 * @var array
		 */
		protected $shortcode_defaults = [
			'radius'                  => 10,
			'max_number'              => '',
			'show_radius'             => false,
			'show_nearest_suggestion' => true,
			'show_map'                => true,
			'show_filter'             => false,
			'map_width'               => '100%',
			'scrollable'              => true,
			'draggable'               => true,
			'marker_clustering'       => false,
			'show_country'            => false,
			'show_state'              => false,
			'show_phone'              => false,
			'show_phone_2'            => false,
			'show_fax'                => false,
			'show_email'              => false,
			'show_url'                => false,
			'map_style'               => 'ROADMAP',
			'show_route_label'        => '',
			'oneline'                 => false,
			'show_opening_hours'      => false,
			'hide_closed'             => false,
			'show_category_filter'    => false,
			'from_widget'             => false,
			'widget_title'            => '',
			'before_title'            => '',
			'after_title'             => '',
			'echo'                    => false,
			'width'                   => '100%',
			'height'                  => 300,
			'zoom'                    => -1,
		];

		/**
		 * Constructor.
		 */
		public function __construct() {
			/**
			 * The functionality from this class never needed in the admin area.
			 * So we bail from executing the rest if we are there.
			 */
			if ( is_admin() ) {
				return;
			}

			$this->options = get_option( 'wpseo_local' );

			if ( isset( $this->options['map_view_style'] ) ) {
				$this->shortcode_defaults['map_style'] = $this->options['map_view_style'];
			}

			if ( isset( $this->options['show_route_label'] ) && ! empty( $this->options['show_route_label'] ) ) {
				$this->shortcode_defaults['show_route_label'] = $this->options['show_route_label'];
			}
			else {
				$this->shortcode_defaults['show_route_label'] = __( 'Show route', 'yoast-local-seo' );
			}

			add_shortcode( 'wpseo_storelocator', [ $this, 'show_storelocator' ] );

			add_action( 'wp_head', [ $this, 'load_scripts' ], 99 );
		}

		/**
		 * Enqueue the scripts necessary for the Store Locator to work.
		 *
		 * The wp-polyfill asset is needed for versions of WP before 5.0.
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			$this->asset_manager = new WPSEO_Local_Admin_Assets();

			$this->asset_manager->enqueue_script( 'store-locator' );
			$this->asset_manager->enqueue_script( 'google-maps' );

			$default_country = isset( $this->options['default_country'] ) ? WPSEO_Local_Frontend::get_country( $this->options['default_country'] ) : '';
			wp_localize_script( WPSEO_Local_Admin_Assets::PREFIX . 'store-locator', 'storeLocator', [ 'defaultCountry' => $default_country ] );

			add_action( 'wp_footer', 'wpseo_enqueue_geocoder' );
			add_action( 'admin_footer', 'wpseo_enqueue_geocoder' );
		}

		/**
		 * Outputs HTML for the store locator.
		 *
		 * @param array $atts Array of attributes for the store locator shortcode.
		 *
		 * @return string
		 */
		public function show_storelocator( $atts ) {
			global $wpseo_enqueue_geocoder, $wpseo_sl_load_scripts;
			$this->enqueue_scripts();

			// Don't show any output when you don't have multiple locations enabled.
			if ( wpseo_has_multiple_locations() === false ) {
				return '';
			}

			$wpseo_sl_load_scripts = true;
			$atts                  = wpseo_check_falses( shortcode_atts( $this->shortcode_defaults, $atts ) );

			if ( $atts['show_map'] ) {
				$wpseo_enqueue_geocoder = true;
			}

			ob_start();
			?>
			<!--local_seo_store_locator_start-->
			<form action="#wpseo-storelocator-form" method="post" id="wpseo-storelocator-form" data-form="wpseo-local-store-locator">
				<fieldset>
					<?php
					$search_string    = isset( $_REQUEST['wpseo-sl-search'] ) ? esc_attr( $_REQUEST['wpseo-sl-search'] ) : '';
					$sl_category_term = ! empty( $_REQUEST['wpseo-sl-category'] ) ? $_REQUEST['wpseo-sl-category'] : '';
					?>
					<p>
						<label for="wpseo-sl-search"><?php echo apply_filters( 'yoast-local-seo-search-label', __( 'Enter your postal code, city and / or state', 'yoast-local-seo' ) ); ?></label>
						<input type="text" name="wpseo-sl-search" id="wpseo-sl-form-search" value="<?php echo esc_attr( $search_string ); ?>">
						<input type="hidden" name="wpseo-sl-lat" id="wpseo-sl-form-lat" value="">
						<input type="hidden" name="wpseo-sl-lng" id="wpseo-sl-form-lng" value="">

						<?php
						// Show icon for retrieving current location.
						if ( wpseo_may_use_current_location() === true ) {
							echo ' <button type="button" class="wpseo_use_current_location" data-target="wpseo-sl-form-search"><img src="' . esc_url( plugins_url( 'images/location-icon.svg', WPSEO_LOCAL_FILE ) ) . '" class="wpseo_use_current_location_image" height="24" width="24" alt="' . esc_attr__( 'Use my current location', 'yoast-local-seo' ) . '" data-loading-text="' . esc_attr__( 'Determining current location', 'yoast-local-seo' ) . '"></button> ';
						}

						// Show the radius selectbox.
						if ( $atts['show_radius'] ) {
							esc_html_e( 'within', 'yoast-local-seo' );
							?>
							<select name="wpseo-sl-radius" id="wpseo-sl-radius">
								<?php
								$radius_array    = [ 1, 5, 10, 25, 50, 100, 250, 500, 1000 ];
								$selected_radius = ! empty( $_REQUEST['wpseo-sl-radius'] ) ? esc_attr( $_REQUEST['wpseo-sl-radius'] ) : $atts['radius'];

								foreach ( $radius_array as $radius ) {
									echo '<option value="' . (int) $radius . '" ' . selected( $selected_radius, $radius, false ) . '>' . (int) $radius . ( ( $this->options['unit_system'] === 'METRIC' ) ? 'km' : 'mi' ) . '</option>';
								}
								?>
							</select>
							<?php
						}
						else {
							?>
							<input type="hidden" name="wpseo-sl-radius" id="wpseo-sl-radius-text" value="<?php echo esc_attr( $atts['radius'] ); ?>">
							<?php
						}
						?>
					</p>

					<?php if ( $atts['show_filter'] ) { ?>
						<?php
						$terms = get_terms( 'wpseo_locations_category' );
						?>
						<?php if ( count( $terms ) > 0 ) { ?>
							<p class="sl-filter">
								<label for="wpseo-sl-category"><?php esc_html_e( 'Filter by category', 'yoast-local-seo' ); ?></label>
								<select name="wpseo-sl-category" id="wpseo-sl-category">
									<option value=""></option>
									<?php
									foreach ( $terms as $term ) {
										echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( $sl_category_term, $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
									}
									?>
								</select>
							</p>
						<?php } ?>
					<?php } ?>

					<p class="sl-submit">
						<input type="submit" value="<?php esc_attr_e( 'Search', 'yoast-local-seo' ); ?>">
					</p>

				</fieldset>
			</form>

			<div id="wpseo-storelocator-results">
				<?php
				$results = false;

				if ( empty( $_POST ) === false ) {
					$results = $this->get_results( $atts );
				}

				if ( $atts['show_map'] ) {
					$location_ids = [];
					$ids          = 'all';
					if ( ! empty( $_POST ) && ! is_wp_error( $results ) ) {
						foreach ( $results['locations'] as $location ) {
							$location_ids[] = $location['ID'];
						}
						$ids = implode( ',', $location_ids );
					}

					$map_atts = [
						'id'                   => $ids,
						'max_number'           => $atts['max_number'],
						'from_sl'              => true,
						'show_route'           => true,
						'scrollable'           => $atts['scrollable'],
						'draggable'            => $atts['draggable'],
						'marker_clustering'    => $atts['marker_clustering'],
						'map_style'            => $atts['map_style'],
						'show_category_filter' => $atts['show_category_filter'],
						'zoom'                 => $atts['zoom'],
						'width'                => $atts['width'],
						'height'               => $atts['height'],
						'show_phone'           => $atts['show_phone'],
						'show_phone_2'         => $atts['show_phone_2'],
						'show_fax'             => $atts['show_fax'],
						'show_country'         => $atts['show_country'],
						'show_email'           => $atts['show_email'],
						'show_url'             => $atts['show_url'],
					];
					echo wpseo_local_show_map( $map_atts );
				}

				if ( empty( $_POST ) === false ) {
					if ( ! is_wp_error( $results ) ) {
						$show_suggestion = $results['in_radius'] <= 0 && $atts['show_nearest_suggestion'] === true && ! empty( $results['locations'] );

						if ( $results['in_radius'] > 0 ) {
							$number = count( $results['locations'] );
							/* translators: %d extends to the number of found locations in the radius */
							echo '<h2>' . sprintf( esc_html( _n( '%d result has been found', '%d results have been found', $number, 'yoast-local-seo' ) ), (int) $number ) . '</h2>';

							foreach ( $results['locations'] as $key => $location ) {
								$this->get_location_details( $location['ID'], $atts );
							}
						}
						else {
							echo '<h2>' . esc_html__( 'No results found', 'yoast-local-seo' ) . '</h2>';

							if ( $show_suggestion ) {
								foreach ( $results['locations'] as $location ) {
									/* translators: %s extends to the distance in miles */
									$text_mi = sprintf( __( 'The nearest location is %s miles away', 'yoast-local-seo' ), $location['distance'] );
									/* translators: %s extends to the distance in kilometers */
									$text_km = sprintf( __( 'The nearest location is %s kilometers away', 'yoast-local-seo' ), $location['distance'] );

									$text = ( ( $this->options['unit_system'] === 'METRIC' ) ? $text_km : $text_mi );
									$text = apply_filters_deprecated( 'wpso_local_no_stores_in_radius', [ $text ], 'WPSEO Local 12.3', 'Yoast\WP\Local\no_stores_in_radius' );
									$text = apply_filters( 'Yoast\WP\Local\no_stores_in_radius', $text );

									echo '<p class="nearest_location">' . esc_html( $text ) . '</p>';

									$this->get_location_details( $location['ID'], $atts );
								}
							}
						}
					}
					else {
						echo '<h2>' . esc_html__( 'No results found', 'yoast-local-seo' ) . '</h2>';
					}
				}
				?>
			</div><!--local_seo_store_locator_end-->

			<?php
			$output = ob_get_contents();
			ob_end_clean();

			return $output;
		}

		/**
		 * Retrieves the search results based on given search term (zipcode or city).
		 *
		 * @param array $atts Array of attributes for the store locator shortcode.
		 *
		 * @return array | WP_Error
		 */
		public function get_results( $atts ) {
			if ( empty( $_POST['wpseo-sl-search'] ) ) {
				return new WP_Error( 'wpseo-no-input', __( 'Please enter a zipcode or city', 'yoast-local-seo' ) );
			}

			$nr_results = $this->get_nr_results( $atts );

			$locations = $this->get_locations( $nr_results, false );

			$count_in_radius = $locations['count'];

			if ( $count_in_radius === 0 ) {
				$locations = $this->get_locations( $nr_results, true );
			}

			return [
				'in_radius' => $count_in_radius,
				'locations' => $locations['results'],
			];
		}

		/**
		 * Fetch location results to work with before returning processed results
		 *
		 * @param int  $nr_results           The amount of location results to display.
		 * @param bool $check_outside_radius Determines whether we should search locations inside or outside the radius.
		 *
		 * @return array
		 */
		private function get_locations( $nr_results, $check_outside_radius ) {
			global $wpdb;

			$metric           = ( $this->options['unit_system'] === 'METRIC' ) ? 'km' : 'mi';
			$radius           = ( ! empty( $_REQUEST['wpseo-sl-radius'] ) ) ? $_REQUEST['wpseo-sl-radius'] : 99999;
			$sl_category_term = ( ! empty( $_REQUEST['wpseo-sl-category'] ) ) ? $_REQUEST['wpseo-sl-category'] : '';
			$distances        = [
				'in_radius' => 0,
				'locations' => [],
			];

			$search_string = isset( $_REQUEST['wpseo-sl-search'] ) ? esc_attr( $_REQUEST['wpseo-sl-search'] ) : '';
			if ( $search_string === '' ) {
				return $distances;
			}

			$coordinates = (object) [
				'lat' => floatval( $_POST['wpseo-sl-lat'] ),
				'lng' => floatval( $_POST['wpseo-sl-lng'] ),
			];

			if ( ! $coordinates ) {
				return new WP_Error( 'wpseo-get-results-error', __( 'No valid coordinates. We cannot complete the search.', 'yoast-local-seo' ) );
			}

			$replacements = [ $coordinates->lat, $coordinates->lng, $coordinates->lat ];

			// Extend SQL with category filter.
			$inner_join = '';
			if ( $sl_category_term !== '' ) {
				$inner_join .= "
				INNER JOIN $wpdb->term_relationships AS term_rel ON p.ID = term_rel.object_id
				INNER JOIN $wpdb->term_taxonomy AS taxo ON term_rel.term_taxonomy_id = taxo.term_taxonomy_id
				AND taxo.taxonomy = 'wpseo_locations_category'
				AND taxo.term_id = %s
				";

				$replacements[] = $sl_category_term;
			}

			$post_type_instance = new PostType();
			$post_type_instance->initialize();

			// Set post type.
			$replacements[] = $post_type_instance->get_post_type();

			/*
			 * Get all coordinates from posts.
			 */

			$post_status = [ 'publish' ];

			// If the user is logged in and can edit posts, add more post statuses.
			if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
				array_push( $post_status, 'draft', 'future', 'pending', 'private' );
			}

			$replacements = array_merge( $replacements, $post_status );

			// Distance query/parameter.
			$distance_value            = $this->get_distance_for_query( $radius, $metric );
			$query_inside_radius_check = 'HAVING distance < %d';
			$replacements[]            = $distance_value;

			// For outside checking we'll limit the number of results to 1.
			if ( $check_outside_radius ) {
				$nr_results = 1;

				// Removing the distance query/parameter.
				$query_inside_radius_check = '';
				array_pop( $replacements );
			}

			$replacements[] = $nr_results;

			/*
			 * The `$inner_join` variable is SQL being "concatenated" in, not a variable which needs to be prepared.
			 * @phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			 */
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID, m1.meta_value as lat, m2.meta_value as lng,
					ROUND( ( 6366.564864 * acos ( cos ( radians(%f) ) * cos( radians( m1.meta_value ) ) * cos( radians( m2.meta_value ) - radians(%f) ) + sin ( radians(%f) ) * sin( radians( m1.meta_value ) ) ) ), 2 ) AS distance
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} m1 ON p.ID = m1.post_id
					INNER JOIN {$wpdb->postmeta} m2 ON p.ID = m2.post_id
					$inner_join
					WHERE
						p.post_type = %s AND
						p.post_status IN( " . implode( ',', array_fill( 0, count( $post_status ), '%s' ) ) . " ) AND
						m1.meta_key = '_wpseo_coordinates_lat' AND
						m2.meta_key = '_wpseo_coordinates_long'
					GROUP BY p.ID, lat, lng
					$query_inside_radius_check
					ORDER BY distance ASC
					LIMIT 0, %d",
					$replacements
				),
				ARRAY_A
			);
			// phpcs:enable

			$location_count = $wpdb->num_rows;

			return [
				'count'   => $location_count,
				'results' => $results,
			];
		}

		/**
		 * Determines the number of results to display
		 *
		 * @param array $atts Array of attributes for the store locator shortcode.
		 *
		 * @return int
		 */
		private function get_nr_results( $atts ) {
			$value = 10;

			if ( ! empty( $this->options['sl_num_results'] ) ) {
				$value = $this->options['sl_num_results'];
			}

			if ( ! empty( $atts['max_number'] ) && $atts['max_number'] <= $value ) {
				$value = $atts['max_number'];
			}

			return $value;
		}

		/**
		 * Calculates distances from select value with defined metric for queries expecting KM metric.
		 *
		 * @param float  $value  Distance.
		 * @param string $metric Whether the distance is in 'km' or 'mi' (miles).
		 *
		 * @return float
		 */
		public function get_distance_for_query( $value, $metric ) {
			$km = $value;

			if ( $metric === 'mi' ) {
				$km = ( $value * 1.60934 );
			}

			return $km;
		}

		/**
		 * Calculates distances from original KM to available metrics.
		 *
		 * @param float $km Distance in KM.
		 *
		 * @return array
		 */
		public function get_distance( $km ) {
			$miles = ( $km * 0.621371 );

			return [
				'mi' => $miles,
				'km' => $km,
			];
		}

		/**
		 * Load jQuery script (if not already loaded before).
		 *
		 * @return void
		 */
		public function load_scripts() {
			if ( wp_script_is( 'jquery', 'done' ) === false && apply_filters( 'wpseo_local_load_jquery', true ) !== false ) {
				wp_enqueue_script( 'jquery', '//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js', [], '1.10.2', true );
			}
		}

		/**
		 * Show all location information.
		 *
		 * @param int   $location_id Post ID of the location.
		 * @param array $atts        Array of attributes, used for displaying the address.
		 *                           These are matching attributes for the wpseo_local_show_address() method.
		 *
		 * @return void
		 */
		public function get_location_details( $location_id, $atts ) {
			$coords_lat  = get_post_meta( $location_id, '_wpseo_coordinates_lat', true );
			$coords_long = get_post_meta( $location_id, '_wpseo_coordinates_long', true );
			?>

			<div class="wpseo-result">
				<?php
				$address_atts = [
					'id'                 => $location_id,
					'show_state'         => $atts['show_state'],
					'show_country'       => $atts['show_country'],
					'show_phone'         => $atts['show_phone'],
					'show_phone_2'       => $atts['show_phone_2'],
					'show_fax'           => $atts['show_fax'],
					'show_email'         => $atts['show_email'],
					'show_url'           => $atts['show_url'],
					'show_opening_hours' => $atts['show_opening_hours'],
					'hide_closed'        => $atts['hide_closed'],
					'oneline'            => $atts['oneline'],
					'from_sl'            => true,
					'echo'               => false,
					'hide_json_ld'       => true,
				];
				$location     = wpseo_local_show_address( $address_atts );

				echo apply_filters( 'wpseo_local_sl_result', $location, $location_id );
				?>
				<div class="wpseo-sl-route">
					<a href="javascript:;" onclick="wpseo_sl_show_route( this, '<?php echo $coords_lat; ?>', '<?php echo $coords_long; ?>' );"><?php echo $atts['show_route_label']; ?></a>
				</div>
			</div>
			<?php
		}
	}
}

if ( ! function_exists( 'wpseo_local_storelocator' ) ) {
	/**
	 * Initialize the store locator.
	 *
	 * @param array $atts Array of attributes for displaying the store locator.
	 *
	 * @return string
	 */
	function wpseo_local_storelocator( $atts ) {
		global $wpseo_local_storelocator;

		if ( $wpseo_local_storelocator === null ) {
			$wpseo_local_storelocator = new WPSEO_Local_Storelocator();
		}

		return $wpseo_local_storelocator->show_storelocator( $atts );
	}
}
$wpseo_sl_load_scripts = false;
