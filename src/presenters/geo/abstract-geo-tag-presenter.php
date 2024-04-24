<?php

namespace Yoast\WP\Local\Presenters\Geo;

use Yoast\WP\Local\Repositories\Locations_Repository;
use Yoast\WP\SEO\Presenters\Abstract_Indexable_Tag_Presenter;

/**
 * Presents the 'geo.placename' meta tag.
 */
abstract class Abstract_Geo_Tag_Presenter extends Abstract_Indexable_Tag_Presenter {

	/**
	 * The tag format including placeholders.
	 *
	 * @var string
	 */
	protected $tag_format = self::META_NAME_CONTENT;

	/**
	 * The location.
	 *
	 * @var object The location.
	 */
	protected $location;

	/**
	 * Placename_Presenter constructor.
	 *
	 * @param object $location The location.
	 */
	public function __construct( $location ) {
		if ( \is_a( $location, Locations_Repository::class ) ) {
			\_deprecated_argument(
				__FUNCTION__,
				'14.0',
				\__( 'The Locations_Repository argument has been deprecated, please provide a location object instead.', 'yoast-local-seo' )
			);
			$this->location = $location->for_current_page();
		}
		else {
			$this->location = $location;
		}
	}
}
