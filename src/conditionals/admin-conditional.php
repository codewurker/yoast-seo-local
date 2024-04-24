<?php

namespace Yoast\WP\Local\Conditionals;

use Yoast\WP\SEO\Conditionals\Conditional;

class Admin_Conditional implements Conditional {

	/**
	 * Returns whether or not the user is inside the WordPress administration interface.
	 *
	 * @return bool True if inside WordPress administration interface, false otherwise.
	 */
	public function is_met() {
		return \is_admin();
	}
}
