<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Frontend
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;

/**
 * Class WPSEO_Show_Locations_By_Category.
 *
 * Creates widget for showing the address.
 */
class WPSEO_Show_Locations_By_Category extends WP_Widget {

	/**
	 * WPSEO_Show_Locations_By_Category constructor.
	 */
	public function __construct() {
		$widget_options = [
			'classname'   => 'WPSEO_Show_Locations_By_Category',
			'description' => __( 'Shows a list of location names by category.', 'yoast-local-seo' ),
		];

		parent::__construct( false, __( 'WP SEO - Show Locations By Category', 'yoast-local-seo' ), $widget_options );
	}

	/**
	 * Displays the store locator form.
	 *
	 * @see WP_Widget::widget
	 *
	 * @param array $args     Array of options for this widget.
	 * @param array $instance Instance of the widget.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$title       = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$category_id = ! empty( $instance['category_id'] ) ? (int) $instance['category_id'] : '';

		if ( empty( $category_id ) ) {
			return;
		}

		$locations_repository_builder = new Locations_Repository_Builder();
		$repo                         = $locations_repository_builder->get_locations_repository();
		$repo->get( [ 'category_id' => $category_id ] );
		$locations = $repo->query;

		if ( $locations->post_count > 0 ) {

			if ( isset( $args['before_widget'] ) ) {
				echo $args['before_widget'];
			}

			if ( ! empty( $title ) ) {
				echo $args['before_title'], esc_html( $title ), $args['after_title'];
			}

			echo '<ul>';
			while ( $locations->have_posts() ) {
				$locations->the_post();
				echo '<li><a href="', esc_url( get_permalink() ), '">', esc_html( get_the_title() ), '</a></li>';
			}
			echo '</ul>';

			if ( isset( $args['after_widget'] ) ) {
				echo $args['after_widget'];
			}
		}
	}

	/**
	 * Updates a particular instance of a widget.
	 *
	 * @see WP_Widget::update
	 *
	 * @param array $new_instance New option values for this widget.
	 * @param array $old_instance Old, current option values for this widget.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = sanitize_text_field( $new_instance['title'] );
		$instance['category_id'] = (int) $new_instance['category_id'];

		return $instance;
	}

	/**
	 * Displays the form for the widget options.
	 *
	 * @see WP_Widget::form
	 *
	 * @param array $instance Array with all the (saved) option values.
	 *
	 * @return string
	 */
	public function form( $instance ) {
		$title       = ( ! empty( $instance['title'] ) ) ? $instance['title'] : '';
		$category_id = ( ! empty( $instance['category_id'] ) ) ? $instance['category_id'] : '';

		printf(
			'
		<p>
			<label for="%1$s">%2$s</label>
			<input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" />
		</p>',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_html__( 'Title:', 'yoast-local-seo' ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title )
		);

		printf(
			'
		<p>
			<label for="%1$s">%2$s
				<select class="widefat" id="%1$s" name="%3$s">',
			esc_attr( $this->get_field_id( 'category_id' ) ),
			esc_html__( 'Category:', 'yoast-local-seo' ),
			esc_attr( $this->get_field_name( 'category_id' ) )
		);

		echo '
					<option value=""> -- ', esc_html__( 'Select a category', 'yoast-local-seo' ), ' -- </option>';

		$categories = get_terms(
			[
				'taxonomy'   => 'wpseo_locations_category',
				'hide_empty' => false,
			]
		);

		foreach ( $categories as $category ) {
			printf(
				'
					<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $category->term_id ),
				selected( $category_id, $category->term_id, false ),
				esc_html( $category->name )
			);
		}

		echo '
				</select>
			</label>
		</p>';

		return '';
	}
}
