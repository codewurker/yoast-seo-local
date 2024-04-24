<?php

namespace Yoast\WP\Local\Builders;

use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\Local\Repositories\Locations_Repository;
use Yoast\WP\Local\Repositories\Options_Repository;

class Locations_Repository_Builder {

	/**
	 * @var Locations_Repository
	 */
	private $locations_repository;

	public function __construct() {
		$post_type = new PostType();
		$options   = new Options_Repository();

		$post_type->initialize();
		$options->initialize();

		$this->locations_repository = new Locations_Repository( $post_type, $options );
		$this->locations_repository->initialize();
	}

	public function get_locations_repository() {
		return $this->locations_repository;
	}
}
