<?php

namespace Yoast\WP\Local\Presenters\Geo;

use WPSEO_Local_Frontend;

/**
 * Presents the 'geo.region' meta tag.
 */
class Region_Presenter extends Abstract_Geo_Tag_Presenter {

	/**
	 * The key to use in the tag format.
	 *
	 * @var string
	 */
	protected $key = 'geo.region';

	/**
	 * @inheritDoc
	 */
	public function get() {
		if ( $this->location === null || ! isset( $this->location->business_country ) ) {
			return '';
		}

		return WPSEO_Local_Frontend::get_country( $this->location->business_country );
	}
}
