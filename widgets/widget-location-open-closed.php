<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Frontend
 */

use Yoast\WP\Local\PostType\PostType;

/**
 * Class WPSEO_Show_Open_Closed.
 *
 * Creates widget for showing the address.
 */
class WPSEO_Show_Open_Closed extends WP_Widget {

	/**
	 * @var string The post type used for Yoast SEO: Local locations.
	 */
	private $local_post_type;

	/**
	 * WPSEO_Show_Open_Closed constructor.
	 */
	public function __construct() {
		$post_type = new PostType();
		$post_type->initialize();
		$this->local_post_type = $post_type->get_post_type();

		$widget_options = [
			'classname'   => 'WPSEO_Show_Open_Closed',
			'description' => __( 'Display a message when a location is open or closed.', 'yoast-local-seo' ),
		];

		parent::__construct( false, __( 'WP SEO - Show open/closed message', 'yoast-local-seo' ), $widget_options );
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
		$title          = apply_filters( 'widget_title', ( $instance['title'] ?? '' ) );
		$location_id    = ! empty( $instance['location_id'] ) ? $instance['location_id'] : '';
		$message_open   = ! empty( $instance['message_open'] ) ? $instance['message_open'] : '';
		$message_closed = ! empty( $instance['message_closed'] ) ? $instance['message_closed'] : '';

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
			|| ( $location_id === 'current' && ! is_singular( $this->local_post_type ) )
		) {
			return;
		}

		if ( $location_id === 'current' ) {
			$location_id = get_queried_object_id();
		}

		if ( isset( $args['before_widget'] ) ) {
			echo $args['before_widget'];
		}

		if ( ! empty( $title ) ) {
			echo $args['before_title'], esc_html( $title ), $args['after_title'];
		}

		$open_closed_message = '';
		if ( ! empty( $message_closed ) ) {
			$open_closed_message = $message_closed;
		}

		if ( ! empty( $message_open ) ) {
			$location_open = yoast_seo_local_is_location_open( $location_id );
			if ( ( ! is_wp_error( $location_open ) && ! empty( $location_open ) ) ) {
				$open_closed_message = $message_open;
			}
		}

		if ( $open_closed_message !== '' ) {
			echo wp_kses_post( wpautop( $open_closed_message ) );
		}

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
		$instance                   = $old_instance;
		$instance['title']          = sanitize_text_field( $new_instance['title'] );
		$instance['location_id']    = sanitize_text_field( $new_instance['location_id'] );
		$instance['message_open']   = sanitize_text_field( $new_instance['message_open'] );
		$instance['message_closed'] = sanitize_text_field( $new_instance['message_closed'] );

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
		$title           = ( ! empty( $instance['title'] ) ) ? $instance['title'] : '';
		$cur_location_id = ( ! empty( $instance['location_id'] ) ) ? $instance['location_id'] : '';
		$message_open    = ( ! empty( $instance['message_open'] ) ) ? $instance['message_open'] : '';
		$message_closed  = ( ! empty( $instance['message_closed'] ) ) ? $instance['message_closed'] : '';

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
			<select class="widefat" id="%1$s" name="%3$s">',
				esc_attr( $this->get_field_id( 'location_id' ) ),
				esc_html__( 'Location:', 'yoast-local-seo' ),
				esc_attr( $this->get_field_name( 'location_id' ) )
			);

			$args      = [
				'post_type'      => $this->local_post_type,
				'orderby'        => 'name',
				'order'          => 'ASC',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_status'    => ( current_user_can( 'edit_posts' ) ? [ 'publish', 'draft' ] : '' ),
			];
			$locations = get_posts( $args );

			if ( ! empty( $locations ) ) {
				echo '
				<option value="">', esc_html__( 'Select a location', 'yoast-local-seo' ), '</option>';

				echo '
				<option value="current"', selected( $cur_location_id, 'current', false ), '>',
					esc_html__( 'Use current location', 'yoast-local-seo' ), '</option>';

				foreach ( $locations as $location_id ) {
					echo '
				<option value="', esc_attr( $location_id ), '"', selected( $cur_location_id, $location_id, false ), '>',
						esc_html( get_the_title( $location_id ) ), '</option>';
				}
			}

			echo '
			</select>
		</p>';
		}

		printf(
			'
		<p>
			<label for="%1$s">%2$s</label>
			<textarea id="%1$s" class="widefat" name="%3$s">%4$s</textarea>
		</p>',
			esc_attr( $this->get_field_id( 'message_open' ) ),
			esc_html__( 'Message when location is open', 'yoast-local-seo' ),
			esc_attr( $this->get_field_name( 'message_open' ) ),
			esc_textarea( $message_open )
		);

		printf(
			'
		<p>
			<label for="%1$s">%2$s</label>
			<textarea id="%1$s" class="widefat" name="%3$s">%4$s</textarea>
		</p>',
			esc_attr( $this->get_field_id( 'message_closed' ) ),
			esc_html__( 'Message when location is closed', 'yoast-local-seo' ),
			esc_attr( $this->get_field_name( 'message_closed' ) ),
			esc_attr( $message_closed )
		);

		return '';
	}
}
