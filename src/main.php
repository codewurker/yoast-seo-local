<?php

namespace Yoast\WP\Local;

use Yoast\WP\Lib\Abstract_Main;
use Yoast\WP\SEO\Dependency_Injection\Container_Compiler;
use Yoast\WP\SEO\Local\Generated\Cached_Container;
use Yoast\WP\SEO\Surfaces\Classes_Surface;

if ( ! \defined( 'WPSEO_LOCAL_VERSION' ) ) {
	\header( 'Status: 403 Forbidden' );
	\header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Main plugin class for Local SEO.
 *
 * @property Classes_Surface $classes The classes surface.
 */
class Main extends Abstract_Main {

	/**
	 * @inheritDoc
	 */
	protected function get_name() {
		return 'yoast-seo-local';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_container() {
		if ( $this->is_development() && \class_exists( '\Yoast\WP\Config\Dependency_Injection\Container_Compiler' ) ) {
			// Exception here is unhandled as it will only occur in development.
			Container_Compiler::compile(
				$this->is_development(),
				__DIR__ . '/generated/container.php',
				__DIR__ . '/../config/dependency-injection/services.php',
				__DIR__ . '/../vendor/composer/autoload_classmap.php',
				'Yoast\WP\SEO\Local\Generated'
			);
		}

		if ( \file_exists( __DIR__ . '/generated/container.php' ) ) {
			require_once __DIR__ . '/generated/container.php';

			return new Cached_Container();
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_surfaces() {
		return [
			'classes' => Classes_Surface::class,
		];
	}
}
