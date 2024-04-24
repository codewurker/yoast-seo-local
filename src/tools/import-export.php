<?php

namespace Yoast\WP\Local\Tools;

use Yoast\WP\SEO\Initializers\Initializer_Interface;
use Yoast\WP\SEO\Integrations\Integration_Interface;

/**
 * Class that holds the functionality for the WPSEO Local Import and Export functions
 *
 * @since 3.9
 */
abstract class Import_Export implements Initializer_Interface, Integration_Interface {

	/**
	 * WPSEO Upload Directory.
	 *
	 * @var string
	 */
	protected $wpseo_upload_dir;

	/**
	 * Error and succes messages.
	 *
	 * @var array
	 */
	protected $messages = [];

	/**
	 * Holds the WPSEO Local option name.
	 *
	 * @var string
	 */
	protected $option_name = 'wpseo_local';

	/**
	 * Holds the predefined column names.
	 *
	 * @var array
	 */
	protected $columns = [
		'name',
		'address',
		'address_2',
		'city',
		'zipcode',
		'state',
		'country',
		'phone',
		'phone2nd',
		'fax',
		'email',
		'description',
		'image',
		'category',
		'url',
		'vat_id',
		'tax_id',
		'coc_id',
		'notes_1',
		'notes_2',
		'notes_3',
		'business_type',
		'location_logo',
		'is_postal_address',
		'custom_marker',
		'multiple_opening_hours',
		'opening_hours_monday_from',
		'opening_hours_monday_to',
		'opening_hours_monday_second_from',
		'opening_hours_monday_second_to',
		'opening_hours_tuesday_from',
		'opening_hours_tuesday_to',
		'opening_hours_tuesday_second_from',
		'opening_hours_tuesday_second_to',
		'opening_hours_wednesday_from',
		'opening_hours_wednesday_to',
		'opening_hours_wednesday_second_from',
		'opening_hours_wednesday_second_to',
		'opening_hours_thursday_from',
		'opening_hours_thursday_to',
		'opening_hours_thursday_second_from',
		'opening_hours_thursday_second_to',
		'opening_hours_friday_from',
		'opening_hours_friday_to',
		'opening_hours_friday_second_from',
		'opening_hours_friday_second_to',
		'opening_hours_saturday_from',
		'opening_hours_saturday_to',
		'opening_hours_saturday_second_from',
		'opening_hours_saturday_second_to',
		'opening_hours_sunday_from',
		'opening_hours_sunday_to',
		'opening_hours_sunday_second_from',
		'opening_hours_sunday_second_to',
	];

	public function initialize() {
		$this->set_upload_dir();
	}

	public function register_hooks() {
		\add_action( 'admin_notices', [ $this, 'show_notices' ] );
	}

	/**
	 * Set the WPSEO Upload Dir
	 *
	 * @return void
	 */
	private function set_upload_dir() {
		$wp_upload_dir          = \wp_upload_dir();
		$this->wpseo_upload_dir = \trailingslashit( $wp_upload_dir['basedir'] . '/wpseo/import' );
	}

	/**
	 * Display admin notices.
	 *
	 * @return void
	 */
	public function show_notices() {
		foreach ( $this->messages as $message ) {
			$class = 'notice-';
			if ( $message['type'] === 'success' ) {
				$class .= 'success';
			}
			elseif ( $message['type'] === 'error' ) {
				$class .= 'error';
			}
			else {
				$class .= 'warning';
			}

			$classes = 'notice ' . $class . ' is-dismissible';

			echo '<div class="' . \esc_attr( $classes ) . '">';
			echo $message['content'];
			echo '</div>';
		}
	}
}
