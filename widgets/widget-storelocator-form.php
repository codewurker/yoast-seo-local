<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Store_Locator
 */

/**
 * Class WPSEO_Storelocator_Form.
 *
 * Handles all store locator functionality.
 */
class WPSEO_Storelocator_Form extends WP_Widget {

	/**
	 * WPSEO_Storelocator_Form constructor.
	 */
	public function __construct() {
		$widget_options = [
			'classname'   => 'WPSEO_Storelocator_Form',
			'description' => __( 'Shows form to search the nearest store. Will submit to the page which contains the store locator.', 'yoast-local-seo' ),
		];

		parent::__construct( false, __( 'WP SEO - Store locator form', 'yoast-local-seo' ), $widget_options );
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
		$title        = apply_filters( 'widget_title', ( $instance['title'] ?? '' ) );
		$search_label = ( ! empty( $instance['search_label'] ) ) ? $instance['search_label'] : apply_filters( 'yoast-local-seo-search-label', __( 'Enter your postal code, city and / or state', 'yoast-local-seo' ) );
		$radius       = ( ! empty( $instance['radius'] ) ) ? $instance['radius'] : 10;
		$page_id      = ( ! empty( $instance['page_id'] ) ) ? (int) $instance['page_id'] : 0;

		if ( empty( $page_id ) ) {
			return;
		}

		if ( isset( $args['before_widget'] ) ) {
			echo $args['before_widget'];
		}

		if ( ! empty( $title ) ) {
			echo $args['before_title'], esc_html( $title ), $args['after_title'];
		}

		$asset_manager = new WPSEO_Local_Admin_Assets();

		$asset_manager->enqueue_script( 'store-locator' );
		$asset_manager->enqueue_script( 'google-maps' );

		$default_country = '';
		if ( isset( $this->options['default_country'] ) ) {
			$default_country = WPSEO_Local_Frontend::get_country( WPSEO_Options::get( 'default_country' ) );
		}

		wp_localize_script( WPSEO_Local_Admin_Assets::PREFIX . 'store-locator', 'storeLocator', [ 'defaultCountry' => $default_country ] );

		$search_string = '';
		if ( isset( $_REQUEST['wpseo-sl-search'] ) ) {
			$search_string = sanitize_text_field( wp_unslash( $_REQUEST['wpseo-sl-search'] ) );
		}

		printf(
			'
		<form action="%1$s" method="post" id="wpseo-storelocator-form-widget" data-form="wpseo-local-store-locator">
			<fieldset>
				<p>
					<label for="wpseo-sl-search">%2$s</label>
					<input type="text" name="wpseo-sl-search" id="wpseo-sl-widget-search" value="%3$s">
				</p>
				<p class="sl-submit">
					<input type="hidden" name="wpseo-sl-radius" id="wpseo-sl-radius-widget" value="%4$s">
					<input type="hidden" name="wpseo-sl-lat" id="wpseo-sl-lat-widget" value="">
					<input type="hidden" name="wpseo-sl-lng" id="wpseo-sl-lng-widget" value="">
					<input type="submit" value="%5$s">
				</p>
			</fieldset>
		</form>',
			esc_url( get_permalink( $page_id ) ),     // 1.
			esc_html( $search_label ),                // 2.
			esc_attr( $search_string ),               // 3.
			(int) $radius,                            // 4.
			esc_attr__( 'Search', 'yoast-local-seo' ) // 5.
		);

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
		$instance                 = $old_instance;
		$instance['title']        = sanitize_text_field( $new_instance['title'] );
		$instance['search_label'] = sanitize_text_field( $new_instance['search_label'] );
		$instance['radius']       = (int) $new_instance['radius'];
		$instance['page_id']      = (int) $new_instance['page_id'];

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
		$title        = ( ! empty( $instance['title'] ) ) ? $instance['title'] : '';
		$search_label = ( ! empty( $instance['search_label'] ) ) ? $instance['search_label'] : '';
		$radius       = ( ! empty( $instance['radius'] ) ) ? $instance['radius'] : 10;
		$page_id      = ( ! empty( $instance['page_id'] ) ) ? $instance['page_id'] : '';

		$options = get_option( 'wpseo_local' );

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
			<label for="%1$s">%2$s</label>
			<input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" />
		</p>',
			esc_attr( $this->get_field_id( 'search_label' ) ),
			esc_html__( 'Search label:', 'yoast-local-seo' ),
			esc_attr( $this->get_field_name( 'search_label' ) ),
			esc_attr( $search_label )
		);

		printf(
			'
		<p>
			<label for="%1$s">%2$s</label>
			<input class="widefat" id="%1$s" name="%3$s" type="number" min="1" value="%4$s" /><br>
			<small>
				%5$s
				%6$s
			</small>
		</p>',
			esc_attr( $this->get_field_id( 'radius' ) ),        // 1.
			esc_html__( 'Default radius:', 'yoast-local-seo' ), // 2.
			esc_attr( $this->get_field_name( 'radius' ) ),      // 3.
			(int) $radius,                                      // 4.
			sprintf(                                            // 5.
				/* translators: %s translates to the used unit system: km or mi in a <code> tag */
				esc_html__( 'Enter the radius in %s to search within.', 'yoast-local-seo' ),
				'<code>' . ( ( $options['unit_system'] === 'METRIC' ) ? 'km' : 'mi' ) . '</code>'
			),
			esc_html__( 'This field will be a hidden field and is only used for calculation.', 'yoast-local-seo' ) // 6.
		);

		echo '
		<p>
			<label for="', esc_attr( $this->get_field_id( 'page_id' ) ), '">',
			esc_html__( 'Select the page where your store locator shortcode is added. This is the page where the form is submitted to.', 'yoast-local-seo' ),
			'</label><br>';

		$args = [
			'name'             => $this->get_field_name( 'page_id' ),
			'id'               => $this->get_field_id( 'page_id' ),
			'class'            => 'widefat',
			'selected'         => (int) $page_id,
			'show_option_none' => esc_html__( 'Select a page', 'yoast-local-seo' ),
		];
		wp_dropdown_pages( $args );

		echo '
		</p>';

		return '';
	}
}
