<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Frontend
 */

use Yoast\WP\Local\Builders\Locations_Repository_Builder;
use Yoast\WP\Local\PostType\PostType;

/**
 * Class WPSEO_Show_Address.
 *
 * Creates widget for showing the address.
 */
class WPSEO_Show_Address extends WP_Widget {

	/**
	 * WPSEO_Show_Address constructor.
	 */
	public function __construct() {
		$widget_options = [
			'classname'   => 'WPSEO_Show_Address',
			'description' => __( 'Shows address of locations.', 'yoast-local-seo' ),
		];

		parent::__construct( false, __( 'WP SEO - Show Address', 'yoast-local-seo' ), $widget_options );
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
		$hide_name          = ! empty( $instance['hide_name'] );
		$hide_address       = ! empty( $instance['hide_address'] );
		$show_country       = ! empty( $instance['show_country'] );
		$show_state         = ! empty( $instance['show_state'] );
		$show_phone         = ! empty( $instance['show_phone'] );
		$show_phone_2       = ! empty( $instance['show_phone_2'] );
		$show_fax           = ! empty( $instance['show_fax'] );
		$show_email         = ! empty( $instance['show_email'] );
		$show_url           = ! empty( $instance['show_url'] );
		$show_logo          = ! empty( $instance['show_logo'] );
		$show_vat           = ! empty( $instance['show_vat'] );
		$show_tax           = ! empty( $instance['show_tax'] );
		$show_coc           = ! empty( $instance['show_coc'] );
		$show_price_range   = ! empty( $instance['show_price_range'] );
		$show_opening_hours = ! empty( $instance['show_opening_hours'] );
		$hide_closed        = ! empty( $instance['hide_closed'] );
		$show_oneline       = ! empty( $instance['show_oneline'] );
		$comment            = ! empty( $instance['comment'] ) ? $instance['comment'] : '';

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

		if ( ( $location_id === '' && wpseo_has_multiple_locations() )
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

		$shortcode_args = [
			'id'                 => $location_id,
			'hide_name'          => $hide_name,
			'hide_address'       => $hide_address,
			'show_country'       => $show_country,
			'show_state'         => $show_state,
			'show_phone'         => $show_phone,
			'show_phone_2'       => $show_phone_2,
			'show_fax'           => $show_fax,
			'show_email'         => $show_email,
			'show_url'           => $show_url,
			'show_logo'          => $show_logo,
			'show_vat'           => $show_vat,
			'show_tax'           => $show_tax,
			'show_coc'           => $show_coc,
			'show_price_range'   => $show_price_range,
			'show_opening_hours' => $show_opening_hours,
			'hide_closed'        => $hide_closed,
			'oneline'            => $show_oneline,
			'comment'            => $comment,
			'from_widget'        => true,
			'widget_title'       => $title,
			'before_title'       => $args['before_title'],
			'after_title'        => $args['after_title'],
			'echo'               => true,
		];

		wpseo_local_show_address( $shortcode_args, $this->id );

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
		$instance                       = $old_instance;
		$instance['title']              = sanitize_text_field( $new_instance['title'] );
		$instance['location_id']        = sanitize_text_field( $new_instance['location_id'] );
		$instance['hide_name']          = isset( $new_instance['hide_name'] ) ? 1 : 0;
		$instance['hide_address']       = isset( $new_instance['hide_address'] ) ? 1 : 0;
		$instance['show_country']       = isset( $new_instance['show_country'] ) ? 1 : 0;
		$instance['show_state']         = isset( $new_instance['show_state'] ) ? 1 : 0;
		$instance['show_phone']         = isset( $new_instance['show_phone'] ) ? 1 : 0;
		$instance['show_phone_2']       = isset( $new_instance['show_phone_2'] ) ? 1 : 0;
		$instance['show_fax']           = isset( $new_instance['show_fax'] ) ? 1 : 0;
		$instance['show_email']         = isset( $new_instance['show_email'] ) ? 1 : 0;
		$instance['show_url']           = isset( $new_instance['show_url'] ) ? 1 : 0;
		$instance['show_logo']          = isset( $new_instance['show_logo'] ) ? 1 : 0;
		$instance['show_vat']           = isset( $new_instance['show_vat'] ) ? 1 : 0;
		$instance['show_tax']           = isset( $new_instance['show_tax'] ) ? 1 : 0;
		$instance['show_coc']           = isset( $new_instance['show_coc'] ) ? 1 : 0;
		$instance['show_price_range']   = isset( $new_instance['show_price_range'] ) ? 1 : 0;
		$instance['show_opening_hours'] = isset( $new_instance['show_opening_hours'] ) ? 1 : 0;
		$instance['hide_closed']        = isset( $new_instance['hide_closed'] ) ? 1 : 0;
		$instance['show_oneline']       = isset( $new_instance['show_oneline'] ) ? 1 : 0;
		$instance['comment']            = sanitize_text_field( $new_instance['comment'] );

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
		$title              = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$cur_location_id    = ! empty( $instance['location_id'] ) ? $instance['location_id'] : '';
		$hide_name          = ! empty( $instance['hide_name'] );
		$hide_address       = ! empty( $instance['hide_address'] );
		$show_country       = ! empty( $instance['show_country'] );
		$show_state         = ! empty( $instance['show_state'] );
		$show_phone         = ! empty( $instance['show_phone'] );
		$show_phone_2       = ! empty( $instance['show_phone_2'] );
		$show_fax           = ! empty( $instance['show_fax'] );
		$show_email         = ! empty( $instance['show_email'] );
		$show_url           = ! empty( $instance['show_url'] );
		$show_logo          = ! empty( $instance['show_logo'] );
		$show_vat           = ! empty( $instance['show_vat'] );
		$show_tax           = ! empty( $instance['show_tax'] );
		$show_coc           = ! empty( $instance['show_coc'] );
		$show_price_range   = ! empty( $instance['show_price_range'] );
		$show_opening_hours = ! empty( $instance['show_opening_hours'] );
		$hide_closed        = ! empty( $instance['hide_closed'] );
		$show_oneline       = ! empty( $instance['show_oneline'] );
		$comment            = ! empty( $instance['comment'] ) ? $instance['comment'] : '';

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
				<select name="%3$s" id="%1$s" class="widefat">
',
				esc_attr( $this->get_field_id( 'location_id' ) ),
				esc_html__( 'Location:', 'yoast-local-seo' ),
				esc_attr( $this->get_field_name( 'location_id' ) )
			);

			$locations_repository_builder = new Locations_Repository_Builder();
			$repo                         = $locations_repository_builder->get_locations_repository();
			$locations                    = $repo->get( [], false );

			if ( ! empty( $locations ) ) {
				echo '
					<option value="">', esc_html__( 'Select a location', 'yoast-local-seo' ), '</option>
					<option value="current" ', selected( $cur_location_id, 'current', false ), '>',
					esc_html__( 'Use current location', 'yoast-local-seo' ), '</option>';

				foreach ( $locations as $location_id ) {
					echo '
					<option value="', esc_attr( $location_id ), '" ',
						selected( $cur_location_id, $location_id, false ), '>',
						esc_html( get_the_title( $location_id ) ), '</option>';
				}
			}

			echo '
				</select>
			</p>';
		}

