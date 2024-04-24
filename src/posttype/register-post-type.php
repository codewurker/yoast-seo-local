<?php

namespace Yoast\WP\Local\PostType;

use WPSEO_Local_Option;
use WPSEO_Options;
use Yoast\WP\Local\Conditionals\Multiple_Locations_Conditional;
use Yoast\WP\SEO\Initializers\Initializer_Interface;

if ( \class_exists( PostType::class ) ) {
	return;
}

/**
 * PostType class. Handles all basic needs registering the necessary post types.
 */
class PostType implements Initializer_Interface {

	/**
	 * The post type on which the Yoast SEO: Local functionality will be added.
	 *
	 * @var string
	 */
	private $post_type = 'wpseo_locations';

	/**
	 * A list of conditionals that must be met to use the class.
	 *
	 * @return array<string>
	 */
	public static function get_conditionals() {
		return [
			Multiple_Locations_Conditional::class,
		];
	}

	/**
	 * Initialize PostType
	 *
	 * @return void
	 */
	public function initialize() {
		$this->post_type = (string) \apply_filters( 'wpseo_local_post_type', $this->post_type );
		// Make sure that the singular and plural labels are available when registering the post type.
		WPSEO_Options::register_option( WPSEO_Local_Option::get_instance() );
		$this->register_post_type();
	}

	/**
	 * Register a custom post type for locations.
	 *
	 * @return void
	 */
	public function register_post_type() {
		/**
		 * Only register our custom post type if the post type is not filtered.
		 *
		 * Also only register if we're using multiple locations.
		 * This is a temporary fix until we remove all usages of deprecated classes.
		 */
		if ( $this->is_post_type_filtered() || ! \wpseo_has_multiple_locations() ) {
			$this->maybe_flush_rewrite_rules();
			return;
		}

		$label_singular = WPSEO_Options::get( 'locations_label_singular' );
		$label_plural   = WPSEO_Options::get( 'locations_label_plural' );
		$slug           = WPSEO_Options::get( 'locations_slug' );

		$labels = [
			'name'               => $label_plural,
			'singular_name'      => $label_singular,
			/* translators: %s extends to the singular label for the location post type */
			'add_new'            => \sprintf( \esc_html__( 'New %s', 'yoast-local-seo' ), $label_singular ),
			/* translators: %s extends to the singular label for the location post type */
			'new_item'           => \sprintf( \esc_html__( 'New %s', 'yoast-local-seo' ), $label_singular ),
			/* translators: %s extends to the singular label for the location post type */
			'add_new_item'       => \sprintf( \esc_html__( 'Add New %s', 'yoast-local-seo' ), $label_singular ),
			/* translators: %s extends to the singular label for the location post type */
			'edit_item'          => \sprintf( \esc_html__( 'Edit %s', 'yoast-local-seo' ), $label_singular ),
			/* translators: %s extends to the singular label for the location post type */
			'view_item'          => \sprintf( \esc_html__( 'View %s', 'yoast-local-seo' ), $label_singular ),
			/* translators: %s extends to the plural label for the location post type */
			'search_items'       => \sprintf( \esc_html__( 'Search %s', 'yoast-local-seo' ), $label_plural ),
			/* translators: %s extends to the plural label for the location post type */
			'not_found'          => \sprintf( \esc_html__( 'No %s found', 'yoast-local-seo' ), $label_plural ),
			/* translators: %s extends to the plural label for the location post type */
			'not_found_in_trash' => \sprintf( \esc_html__( 'No %s found in trash', 'yoast-local-seo' ), $label_plural ),
		];

		$args_cpt = [
			'labels'          => $labels,
			'public'          => true,
			'show_ui'         => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'rewrite'         => [
				'slug'       => \esc_html( $slug ),
				'with_front' => \apply_filters( 'yoast_seo_local_cpt_with_front', true ),
			],
			'has_archive'     => \esc_html( $slug ),
			'menu_icon'       => 'dashicons-location',
			'query_var'       => true,
			'show_in_rest'    => true,
			'rest_base'       => $this->post_type,
			'supports'        => [ 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes', 'publicize', 'wpcom-markdown' ],
		];
		$args_cpt = \apply_filters( 'wpseo_local_cpt_args', $args_cpt );

		\register_post_type( $this->post_type, $args_cpt );
		$this->maybe_flush_rewrite_rules();
	}

	/**
	 * Whether the locations post type is being altered by a filter.
	 *
	 * @return bool
	 */
	public function is_post_type_filtered() {
		return \has_filter( 'wpseo_local_post_type' );
	}

	/**
	 * The location post type.
	 *
	 * @return string
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Flushes rewrite rules if the relevant transient had been set when a settings change was detected.
	 *
	 * @return void
	 */
	private function maybe_flush_rewrite_rules() {
		if ( \get_transient( 'wpseo_local_location_type_status_changed' ) ) {
			\flush_rewrite_rules();

			\delete_transient( 'wpseo_local_location_type_status_changed' );
		}
	}
}
