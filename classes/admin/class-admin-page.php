<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Admin\
 * @since   4.1
 * @todo    CHECK THE @SINCE VERSION NUMBER!!!!!!!!
 */

if ( ! defined( 'WPSEO_LOCAL_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! class_exists( 'WPSEO_Local_Admin_Page' ) ) {

	/**
	 * WPSEO_Local_Admin_Page class.
	 *
	 * Build the WPSEO Local admin form.
	 *
	 * @since 4.0
	 */
	class WPSEO_Local_Admin_Page {

		/**
		 * Array containing the tabs for the WPSEO Local Admin Page.
		 *
		 * @var array
		 */
		public static $tabs;

		/**
		 * Array containing help center videos.
		 *
		 * @var array
		 */
		public static $videos;

		/**
		 * WPSEO_Local_Admin_Page constructor.
		 */
		public function __construct() {
			add_action( 'admin_init', [ $this, 'set_tabs' ] );
		}

		/**
		 * Apply filters on array holding the tabs.
		 *
		 * @return void
		 */
		public function set_tabs() {
			self::$tabs = apply_filters( 'wpseo_local_admin_tabs', self::$tabs );
		}

		/**
		 * Build the WPSEO Local Admin page.
		 *
		 * @return void
		 */
		public static function build_page() {
			// Admin header.
			WPSEO_Local_Admin_Wrappers::admin_header( true, 'yoast_wpseo_local_options', 'wpseo_local' );

			// Adding tabs.
			self::create_tabs();
			self::tab_content();

			// Admin footer.
			WPSEO_Local_Admin_Wrappers::admin_footer( true, false );
		}

		/**
		 * Function to create tabs for general and API settings.
		 *
		 * @return void
		 */
		private static function create_tabs() {
			echo '<h2 class="nav-tab-wrapper" id="wpseo-tabs">';
			foreach ( self::$tabs as $slug => $titles ) {
				/**
				 * Stop building the tab if $titles is not an array.
				 *
				 * @var array $titles This should contain an array with at least the tab_title as key.
				 */
				if ( ! is_array( $titles ) ) {
					return;
				}
				echo '<a class="nav-tab" id="' . $slug . '-tab" href="#top#' . $slug . '">' . $titles['tab_title'] . '</a>';
			}
			echo '</h2>';
		}

		/**
		 * Add content to the admin tabs.
		 *
		 * @return void
		 */
		private static function tab_content() {
			foreach ( self::$tabs as $slug => $titles ) {
				self::section_before( $slug, null, 'wpseotab ' . ( $slug === current( array_keys( self::$tabs ) ) ? 'active' : '' ) );

				do_action( 'Yoast\WP\Local\before_option_content_' . $slug );

				self::section_before( 'local-' . $slug, null, 'yoastbox paper tab-block local-seo-settings' );
				self::section_before( 'local-' . $slug . '-container', null, 'paper-container' );
				do_action( 'wpseo_local_admin_' . $slug . '_before_title', $slug );
				echo '<h2>' . esc_html( $titles['content_title'] ) . '</h2>';
				do_action( 'wpseo_local_admin_' . $slug . '_content', $slug );
				self::section_after();
				self::section_after();

				// End yoastbox.
				self::section_after();
			}
		}

		/**
		 * Use this function to create sections between settings.
		 *
		 * @param string $id            ID of the section.
		 * @param string $style         Styling for the section.
		 * @param string $section_class Class names for the section.
		 *
		 * @return void
		 */
		public static function section_before( $id = '', $style = '', $section_class = '' ) {
			echo '<div' . ( isset( $id ) ? ' id="' . $id . '"' : '' ) . '' . ( ! empty( $style ) ? ' style="' . $style . '"' : '' ) . '' . ( ! empty( $section_class ) ? ' class="' . $section_class . '"' : '' ) . '>';
		}

		/**
		 * Use this function to close a section.
		 *
		 * @return void
		 */
		public static function section_after() {
			echo '</div>';
		}
	}
}
