<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Frontend
 * @since   7.1
 */

use Yoast\WP\Local\PostType\PostType;

if ( ! class_exists( 'WPSEO_Local_Search' ) ) {

	/**
	 * Class WPSEO_Local_Search
	 *
	 * Add functionality for enhancing the search engine and page
	 */
	class WPSEO_Local_Search {

		/**
		 * Stores the options for this plugin.
		 *
		 * @var array
		 */
		private $search_fields = [];

		/**
		 * @var bool
		 */
		private $enhanced_search_result_enabled = true;

		/**
		 * Holds the global wpdb variable.
		 *
		 * @var wpdb
		 */
		private $wpdb;

		/**
		 * @var string The post type used for Yoast SEO: Local locations.
		 */
		private $local_post_type;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->run();
		}

		/**
		 * Run all the needed actions.
		 *
		 * @return void
		 */
		public function run() {
			add_action( 'pre_get_posts', [ $this, 'enhance_search' ] );
			add_filter( 'the_excerpt', [ $this, 'enhance_location_search_results' ] );
			$post_type = new PostType();
			$post_type->initialize();
			$this->local_post_type = $post_type->get_post_type();
		}

		/**
		 * Enhance the WordPress search to search in WPSEO Local locations meta data.
		 *
		 * @return void
		 */
		public function enhance_search() {
			if ( is_search() && $this->is_enhanced_search_enabled() && ! is_admin() && ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] === $this->local_post_type ) ) {
				global $wpdb;
				$this->wpdb = $wpdb;

				$this->set_search_fields();

				add_filter( 'posts_where', [ $this, 'where' ], 99, 1 );
				add_filter( 'posts_join', [ $this, 'join' ], 99, 1 );
				add_filter( 'posts_groupby', [ $this, 'groupby' ], 99, 1 );
			}
		}

		/**
		 * @param string $where The WHERE clause for the search query.
		 *
		 * @return mixed
		 */
		public function where( $where ) {
			$meta_query = '';
			$where      = $this->wpdb->remove_placeholder_escape( $where );

			foreach ( $this->search_fields as $field ) {
				$meta_query .= '((' . $this->wpdb->postmeta . ".meta_key = '" . $field . "')";
				$meta_query .= ' AND (' . $this->wpdb->postmeta . ".meta_value  LIKE '%" . get_search_query() . "%')) OR ";
			}

			$where = str_replace( '(((' . $this->wpdb->posts . ".post_title LIKE '%", '( ' . $meta_query . ' ((' . $this->wpdb->posts . ".post_title LIKE '%", $where );

			$where = $this->wpdb->remove_placeholder_escape( $where );

			return $where;
		}

		/**
		 * @param string $join The JOIN clause for the search query.
		 *
		 * @return mixed
		 */
		public function join( $join ) {
			$join .= ' INNER JOIN ' . $this->wpdb->postmeta . ' ON (' . $this->wpdb->posts . '.ID = ' . $this->wpdb->postmeta . '.post_id)';

			return $join;
		}

		/**
		 * @param string $groupby The GROUPBY clause for the search query.
		 *
		 * @return mixed
		 */
		public function groupby( $groupby ) {
			$groupby = $this->wpdb->posts . '.ID';

			return $groupby;
		}

		/**
		 * Add address to locations in search results
		 *
		 * @since 1.3.8
		 *
		 * @param string $excerpt The excerpt which will be changed by this method.
		 *
		 * @return string
		 */
		public function enhance_location_search_results( $excerpt ) {
			if ( is_search() && $this->is_enhanced_search_result_enabled() && $this->is_enhanced_search_enabled() ) {
				global $post;

				if ( get_post_type( $post->ID ) === $this->local_post_type ) {
					$atts = [
						'id'           => $post->ID,
						'hide_name'    => true,
						'hide_json_ld' => true,
					];

					$excerpt .= '<div class="wpseo-local-search-details">';
					$excerpt .= wpseo_local_show_address( $atts );
					$excerpt .= '</div>';
				}
			}

			return $excerpt;
		}

		/**
		 * Set the default fields to search in.
		 *
		 * @return void
		 */
		private function set_search_fields() {
			$this->search_fields = [ '_wpseo_business_address', '_wpseo_business_city', '_wpseo_business_zipcode' ];
			$this->search_fields = apply_filters( 'wpseo_local_search_custom_fields', $this->search_fields );
		}

		/**
		 * Check if enhanced search is enabled.
		 *
		 * @return bool
		 */
		private function is_enhanced_search_enabled() {
			$enhanced_search = apply_filters( 'yoast_local_seo_enhanced_search_enabled', WPSEO_Options::get( 'local_enhanced_search' ) );

			return ( $enhanced_search === 'on' );
		}

		/**
		 * Check if enhanced search result is enabled.
		 *
		 * @return bool
		 */
		private function is_enhanced_search_result_enabled() {
			return apply_filters( 'yoast_local_seo_enhanced_search_result_enabled', $this->enhanced_search_result_enabled );
		}
	}
}
