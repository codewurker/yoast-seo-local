<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Frontend
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\Local\PostType\PostType;
use Yoast\WP\Local\Repositories\Options_Repository;

/**
 * Class WPSEO_Show_OpeningHours.
 *
 * Creates widget for showing the address.
 */
class WPSEO_Show_OpeningHours extends WP_Widget {

	/**
	 * Holds the Opening Hours Repository object
	 *
	 * @var WPSEO_Local_Opening_Hours_Repository
	 */
	private $opening_hours_repository;

	/**
	 * WPSEO_Show_OpeningHours constructor.
	 */
	public function __construct() {
		$widget_options = [
			'classname'   => 'WPSEO_Show_OpeningHours',
			'description' => __( 'Shows opening hours of locations.', 'yoast-local-seo' ),
		];

		parent::__construct( false, __( 'WP SEO - Show Opening hours', 'yoast-local-seo' ), $widget_options );

		$this->opening_hours_repository = new WPSEO_Local_Opening_Hours_Repository(
			new Options_Repository()
		);
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
		$title              = apply_filters( 'widget_title', ( $instance['title'] ?? '' ) );
		$location_id        = ! empty( $instance['location_id'] ) ? $instance['location_id'] : '';
		$show_days          = ! empty( $instance['show_days'] ) ? $instance['show_days'] : [];
		$hide_closed        = ! empty( $instance['hide_closed'] );
		$show_open_label    = ! empty( $instance['show_open_label'] );
		$comment            = ! empty( $instance['comment'] ) ? $instance['comment'] : '';
		$post_type_instance = new PostType();
		$post_type_instance->initialize();
		$post_type = $post_type_instance->get_post_type();

		// Set location ID, since get_post_status() needs an integer as parameter.
		if ( $location_id === 'current' ) {
			$location_id = get_queried_object_id();
		}

		if ( wpseo_has_multiple_locations()
			&& ( get_post_status( $location_id ) !== 'publish' && ! current_user_can( 'edit_posts' ) )
		) {
			return;
		}

		if ( ( $location_id === '' && wpseo_has_multiple_locations() )
			|| ( $location_id === 'current' && ! is_singular( $post_type ) )
		) {
			return;
		}

		if ( wpseo_has_multiple_locations() && get_post_type( $location_id ) !== $post_type ) {
			return;
		}

		if ( isset( $args['before_widget'] ) ) {
			echo $args['before_widget'];
		}

		if ( ! empty( $title ) ) {
			echo $args['before_title'], esc_html( $title ), $args['after_title'];
		}

		$shortcode_args = [
			'id'              => $location_id,
			'echo'            => true,
			'comment'         => $comment,
			'hide_closed'     => $hide_closed,
			'show_days'       => $show_days,
			'show_open_label' => $show_open_label,
		];

		wpseo_local_show_opening_hours( $shortcode_args );

		if ( isset( $args['after_widget'] ) ) {
			echo $args['after_widget'];
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
		$instance                    = $old_instance;
		$instance['title']           = sanitize_text_field( $new_instance['title'] );
		$instance['location_id']     = sanitize_text_field( $new_instance['location_id'] );
		$instance['show_days']       = ! empty( $new_instance['show_days'] ) ? $new_instance['show_days'] : [];
		$instance['hide_closed']     = isset( $new_instance['hide_closed'] ) ? 1 : 0;
		$instance['show_open_label'] = isset( $new_instance['show_open_label'] ) ? 1 : 0;
		$instance['comment']         = sanitize_text_field( $new_instance['comment'] );

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
		$title           = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$location_id     = ! empty( $instance['location_id'] ) ? $instance['location_id'] : '';
		$show_days       = ( isset( $instance['show_days'] ) && is_array( $instance['show_days'] ) ) ? $instance['show_days'] : array_keys( $this->opening_hours_repository->get_days() );
		$hide_closed     = ! empty( $instance['hide_closed'] );
		$show_open_label = ! empty( $instance['show_open_label'] );
		$comment         = ! empty( $instance['comment'] ) ? $instance['comment'] : '';

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

		if ( wpseo_has_multiple_locations() ) {
			printf(
				'
		<p>
			<label for="%1$s">%2$s</label>
			<select class="widefat" id="%1$s" name="%3$s">
				<option value="">%4$s</option>
				<option value="current" %5$s>%6$s</option>',
				esc_attr( $this->get_field_id( 'location_id' ) ),       // 1.
				esc_html__( 'Location:', 'yoast-local-seo' ),           // 2.
				esc_attr( $this->get_field_name( 'location_id' ) ),     // 3.
				esc_html__( 'Select a location', 'yoast-local-seo' ),   // 4.
				selected( $location_id, 'current', false ),             // 5.
				esc_html__( 'Use current location', 'yoast-local-seo' ) // 6.
			);

			$locations_repository_builder = new Locations_Repository_Builder();
			$repo                         = $locations_repository_builder->get_locations_repository();
			$locations                    = $repo->get( [], false );

			foreach ( $locations as $loc_id ) {
				echo '
				<option value="', esc_attr( $loc_id ), '" ', selected( $location_id, $loc_id, false ), '>',
					esc_html( get_the_title( $loc_id ) ), '</option>';
			}

			echo '
			</select>
		</p>';
		}

		echo '
		<p>
			', esc_html__( 'Show days', 'yoast-local-seo' ), ':<br>';

		foreach ( $this->opening_hours_repository->get_days() as $key => $day ) {
			$checked = ( ! empty( $show_days ) && is_array( $show_days ) && in_array( $key, $show_days, true ) );
			printf(
				'
			<label for="%1$s"><input type="checkbox" id="%1$s" value="%2$s" name="%3$s" %4$s/>%5$s</label><br>',
				esc_attr( $this->get_field_id( 'show_days' . $key ) ), // 1.
				esc_attr( $key ),                                      // 2.
				esc_attr( $this->get_field_name( 'show_days[]' ) ),    // 3.
				checked( $checked, true, false ),                      // 4.
				esc_html( $day )                                       // 5.
			);
		}

		echo '
		</p>';

		printf(
			'
		<p>
			<label for="%1$s">
				<input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s />
				%4$s
			</label>
		</p>',
			esc_attr( $this->get_field_id( 'hide_closed' ) ),
			esc_attr( $this->get_field_name( 'hide_closed' ) ),
			checked( $hide_closed, true, false ),
			esc_html__( 'Hide closed days', 'yoast-local-seo' )
		);

		printf(
			'
		<p>
			<label for="%1$s">
				<input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s />
				%4$s
			</label>
		</p>',
			esc_attr( $this->get_field_id( 'show_open_label' ) ),
			esc_attr( $this->get_field_name( 'show_open_label' ) ),
			checked( $show_open_label, true, false ),
			esc_html__( 'Show open now label after opening hour for current day', 'yoast-local-seo' )
		);

		printf(
			'
		<p>
			<label for="%1$s">%2$s</label>
			<textarea class="widefat" id="%1$s" name="%3$s">%4$s</textarea>
		</p>',
			esc_attr( $this->get_field_id( 'comment' ) ),
			esc_html__( 'Extra comment', 'yoast-local-seo' ),
			esc_attr( $this->get_field_name( 'comment' ) ),
			esc_textarea( $comment )
		);

		return '';
	}
}
