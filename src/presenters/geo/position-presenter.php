<?php

namespace Yoast\WP\Local\Presenters\Geo;

/**
 * Presents the 'geo.position' meta tag.
 */
class Position_Presenter extends Abstract_Geo_Tag_Presenter {

	/**
	 * The key to use in the tag format.
	 *
	 * @var string
	 */
	protected $key = 'geo.position';

	/**
	 * @inheritDoc
	 */
	public function present() {
		$coords = $this->get();

		if ( $coords === null ) {
			return '';
		}

		$output = $coords['lat'] . ';' . $coords['long'];

		/**
		 * There may be some classes that are derived from this class that do not use the $key property
		 * in their $tag_format string. In that case the key property will simply not be used.
		 */
		return \sprintf(
			$this->tag_format,
			$this->escape_value( $output ),
			$this->key,
			\is_admin_bar_showing() ? ' class="yoast-seo-meta-tag"' : ''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get() {
		if ( $this->location === null || $this->location->coords['lat'] === '' || $this->location->coords['long'] === '' ) {
			return null;
		}

		return $this->location->coords;
	}
}
