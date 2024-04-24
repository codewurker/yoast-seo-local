<?php
/**
 * WPSEO plugin file.
 *
 * @package Yoast\WP\SEO\Premium
 */

use Yoast\WP\Local\Main;

/**
 * Retrieves the main instance.
 *
 * @phpcs:disable WordPress.NamingConventions -- Should probably be renamed, but leave for now.
 *
 * @return Main The main instance.
 *
 * @throws Exception If loading fails and YOAST_ENVIRONMENT is development.
 */
function YoastSEOLocal() {
	// phpcs:enable

	static $main;

	if ( did_action( 'wpseo_loaded' ) ) {
		if ( $main === null ) {
			// Ensure free is loaded as loading Local will fail without it.
			YoastSEO();
			$main = new Main();
			$main->load();
		}
	}
	else {
		add_action( 'wpseo_loaded', 'YoastSEOLocal' );
	}

	return $main;
}
