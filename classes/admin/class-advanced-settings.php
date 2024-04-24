<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Admin\
 * @since   11.0
 * @todo    CHECK THE @SINCE VERSION NUMBER!!!!!!!!
 */

use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\SEO\Helpers\Indexable_Helper;
use Yoast\WP\SEO\Integrations\Watchers\Indexable_Permalink_Watcher;

if ( ! defined( 'WPSEO_LOCAL_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! class_exists( 'WPSEO_Local_Admin_Advanced_Settings' ) ) {

	/**
	 * WPSEO_Local_Admin_API_Settings class.
	 *
	 * Build the WPSEO Local admin form.
	 *
	 * @since   11.7
	 */
	class WPSEO_Local_Admin_Advanced_Settings {

		/**
		 * Holds the slug for this settings tab.
		 *
		 * @var string
		 */
		private $slug = 'advanced';

		/**
		 * WPSEO_Local_Admin_API_Settings constructor.
		 */
		public function __construct() {
			$post_type = new PostType();
			$post_type->initialize();
			add_filter( 'wpseo_local_admin_tabs', [ $this, 'create_tab' ] );
			add_filter( 'wpseo_local_admin_help_center_video', [ $this, 'set_video' ] );

			add_action(
				'wpseo_local_admin_' . $this->slug . '_content',
				[ $this, 'maybe_show_multiple_location_notification' ],
				10
			);
			if ( $post_type->is_post_type_filtered() ) {
				add_action(
					'wpseo_local_admin_' . $this->slug . '_content',
					[ $this, 'post_type_filtered_notification' ],
					10
				);
			}
			if ( ! $post_type->is_post_type_filtered() ) {
				add_action( 'wpseo_local_admin_' . $this->slug . '_content', [ $this, 'permalinks' ], 10 );
				add_action( 'wpseo_local_admin_' . $this->slug . '_content', [ $this, 'admin_labels' ], 10 );
			}
			add_action( 'wpseo_local_admin_' . $this->slug . '_content', [ $this, 'enhanced_search' ], 10 );
			add_action( 'update_option_wpseo_local', [ $this, 'reset_indexable_permalinks' ], 10, 2 );
		}

		/**
		 * Resets the permalinks for the indexables.
		 *
		 * @param array $old_value The old option value.
		 * @param array $new_value The new option value.
		 *
		 * @return void
		 */
		public function reset_indexable_permalinks( $old_value, $new_value ) {
			if ( $old_value['locations_slug'] === $new_value['locations_slug'] && $old_value['locations_taxo_slug'] === $new_value['locations_taxo_slug'] ) {
				return;
			}

			/**
			 * Represents the Indexable_Permalink_Watcher.
			 *
			 * @var Indexable_Permalink_Watcher $watcher
			 */
			$watcher = YoastSEO()->classes->get( Indexable_Permalink_Watcher::class );
			$helper  = YoastSEO()->classes->get( Indexable_Helper::class );

			if ( $old_value['locations_slug'] !== $new_value['locations_slug'] ) {
				$watcher->reset_permalinks_post_type( 'wpseo_locations' );
			}

			if ( $old_value['locations_taxo_slug'] !== $new_value['locations_taxo_slug'] ) {
				$helper->reset_permalink_indexables( 'term', 'wpseo_locations_category' );
			}
		}

		/**
		 * @param array $tabs Array holding the tabs.
		 *
		 * @return mixed
		 */
		public function create_tab( $tabs ) {
			$tabs[ $this->slug ] = [
				'tab_title'     => __( 'Advanced settings', 'yoast-local-seo' ),
				'content_title' => __( 'Advanced settings', 'yoast-local-seo' ),
			];

			return $tabs;
		}

		/**
		 * @param array $videos Array holding the videos for the help center.
		 *
		 * @return mixed
		 */
		public function set_video( $videos ) {
			$videos[ $this->slug ] = 'https://yoa.st/screencast-local-settings-api-keys';

			return $videos;
		}

		/**
		 * If multiple locations are not enabled, show a notification there are more (advanced) settings available if they are activated.
		 *
		 * @return void
		 */
		public function maybe_show_multiple_location_notification() {
			WPSEO_Local_Admin_Page::section_before( 'wpseo-local-multiple-locations-notification', 'clear: both; ' . ( ! wpseo_has_multiple_locations() ? '' : 'display: none;' ) );
			echo '<p>';
			echo esc_html__( 'When you use multiple locations, which you can enable under the Business info tab, you can find advanced settings regarding multiple locations here.', 'yoast-local-seo' );
			echo '</p>';
			WPSEO_Local_Admin_Page::section_after(); // End wpseo-local-enhanced section.
		}

		/**
		 * If multiple locations are not enabled, show a notification there are more (advanced) settings available if they are activated.
		 *
		 * @return void
		 */
		public function post_type_filtered_notification() {
			$post_type_instance = new PostType();
			$post_type_instance->initialize();
			$post_type = $post_type_instance->get_post_type();

			$post_type_object = get_post_type_object( $post_type );

			WPSEO_Local_Admin_Page::section_before( 'wpseo-local-post-type-filtered-notification', 'clear: both; ' . ( wpseo_has_multiple_locations() ? '' : 'display: none;' ) );
			echo '<p>';
			printf(
				/* Translators: %s is a link to the post type edit page */
				esc_html__( 'Permalink and label settings are not available because your locations post type is being overwritten by a filter. The current post type used for locations is %s', 'yoast-local-seo' ),
				'<a href="' . admin_url( 'edit.php?post_type=' . $post_type ) . '">' . $post_type_object->labels->name . '</a>'
			);
			echo '</p>';
			WPSEO_Local_Admin_Page::section_after(); // End wpseo-local-enhanced section.
		}

		/**
		 * Advanced settings section.
		 *
		 * @return void
		 */
		public function enhanced_search() {
			WPSEO_Local_Admin_Page::section_before( 'wpseo-local-enhanced', 'clear: both; ' . ( wpseo_has_multiple_locations() ? '' : 'display: none;' ) );
			echo '<h3>' . esc_html__( 'Enhanced search', 'yoast-local-seo' ) . '</h3>';

			Yoast_Form::get_instance()->light_switch(
				'local_enhanced_search',
				__( 'Include business locations in site-wide search results.', 'yoast-local-seo' ),
				[
					__( 'No', 'yoast-local-seo' ),
					__( 'Yes', 'yoast-local-seo' ),
				]
			);

			echo '<p style="border:none;">' . esc_html__( 'Users searching for street name, zip code or city will now also get your business location(s) in their search results.', 'yoast-local-seo' ) . '</p>';
			WPSEO_Local_Admin_Page::section_after(); // End wpseo-local-enhanced section.
		}

		/**
		 * Show the permalink settings when multiple locations is active.
		 *
		 * @return void
		 */
		public function permalinks() {
			$base_url = get_site_url();

			if ( apply_filters( 'yoast_seo_local_cpt_with_front', true ) ) {
				// Check if a custom permalink structure is set. If so, append it to the base site URL.
				$permalink_structure = get_option( 'permalink_structure' );
				preg_match( '~.+?(?=/%)~', $permalink_structure, $matches );
				if ( ! empty( $matches[0] ) ) {
					$base_url .= $matches[0];
				}
			}

			WPSEO_Local_Admin_Page::section_before( 'wpseo-local-permalinks', 'clear: both; ' . ( wpseo_has_multiple_locations() ? '' : 'display: none;' ) );
			echo '<h3>' . esc_html__( 'Permalinks', 'yoast-local-seo' ) . '</h3>';
			echo '<p>';
			printf(
			/* translators: %1$s and %2$s show code blocks with a URL inside */
				esc_html__( 'Each location and location category will receive a custom URL. By default, these are %1$s and %2$s. If you like, you may enter custom structures for your locations and location categories.', 'yoast-local-seo' ),
				'<code>/locations/%postname%/</code>', // Post URL.
				'<code>/locations-category/%category-slug%/</code>' // Category URL.
			);
			echo '</p>';

			echo '<div class="wpseo_local_input">';
			echo '<code class="before">' . esc_url( $base_url ) . '/</code>';
			WPSEO_Local_Admin_Wrappers::textinput( 'locations_slug', apply_filters( 'yoast-local-seo-admin-label-locations-slug', __( 'Locations base', 'yoast-local-seo' ) ), '', [ 'class' => 'inline' ] );
			echo '<code class="after">/%postname%/</code>';
			echo '</div>';
			echo '<p>';

			esc_html_e( 'The slug for your location pages. Default slug is', 'yoast-local-seo' );

			echo ' <code>locations</code>.';  /* this slug must never be translated as it is a standard. */

			echo '</p>';

			echo '<div class="wpseo_local_input">';
			echo '<code class="before">' . esc_url( $base_url ) . '/</code>';
			WPSEO_Local_Admin_Wrappers::textinput( 'locations_taxo_slug', apply_filters( 'yoast-local-seo-admin-label-locations-category-slug', __( 'Locations category base', 'yoast-local-seo' ) ), '', [ 'class' => 'inline' ] );
			echo '<code class="after">/%category-slug%/</code>';
			echo '</div>';
			echo '<p>';
			esc_html_e( 'The category slug for your location pages. Default slug is', 'yoast-local-seo' );
			echo ' <code>locations-category</code>.';  /* this slug must never be translated as it is a standard. */
			echo '</p>';
			WPSEO_Local_Admin_Page::section_after(); // End permalinks.
		}

		/**
		 * Show fields to change the admin labels when multiple locations is active.
		 *
		 * @return void
		 */
		public function admin_labels() {
			WPSEO_Local_Admin_Page::section_before( 'wpseo-local-admin_labels', 'clear: both; ' . ( wpseo_has_multiple_locations() ? '' : 'display: none;' ) );
			echo '<h3>' . esc_html__( 'Admin Label', 'yoast-local-seo' ) . '</h3>';
			echo '<p>' . esc_html__( 'With multiple locations, you will have a new menu item in your admin sidebar. By default, this menu item is labeled using the plural term of locations with each single item being called a location. If you like, you may enter custom labels to better match your business.', 'yoast-local-seo' ) . '</p>';

			WPSEO_Local_Admin_Wrappers::textinput( 'locations_label_singular', apply_filters( 'yoast-local-seo-admin-label-locations-label', __( 'Single label', 'yoast-local-seo' ) ) );
			echo '<p class="label desc">';
			printf(
			/* translators: 1: HTML <code> open tag; 2: <code> close tag. */
				esc_html__( 'The singular label for your location pages. Default label is %1$sLocation%2$s.', 'yoast-local-seo' ),
				'<code>',
				'</code>'
			);
			echo '</p>';

			WPSEO_Local_Admin_Wrappers::textinput( 'locations_label_plural', apply_filters( 'yoast-local-seo-admin-label-locations-label-plural', __( 'Plural label', 'yoast-local-seo' ) ) );
			echo '<p class="label desc">';
			printf(
			/* translators: 1: HTML <code> open tag; 2: <code> close tag. */
				esc_html__( 'The plural label for your location pages. Default label is %1$sLocations%2$s.', 'yoast-local-seo' ),
				'<code>',
				'</code>'
			);
			echo '</p>';

			WPSEO_Local_Admin_Page::section_after(); // End admin labels.
		}
	}
}