		$checkboxes = [
			'hide_name'          => __( 'Hide business name', 'yoast-local-seo' ),
			'hide_address'       => __( 'Hide business address', 'yoast-local-seo' ),
			'show_country'       => __( 'Show country', 'yoast-local-seo' ),
			'show_state'         => __( 'Show state', 'yoast-local-seo' ),
			'show_phone'         => __( 'Show phone number', 'yoast-local-seo' ),
			'show_phone_2'       => __( 'Show second phone number', 'yoast-local-seo' ),
			'show_fax'           => __( 'Show fax number', 'yoast-local-seo' ),
			'show_email'         => __( 'Show email address', 'yoast-local-seo' ),
			'show_url'           => __( 'Show URL', 'yoast-local-seo' ),
			'show_logo'          => __( 'Show logo', 'yoast-local-seo' ),
			'show_vat'           => __( 'Show VAT ID', 'yoast-local-seo' ),
			'show_tax'           => __( 'Show Tax ID', 'yoast-local-seo' ),
			'show_coc'           => __( 'Show Chamber of Commerce ID', 'yoast-local-seo' ),
			'show_price_range'   => __( 'Show price indication', 'yoast-local-seo' ),
			'show_opening_hours' => __( 'Show opening hours', 'yoast-local-seo' ),
			'hide_closed'        => __( 'Hide closed days', 'yoast-local-seo' ),
			'show_oneline'       => __( 'Show address one line', 'yoast-local-seo' ),
		];

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
