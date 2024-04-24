<?php

namespace Yoast\WP\Local\Presenters\Geo;

/**
 * Presents the 'geo.placename' meta tag.
 */
class Placename_Presenter extends Abstract_Geo_Tag_Presenter {

	/**
	 * The key to use in the tag format.
	 *
	 * @var string
	 */
	protected $key = 'geo.placename';

	/**
	 * @inheritDoc
	 */
	public function get() {
		if ( $this->location === null || ! isset( $this->location->business_city ) ) {
			return '';
		}

		return $this->location->business_city;
	}
}
