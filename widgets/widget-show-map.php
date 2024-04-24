<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Frontend
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\Local\PostType\PostType;

/**
 * Class WPSEO_Show_Map.
 *
 * Creates widget for showing the map.
 */
class WPSEO_Show_Map extends WP_Widget {

	/**
	 * WPSEO_Show_Map constructor.
	 */
	public function __construct() {
		$widget_options = [
			'classname'   => 'WPSEO_Show_Map',
			'description' => __( 'Shows Google Map of your location', 'yoast-local-seo' ),
		];

		parent::__construct( false, __( 'WP SEO - Show Map', 'yoast-local-seo' ), $widget_options );
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
		$title                   = apply_filters( 'widget_title', ( $instance['title'] ?? '' ) );
		$show_all_locations      = ! empty( $instance['show_all_locations'] );
		$location_id             = ! empty( $instance['location_id'] ) ? $instance['location_id'] : '';
		$width                   = ! empty( $instance['width'] ) ? $instance['width'] : 400;
		$height                  = ! empty( $instance['height'] ) ? $instance['height'] : 300;
		$zoom                    = ! empty( $instance['zoom'] ) ? $instance['zoom'] : 10;
		$show_state              = ! empty( $instance['show_state'] );
		$show_country            = ! empty( $instance['show_country'] );
		$show_url                = ! empty( $instance['show_url'] );
		$show_route              = ! empty( $instance['show_route'] );
		$show_category_filter    = ! empty( $instance['show_category_filter'] );
		$default_show_infowindow = ! empty( $instance['default_show_infowindow'] );
		$marker_clustering       = ! empty( $instance['marker_clustering'] );

		// Set location ID, since get_post_status() needs an integer as parameter.
		if ( $location_id === 'current' ) {
			$location_id = get_queried_object_id();
		}

		if ( wpseo_has_multiple_locations()
			&& ( get_post_status( $location_id ) !== 'publish' && ! current_user_can( 'edit_posts' ) )
		) {
			return;
		}

		$post_type_instance = new PostType();
		$post_type_instance->initialize();

		if ( ( $location_id === '' && wpseo_has_multiple_locations() && $show_all_locations === false )
			|| ( $location_id === 'current' && ! is_singular( $post_type_instance->get_post_type() ) )
		) {
			return;
		}

		if ( isset( $args['before_widget'] ) ) {
			echo $args['before_widget'];
		}

		if ( ! empty( $title ) ) {
			echo $args['before_title'], esc_html( $title ), $args['after_title'];
		}

		$map_args = [
			'width'                   => $width,
			'height'                  => $height,
			'zoom'                    => $zoom,
			'id'                      => ( $show_all_locations ) ? 'all' : $location_id,
			'show_route'              => $show_route,
			'show_state'              => $show_state,
			'show_country'            => $show_country,
			'show_url'                => $show_url,
			'show_category_filter'    => $show_category_filter,
			'default_show_infowindow' => $default_show_infowindow,
			'marker_clustering'       => $marker_clustering,
			'echo'                    => true,
		];

		wpseo_local_show_map( $map_args );

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
		$instance                            = $old_instance;
		$instance['title']                   = sanitize_text_field( $new_instance['title'] );
		$instance['show_all_locations']      = isset( $new_instance['show_all_locations'] ) ? 1 : 0;
		$instance['location_id']             = sanitize_text_field( $new_instance['location_id'] );
		$instance['width']                   = ! empty( $new_instance['width'] ) ? (int) $new_instance['width'] : 400;
		$instance['height']                  = ! empty( $new_instance['height'] ) ? (int) $new_instance['height'] : 300;
		$instance['zoom']                    = ( isset( $new_instance['zoom'] ) && $new_instance['zoom'] >= 1 && $new_instance['zoom'] <= 20 ) ? $new_instance['zoom'] : 10;
		$instance['show_state']              = isset( $new_instance['show_state'] ) ? 1 : 0;
		$instance['show_country']            = isset( $new_instance['show_country'] ) ? 1 : 0;
		$instance['show_url']                = isset( $new_instance['show_url'] ) ? 1 : 0;
		$instance['show_route']              = isset( $new_instance['show_route'] ) ? 1 : 0;
		$instance['show_category_filter']    = isset( $new_instance['show_category_filter'] ) ? 1 : 0;
		$instance['default_show_infowindow'] = isset( $new_instance['default_show_infowindow'] ) ? 1 : 0;
		$instance['marker_clustering']       = isset( $new_instance['marker_clustering'] ) ? 1 : 0;

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
		$title                   = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$show_all_locations      = ! empty( $instance['show_all_locations'] );
		$location_id             = ! empty( $instance['location_id'] ) ? $instance['location_id'] : '';
		$width                   = ! empty( $instance['width'] ) ? $instance['width'] : 400;
		$height                  = ! empty( $instance['height'] ) ? $instance['height'] : 300;
		$zoom                    = ! empty( $instance['zoom'] ) ? $instance['zoom'] : 10;
		$show_state              = ! empty( $instance['show_state'] );
		$show_country            = ! empty( $instance['show_country'] );
		$show_url                = ! empty( $instance['show_url'] );
		$show_route              = ! empty( $instance['show_route'] );
		$show_category_filter    = ! empty( $instance['show_category_filter'] );
		$default_show_infowindow = ! empty( $instance['default_show_infowindow'] );
		$marker_clustering       = ! empty( $instance['marker_clustering'] );

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
			echo '<p>',
				esc_html__( 'Choose to show all your locations in the map, otherwise just pick one in the selectbox below', 'yoast-local-seo' ),
				'</p>';

			printf(
				'
		<p id="wpseo-checkbox-multiple-locations-wrapper">
			<label for="%1$s">
				<input id="%1$s" name="%2$s" type="checkbox" class="wpseo_widget_show_locations_checkbox" value="1" %3$s/>
				%4$s
			</label>
		</p>',
				esc_attr( $this->get_field_id( 'show_all_locations' ) ),
				esc_attr( $this->get_field_name( 'show_all_locations' ) ),
				checked( $show_all_locations, true, false ),
				esc_html__( 'Show all locations', 'yoast-local-seo' )
			);

			printf(
				'
		<p id="wpseo-locations-wrapper" %1$s>
			<label for="%2$s">%3$s</label>
			<select name="%4$s" id="%2$s" class="widefat">
				<option value="">%5$s</option>
				<option value="current" %6$s>%7$s</option>',
				( $show_all_locations ) ? 'style="display: none;"' : '', // 1.
				esc_attr( $this->get_field_id( 'location_id' ) ),        // 2.
				esc_html__( 'Location:', 'yoast-local-seo' ),            // 3.
				esc_attr( $this->get_field_name( 'location_id' ) ),      // 4.
				esc_html__( 'Select a location', 'yoast-local-seo' ),    // 5.
				selected( $location_id, 'current', false ),              // 6.
				esc_html__( 'Use current location', 'yoast-local-seo' )  // 7.
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
		<h4>', esc_html__( 'Maps settings', 'yoast-local-seo' ), '</h4>';

		printf(
			'
		<p>
			<label for="%1$s">%2$s</label>
			<input class="widefat" id="%1$s" name="%3$s" type="number" min="40" value="%4$s" />
		</p>',
			esc_attr( $this->get_field_id( 'width' ) ),
			esc_html__( 'Width:', 'yoast-local-seo' ),
			esc_attr( $this->get_field_name( 'width' ) ),
			(int) $width
		);

		printf(
			'
		<p>
			<label for="%1$s">%2$s</label>
			<input class="widefat" id="%1$s" name="%3$s" type="number" min="30" value="%4$s" />
		</p>',
			esc_attr( $this->get_field_id( 'height' ) ),
			esc_html__( 'Height:', 'yoast-local-seo' ),
			esc_attr( $this->get_field_name( 'height' ) ),
			(int) $height
		);

		printf(
			'
		<p>
			<label for="%1$s">%2$s</label>
			<select id="%1$s" name="%3$s">',
			esc_attr( $this->get_field_id( 'zoom' ) ),
			esc_html__( 'Zoom level:', 'yoast-local-seo' ),
			esc_attr( $this->get_field_name( 'zoom' ) )
		);

		for ( $i = 1; $i <= 20; $i++ ) {
			echo '
				<option value="', (int) $i, '"', selected( $zoom, $i, false ), '>', (int) $i, '</option>';
		}

		echo '
			</select>
		</p>';

		$checkboxes = [
			'show_state'              => __( 'Show state in info-window', 'yoast-local-seo' ),
			'show_country'            => __( 'Show country in info-window', 'yoast-local-seo' ),
			'show_url'                => __( 'Show URL in info-window', 'yoast-local-seo' ),
			'show_route'              => __( 'Show route planner', 'yoast-local-seo' ),
			'show_category_filter'    => __( 'Show category filter', 'yoast-local-seo' ),
			'default_show_infowindow' => __( 'Show infowindow by default', 'yoast-local-seo' ),
		];

		if ( wpseo_has_multiple_locations() ) {
			$checkboxes['marker_clustering'] = __( 'Marker clustering', 'yoast-local-seo' );
		}

		foreach ( $checkboxes as $field_name => $label ) {
			printf(
				'
		<p>
			<label for="%1$s">
				<input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s />
				%4$s
			</label>
		</p>',
				esc_attr( $this->get_field_id( $field_name ) ),
				esc_attr( $this->get_field_name( $field_name ) ),
				checked( ${$field_name}, true, false ),
				esc_html( $label )
			);
		}

		return '';
	}
}
