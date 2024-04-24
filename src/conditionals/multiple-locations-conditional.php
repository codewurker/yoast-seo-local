<?php

namespace Yoast\WP\Local\Conditionals;

use Yoast\WP\SEO\Conditionals\Conditional;

class Multiple_Locations_Conditional implements Conditional {

	/**
	 * Returns whether or not multiple locations are enabled.
	 *
	 * @return bool True if multiple locations are enabled, false otherwise.
	 */
	public function is_met() {
		return \wpseo_has_multiple_locations();
	}
}
