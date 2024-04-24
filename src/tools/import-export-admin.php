<?php

namespace Yoast\WP\Local\Tools;

use Yoast\WP\Local\Conditionals\Admin_Conditional;
use Yoast\WP\Local\Conditionals\Multiple_Locations_Conditional;
use Yoast\WP\SEO\Integrations\Integration_Interface;

/**
 * Class that holds the functionality for the WPSEO Local Import and Export functions
 *
 * @since 3.9
 */
class Import_Export_Admin implements Integration_Interface {

	public static function get_conditionals() {
		return [
			Multiple_Locations_Conditional::class,
			Admin_Conditional::class,
		];
	}

	public function register_hooks() {
		\add_action( 'wpseo_import_tab_header', [ $this, 'create_import_tab_header' ] );
		\add_action( 'wpseo_import_tab_content', [ $this, 'create_import_tab_content_wrapper' ] );
	}

	/**
	 * Creates new import tab
	 *
	 * @since 1.3.5
	 *
	 * @return void
	 */
	public function create_import_tab_header() {
		echo '<a class="nav-tab" id="local-seo-tab" href="#top#local-seo">Local SEO</a>';
	}

	/**
	 * Creates content wrapper for Local SEO import tab
	 *
	 * @since 1.3.5
	 *
	 * @return void
	 */
	public function create_import_tab_content_wrapper() {
		echo '<div id="local-seo" class="wpseotab">';
		\do_action( 'wpseo_import_tab_content_inner' );
		echo '</div>';
	}
}
