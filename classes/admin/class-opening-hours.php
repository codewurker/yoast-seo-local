<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Admin\
 * @since   11.0
 * @todo    CHECK THE @SINCE VERSION NUMBER!!!!!!!!
 */

use Yoast\WP\Local\Repositories\Options_Repository;
use Yoast\WP\Local\Repositories\Timezone_Repository;
use Yoast\WP\SEO\Presenters\Admin\Help_Link_Presenter;

if ( ! defined( 'WPSEO_LOCAL_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'WPSEO_Local_Admin_Opening_Hours' ) ) {
	return;
}

/**
 * WPSEO_Local_Admin_Opening_Hours class.
 *
 * Build the WPSEO Local admin form.
 *
 * @since   11.7
 */
class WPSEO_Local_Admin_Opening_Hours {

	/**
	 * Holds the slug for this settings tab.
	 *
	 * @var string
	 */
	private $slug = 'opening_hours';

	/**
	 * Holds WPSEO Local Core instance.
	 *
	 * @var mixed
	 */
	private $wpseo_local_core;

	/**
	 * Stores the options for this plugin.
	 *
	 * @var Options_Repository
	 */
	private $options;

	/**
	 * Holds the Timezone repository.
	 *
	 * @var Timezone_Repository
	 */
	private $wpseo_local_timezone_repository;

	/**
	 * WPSEO_Local_Admin_API_Opening_Hours constructor.
	 */
	public function __construct() {
		$this->get_core();
		$this->get_timezone_repository();
		$this->get_options();

		add_filter( 'wpseo_local_admin_tabs', [ $this, 'create_tab' ] );
		add_filter( 'wpseo_local_admin_help_center_video', [ $this, 'set_video' ] );

		add_action( 'wpseo_local_admin_' . $this->slug . '_content', [ $this, 'opening_hours' ], 10 );
	}

	/**
	 * Set WPSEO Local Core instance in local property
	 *
	 * @return void
	 */
	private function get_core() {
		global $wpseo_local_core;
		$this->wpseo_local_core = $wpseo_local_core;
	}

	/**
	 * Set WPSEO Local Core Timezone Repository in local property
	 *
	 * @return void
	 */
	private function get_timezone_repository() {
		$wpseo_local_timezone_repository = new Timezone_Repository();
		$wpseo_local_timezone_repository->initialize();
		$this->wpseo_local_timezone_repository = $wpseo_local_timezone_repository;
	}

	/**
	 * Get wpseo_local options.
	 *
	 * @return void
	 */
	private function get_options() {
		$this->options = new Options_Repository();
		$this->options->initialize();
	}

	/**
	 * @param array $tabs Array holding the tabs.
	 *
	 * @return mixed
	 */
	public function create_tab( $tabs ) {
		$tabs[ $this->slug ] = [
			'tab_title'     => __( 'Opening hours', 'yoast-local-seo' ),
			'content_title' => __( 'Opening hours', 'yoast-local-seo' ),
		];

		return $tabs;
	}

	/**
	 * @param array $videos Array holding the videos for the help center.
	 *
	 * @return mixed
	 */
	public function set_video( $videos ) {
		$videos[ $this->slug ] = 'https://yoa.st/screencast-local-settings-api-keys';

		return $videos;
	}

	/**
	 * Opening hours settings section.
	 *
	 * @return void
	 */
	public function opening_hours() {
		WPSEO_Local_Admin_Page::section_before( 'opening-hours-container', 'clear: both; ' );

		Yoast_Form::get_instance()->light_switch(
			'hide_opening_hours',
			__( 'Hide opening hours option', 'yoast-local-seo' ),
			[
				__( 'No', 'yoast-local-seo' ),
				__( 'Yes', 'yoast-local-seo' ),
			]
		);

		$hide_opening_hours = ! empty( $this->options->get( 'hide_opening_hours' ) ) && $this->options->get( 'hide_opening_hours' ) === 'on';

		WPSEO_Local_Admin_Page::section_before( 'opening-hours-settings', 'clear: both; display: ' . ( ( $hide_opening_hours === false ) ? 'block' : 'none' ) . ';' );
		echo '<p>' . esc_html__( 'Below you can enter a custom text to display in the opening hours for locations that are closed/open for 24 hours.', 'yoast-local-seo' ) . '</p>';

		WPSEO_Local_Admin_Wrappers::textinput( 'closed_label', __( 'Closed label', 'yoast-local-seo' ) );
		WPSEO_Local_Admin_Wrappers::textinput( 'open_24h_label', __( 'Open 24h label', 'yoast-local-seo' ) );
		WPSEO_Local_Admin_Wrappers::textinput( 'open_247_label', __( 'Open 24/7 label', 'yoast-local-seo' ) );

		$this->generate_24h_format_toggle();

		$is_multiple_location_single_organization = $this->options->is_one_organization() && $this->options->use_multiple_locations();

		WPSEO_Local_Admin_Page::section_before( 'share-opening-hours-settings', 'clear: both; display: ' . ( ( $is_multiple_location_single_organization ) ? 'block' : 'none' ) . ';' );

		echo '<p>' . esc_html__( 'This is the default setting for all locations and can be overridden per location.', 'yoast-local-seo' ) . '</p>';

		$shared_opening_hours_help_link = new Help_Link_Presenter(
			WPSEO_Shortlinker::get( 'https://yoa.st/local-new-3' ),
			__( 'Learn more about shared opening hours', 'yoast-local-seo' )
		);
		Yoast_Form::get_instance()->light_switch(
			'multiple_locations_shared_opening_hours',
			__( 'Locations inherit shared opening hours ', 'yoast-local-seo' ),
			[
				__( 'No', 'yoast-local-seo' ),
				__( 'Yes', 'yoast-local-seo' ),
			],
			true,
			$shared_opening_hours_help_link,
			false,
			[ 'disabled' => ! $this->options->use_multiple_locations() ]
		);
		WPSEO_Local_Admin_Page::section_after();

		WPSEO_Local_Admin_Page::section_after(); // End opening-hours-settings section.

		$display_time_settings = ( ( $is_multiple_location_single_organization || ! $this->options->use_multiple_locations() ) && $hide_opening_hours === false );

		WPSEO_Local_Admin_Page::section_before( 'opening-hours-time-settings', 'clear: both; display: ' . ( ( $display_time_settings ) ? 'block' : 'none' ) . ';' );

		$shared_opening_hours_disabled = ( $this->options->use_multiple_locations() && ! $this->options->use_shared_opening_hours() );

		WPSEO_Local_Admin_Page::section_before( 'opening-hours-time-settings-normal', '', ( $shared_opening_hours_disabled ) ? 'wpseo-local-form-elements-disabled' : '' );
		$this->generate_opening_hour_forms();
		WPSEO_Local_Admin_Page::section_after();

		WPSEO_Local_Admin_Page::section_after(); // End opening-hours-time-settings section.

		WPSEO_Local_Admin_Page::section_after(); // End opening-hours-container section.
	}

	/**
	 * Generates opening hours form.
	 *
	 * @return void
	 */
	private function generate_opening_hour_forms() {
		$timezone_help = new WPSEO_Local_Admin_Help_Panel(
			'timezone_help',
			/* translators: Hidden accessibility text. */
			__( 'Help with: Timezone', 'yoast-local-seo' ),
			esc_html__( 'The timezone is used to calculate the “Open now” functionality which can be shown together with your opening hours.', 'yoast-local-seo' ),
			'has-wrapper'
		);

		$field_name_prefix                 = '';
		$open_247_field_name               = 'open_247';
		$multiple_opening_hours_field_name = 'multiple_opening_hours';
		$location_timezone_field_name      = 'location_timezone';
		$opening_hours_24h_option_name     = 'opening_hours_24h';

		$shared_opening_hours_disabled = ( $this->options->use_multiple_locations() && ! $this->options->use_shared_opening_hours() );

		echo '<div class="open_247_wrapper">';

		Yoast_Form::get_instance()->light_switch(
			$open_247_field_name,
			__( 'Open 24/7', 'yoast-local-seo' ),
			[
				__( 'No', 'yoast-local-seo' ),
				__( 'Yes', 'yoast-local-seo' ),
			],
			true,
			'',
			false,
			[ 'disabled' => $shared_opening_hours_disabled ]
		);

		echo '</div>';

		$open_247 = ! empty( $this->options->get( $open_247_field_name ) ) && $this->options->get( $open_247_field_name ) === 'on';

		echo '<div id="opening-hours-time-specification-wrap" style="display:' . ( ( $open_247 ) ? 'none' : 'block' ) . '">';

		Yoast_Form::get_instance()->light_switch(
			$multiple_opening_hours_field_name,
			__( 'I have two sets of opening hours per day', 'yoast-local-seo' ),
			[
				__( 'No', 'yoast-local-seo' ),
				__( 'Yes', 'yoast-local-seo' ),
			],
			true,
			'',
			false,
			[ 'disabled' => $shared_opening_hours_disabled ]
		);
		$use_multiple_opening_hours = $this->options->get( $multiple_opening_hours_field_name );

		echo '<p class="opening-hours-second-description" style="display: ' . ( ( $use_multiple_opening_hours !== 'on' ) ? 'none' : 'block' ) . '">';
		printf(
		/* translators: 1: <strong> open tag; 2: </strong> close tag. */
			esc_html__( 'If a specific day only has one set of opening hours, please set the second set for that day to %1$sclosed%2$s', 'yoast-local-seo' ),
			'<strong>',
			'</strong>'
		);
		echo '</p>';
		foreach ( $this->wpseo_local_core->days as $key => $day ) {
			$field_name = 'opening_hours_' . $key;

			if ( strlen( $field_name_prefix ) > 0 ) {
				$field_name = $field_name_prefix . '_' . $field_name;
			}

			$value_from        = ( ! empty( $this->options->get( $field_name . '_from' ) ) ) ? esc_attr( $this->options->get( $field_name . '_from' ) ) : '09:00';
			$value_to          = ( ! empty( $this->options->get( $field_name . '_to' ) ) ) ? esc_attr( $this->options->get( $field_name . '_to' ) ) : '17:00';
			$value_second_from = ( ! empty( $this->options->get( $field_name . '_second_from' ) ) ) ? esc_attr( $this->options->get( $field_name . '_second_from' ) ) : '09:00';
			$value_second_to   = ( ! empty( $this->options->get( $field_name . '_second_to' ) ) ) ? esc_attr( $this->options->get( $field_name . '_second_to' ) ) : '17:00';

			$value_24h = ( ! empty( $this->options->get( $field_name . '_24h' ) ) ) ? esc_attr( $this->options->get( $field_name . '_24h' ) ) : false;

			// Determine whether we're using the 24h format.
			$use_24_hours = ( ! empty( $this->options->get( $opening_hours_24h_option_name ) ) && $this->options->get( $opening_hours_24h_option_name ) === 'on' );

			$disabled_html_attribute = ( $shared_opening_hours_disabled ) ? ' disabled' : '';

			WPSEO_Local_Admin_Page::section_before( 'opening-hours-' . $key, null, 'opening-hours' );
			echo '<label class="textinput">' . $day . '</label>';
			echo '<div class="openinghours-wrapper">';
			echo '<select' . $disabled_html_attribute . ' class="openinghours_from" style="width: 100px;" id="' . $field_name . '_from" name="wpseo_local[' . $field_name . '_from]" ' . ( ( $value_24h === 'on' ) ? ' disabled="disabled" ' : '' ) . '>';
			echo wpseo_show_hour_options( $use_24_hours, $value_from );
			echo '</select>';
			echo '<span> - </span>';
			echo '<select' . $disabled_html_attribute . '  class="openinghours_to" style="width: 100px;" id="' . $field_name . '_to" name="wpseo_local[' . $field_name . '_to]" ' . ( ( $value_24h === 'on' ) ? 'disabled="disabled"' : '' ) . '>';
			echo wpseo_show_hour_options( $use_24_hours, $value_to );
			echo '</select>';

			WPSEO_Local_Admin_Page::section_before( 'opening-hours-second-' . $key, null, 'opening-hours-second ' . ( ( empty( $this->options->get( $multiple_opening_hours_field_name ) ) || $this->options->get( $multiple_opening_hours_field_name ) !== 'on' ) ? 'hidden' : '' ) . '' );
			echo '<select' . $disabled_html_attribute . '  class="openinghours_from_second" style="width: 100px;" id="' . $field_name . '_second_from" name="wpseo_local[' . $field_name . '_second_from]" ' . ( ( $value_24h === 'on' ) ? 'disabled="disabled"' : '' ) . '>';
			echo wpseo_show_hour_options( $use_24_hours, $value_second_from );
			echo '</select>';
			echo '<span> - </span>';
			echo '<select' . $disabled_html_attribute . '  class="openinghours_to_second" style="width: 100px;" id="' . $field_name . '_second_to" name="wpseo_local[' . $field_name . '_second_to]" ' . ( ( $value_24h === 'on' ) ? 'disabled="disabled"' : '' ) . '>';
			echo wpseo_show_hour_options( $use_24_hours, $value_second_to );
			echo '</select>';
			WPSEO_Local_Admin_Page::section_after(); // End opening-hours-second-{key} section.
			echo '</div>';
			echo '<label class="wpseo_open_24h" for="' . $field_name . '_24h"><input' . $disabled_html_attribute . ' type="checkbox" name="wpseo_local[' . $field_name . '_24h]" id="' . $field_name . '_24h" ' . checked( $value_24h, 'on', false ) . ' value="on" /> ' . esc_html__( 'Open 24 hours', 'yoast-local-seo' ) . '</label>';

			WPSEO_Local_Admin_Page::section_after(); // End opening-hours-{$key} section.
		}

		echo '<div class="wpseo-local-help-wrapper">';
		$timezones = Timezone_Repository::get_timezones();
		WPSEO_Local_Admin_Wrappers::select(
			$location_timezone_field_name,
			__( 'Timezone', 'yoast-local-seo' ) . $timezone_help->get_button_html(),
			$timezones,
			'',
			[ 'disabled' => $shared_opening_hours_disabled ]
		);
		echo $timezone_help->get_panel_html();
		echo '</div>';

		echo '</div>'; // Opening hours wrap.
	}

	/**
	 * Generates a 24-hour format toggle.
	 *
	 * @return void
	 */
	protected function generate_24h_format_toggle() {
		Yoast_Form::get_instance()->light_switch(
			'opening_hours_24h',
			__( 'Use 24h format', 'yoast-local-seo' ),
			[
				__( 'No', 'yoast-local-seo' ),
				__( 'Yes', 'yoast-local-seo' ),
			]
		);
	}
}
