<?php

namespace Yoast\WP\Local\Integrations;

use Yoast\WP\SEO\Conditionals\No_Conditionals;
use Yoast\WP\SEO\Integrations\Integration_Interface;

/**
 * Watches for changes in the multiple locations setting to trigger a flushing of the rewrite rules.
 */
class Multiple_Locations_Watcher implements Integration_Interface {

	use No_Conditionals;

	/**
	 * Initializes the integration.
	 *
	 * This is the place to register hooks and filters.
	 *
	 * @return void
	 */
	public function register_hooks() {
		\add_action( 'update_option_wpseo_local', [ $this, 'maybe_flush_rewrite_rules' ], 10, 2 );
	}

	/**
	 * Flushes the rewrite rules if the setting for multiple location has changed.
	 *
	 * We need to use a transient to allow the PostType initializer to flush the rules after the post type has been registered:
	 * if we did that now, the post type might not be there and the new rules would not include it.
	 * We also set it when the multiple location setting changes to 'off' so the new rules won't contain the location type anymore.
	 *
	 * @param array<string, bool|int|string> $old_option_value Old value of the option.
	 * @param array<string, bool|int|string> $new_option_value New value for the option.
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules( $old_option_value, $new_option_value ) {
		$old_value_exists = \array_key_exists( 'use_multiple_locations', $old_option_value );
		$new_value_exists = \array_key_exists( 'use_multiple_locations', $new_option_value );

		$old_value = ( $old_value_exists ) ? $old_option_value['use_multiple_locations'] : 'off';
		$new_value = ( $new_value_exists ) ? $new_option_value['use_multiple_locations'] : 'off';

		if ( $old_value !== $new_value ) {
			\set_transient( 'wpseo_local_location_type_status_changed', true, 60 );
		}
	}
}
