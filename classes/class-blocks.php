<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Main
 * @since   6.0
 */

use Yoast\WP\Local\PostType\PostType;

if ( ! class_exists( 'WPSEO_Local_Blocks' ) ) {

	/**
	 * WPSEO_Local_Core class. Handles defining of Yoast Local SEO Gutenberg blocks.
	 */
	class WPSEO_Local_Blocks {

		/**
		 * WPSEO_Local_Blocks constructor.
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * The init function for the WPSEO_Local_Blocks class.
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'enqueue_block_editor_assets', [ $this, 'register_block_editor_assets' ] );

			add_action( 'wp_ajax_wpseo_local_show_address_ajax_cb', 'wpseo_local_show_address_ajax_cb', 10 );
			add_action( 'wp_ajax_nopriv_wpseo_local_show_address_ajax_cb', 'wpseo_local_show_address_ajax_cb', 10 );

			add_action( 'wp_ajax_wpseo_local_show_map_ajax_cb', 'wpseo_local_show_map_ajax_cb', 10 );
			add_action( 'wp_ajax_nopriv_wpseo_local_show_map_ajax_cb', 'wpseo_local_show_map_ajax_cb', 10 );

			add_action( 'wp_ajax_wpseo_local_show_opening_hours_ajax_cb', 'wpseo_local_show_opening_hours_ajax_cb', 10 );
			add_action( 'wp_ajax_nopriv_wpseo_local_show_opening_hours_ajax_cb', 'wpseo_local_show_opening_hours_ajax_cb', 10 );

			$wordpress_version = YoastSEO()->helpers->wordpress->get_wordpress_version();

			// The 'block_categories' filter has been deprecated in WordPress 5.8 and replaced by 'block_categories_all'.
			if ( version_compare( $wordpress_version, '5.8-beta0', '<' ) ) {
				add_filter( 'block_categories', [ $this, 'block_category' ] );
			}
			else {
				add_filter( 'block_categories_all', [ $this, 'block_category' ] );
			}
		}

		/**
		 * Register Block Editor Assets.
		 *
		 * @return void
		 */
		public function register_block_editor_assets() {
			/**
			 * Filter: 'wpseo_enable_structured_data_blocks' - Allows disabling Yoast's schema blocks entirely.
			 *
			 * @param bool $enabled If false, our structured data blocks won't show.
			 */
			$enabled = apply_filters( 'wpseo_enable_structured_data_blocks', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- YoastSEO Free hook.
			if ( ! $enabled ) {
				return;
			}

			$wpseo_asset_manager = new WPSEO_Admin_Asset_Manager();
			$wpseo_asset_manager->register_assets();
			$wpseo_asset_manager->enqueue_script( 'api' );

			$yoast_seo_local_asset_manager = new WPSEO_Local_Admin_Assets();
			$yoast_seo_local_asset_manager->register_assets();
			$yoast_seo_local_asset_manager->enqueue_script( 'frontend' );
			$yoast_seo_local_asset_manager->enqueue_script( 'blocks' );

			$unit_system = WPSEO_Options::get( 'unit_system' );

			$post_type_instance = new PostType();
			$post_type_instance->initialize();
			$post_type = $post_type_instance->get_post_type();

			$localization_data = [
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'plugin_url'           => trailingslashit( plugins_url( '', WPSEO_LOCAL_FILE ) ),
				'hasMultipleLocations' => wpseo_has_multiple_locations(),
				'unitSystem'           => ( ( empty( $unit_system ) || $unit_system === 'METRIC' ) ? 'km' : 'mi' ),
				'locationsPostType'    => $post_type,
			];

			$localization_data = $this->maybe_add_preview_address( $localization_data );

			wp_localize_script( WPSEO_Local_Admin_Assets::PREFIX . 'blocks', 'yoastSeoLocal', $localization_data );
		}

		/**
		 * Add a preview address to the localization array when in the admin area.
		 *
		 * @param array $localization_data The given localization data from enqueueing blocks.
		 *
		 * @return array
		 */
		private function maybe_add_preview_address( $localization_data = [] ) {
			if ( is_admin() ) {
				$args = [
					'id'         => 'preview',
					'is_preview' => true,
				];

				if ( wpseo_has_multiple_locations() ) {
					$post_type_instance = new PostType();
					$post_type_instance->initialize();
					$post_type = $post_type_instance->get_post_type();

					if ( get_post_type( get_the_ID() ) === $post_type ) {
						$args['id'] = get_the_ID();
					}
				}

				$localization_data['previewAddress'] = wpseo_local_show_address( $args );
			}

			return $localization_data;
		}

		/**
		 * This method adds Yoast Local SEO blocks as a category to the block editor.
		 *
		 * @param array $categories An array containing the current registered block categories.
		 *
		 * @return array
		 */
		public function block_category( $categories ) {
			return array_merge(
				$categories,
				[
					[
						'slug'  => 'yoast-seo-local',
						'title' => esc_html__( 'Yoast Local SEO blocks', 'yoast-local-seo' ),
					],
				]
			);
		}
	}
}
