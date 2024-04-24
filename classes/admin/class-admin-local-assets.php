<?php
/**
 * Yoast SEO: Local plugin file.
 *
 * @package WPSEO_Local\Admin
 */

use Yoast\WP\Local\Repositories\Api_Keys_Repository;

/**
 * This class holds the assets for Yoast Local SEO.
 */
class WPSEO_Local_Admin_Assets extends WPSEO_Admin_Asset_Manager {

	/**
	 * Prefix for naming the assets.
	 *
	 * @var string
	 */
	public const PREFIX = 'wp-seo-local-';

	/**
	 * Child constructor for WPSEO_Local_Admin_Assets
	 */
	public function __construct() {
		parent::__construct( new WPSEO_Admin_Asset_SEO_Location( WPSEO_LOCAL_FILE ), self::PREFIX );
	}

	/**
	 * Returns the scripts that need to be registered.
	 *
	 * @return array Scripts that need to be registered.
	 */
	protected function scripts_to_be_registered() {
		$flat_version = $this->flatten_version( WPSEO_LOCAL_VERSION );

		$scripts = $this->load_select2_scripts();

		$scripts['commons-bundle']       = [
			'name' => 'commons-bundle',
			'src'  => self::PREFIX . 'vendor-' . $flat_version,
			'deps' => [ 'react', 'react-dom' ],
		];
		$scripts['frontend']             = [
			'name'    => 'frontend',
			'src'     => self::PREFIX . 'frontend-' . $flat_version,
			'deps'    => [ 'jquery', self::PREFIX . 'commons-bundle' ],
		];
		$scripts['google-maps']          = [
			'name'      => 'google-maps',
			'src'       => $this->get_google_maps_url(),
			'in_footer' => true,
			'deps'      => [ 'jquery', self::PREFIX . 'commons-bundle', self::PREFIX . 'frontend' ],
		];
		$scripts['seo-locations']        = [
			'name'      => 'seo-locations',
			'src'       => self::PREFIX . 'analysis-locations-' . $flat_version,
			'in_footer' => true,
		];
		$scripts['seo-pages']            = [
			'name'      => 'seo-pages',
			'src'       => self::PREFIX . 'analysis-pages-' . $flat_version,
			'in_footer' => true,
		];
		$scripts['global-script']        = [
			'name'      => 'global-script',
			'src'       => self::PREFIX . 'global-' . $flat_version,
			'deps'      => [ 'jquery', self::PREFIX . 'commons-bundle' ],
			'in_footer' => true,
		];
		$scripts['geocoding-repository'] = [
			'name'      => 'geocoding-repository',
			'src'       => self::PREFIX . 'geocoding-repository-' . $flat_version,
			'deps'      => [ 'jquery', self::PREFIX . 'commons-bundle' ],
			'in_footer' => true,
		];
		$scripts['locations']            = [
			'name'      => 'locations',
			'src'       => self::PREFIX . 'locations-' . $flat_version,
			'deps'      => [ 'wp-polyfill' ],
			'in_footer' => true,
		];
		$scripts['location-settings']    = [
			'name'      => 'location-settings',
			'src'       => self::PREFIX . 'location-settings-' . $flat_version,
			'version'   => WPSEO_LOCAL_VERSION,
			'deps'      => [
				'wp-polyfill',
				'wp-element',
				'wp-i18n',
				'yoast-seo-editor-modules',
			],
			'in_footer' => true,
		];
		$scripts['store-locator']        = [
			'name'      => 'store-locator',
			'src'       => self::PREFIX . 'store-locator-' . $flat_version,
			'deps'      => [ 'wp-polyfill' ],
			'in_footer' => true,
		];
		$scripts['checkout']             = [
			'name'    => 'checkout',
			'src'     => self::PREFIX . 'checkout-' . $flat_version,
			'deps'    => [ 'jquery' ],
		];
		$scripts['hipping-settings']     = [
			'name'    => 'shipping-settings',
			'src'     => self::PREFIX . 'shipping-settings-' . $flat_version,
			'deps'    => [ 'jquery' ],
		];
		$scripts['settings']             = [
			'name'    => 'settings',
			'src'     => self::PREFIX . 'settings-' . $flat_version,
			'deps'    => [ WPSEO_Admin_Asset_Manager::PREFIX . 'settings' ],
		];
		$scripts['blocks']               = [
			'name'    => 'blocks',
			'src'     => self::PREFIX . 'blocks-' . $flat_version,
			'deps'    => [ 'wp-blocks', 'wp-i18n', 'wp-element', 'react', 'react-dom' ],
		];

		return $scripts;
	}

	/**
	 * Returns the styles that need to be registered.
	 *
	 * @todo Data format is not self-documenting. Needs explanation inline. R.
	 *
	 * @return array Styles that need to be registered.
	 */
	protected function styles_to_be_registered() {
		$flat_version = $this->flatten_version( WPSEO_LOCAL_VERSION );

		return [
			[
				'name' => 'admin-css',
				'src'  => 'admin-' . $flat_version,
				'rtl'  => false,
			],
			[
				'name'    => 'select2',
				'src'     => 'select2/select2',
				'suffix'  => '.min',
				'version' => '4.0.13',
				'rtl'     => false,
			],
		];
	}

	/**
	 * Get the Google Maps external library URL.
	 *
	 * @return string
	 */
	private function get_google_maps_url() {
		$google_maps_url = 'https://maps.google.com/maps/api/js';
		$api_repository  = new Api_Keys_Repository();
		$api_repository->initialize();

		$api_key    = $api_repository->get_api_key( 'browser' );
		$query_args = [];
		if ( ! empty( $api_key ) ) {
			$query_args['key'] = $api_key;
		}
		$query_args['callback'] = 'wpseo_map_init';

		// Load Maps API script.
		$locale = get_locale();
		$locale = explode( '_', $locale );

		$multi_country_locales = [
			'en',
			'de',
			'es',
			'it',
			'pt',
			'ro',
			'ru',
			'sv',
			'nl',
			'zh',
			'fr',
		];

		// Check if it might be a language spoken in more than one country.
		if ( isset( $locale[1] ) && in_array( $locale[0], $multi_country_locales, true ) ) {
			$language = $locale[0] . '-' . $locale[1];
		}

		if ( ! isset( $language ) ) {
			$language = ( $locale[1] ?? $locale[0] );
		}

		if ( isset( $language ) ) {
			$query_args['language'] = esc_attr( strtolower( $language ) );
		}

		if ( ! empty( $query_args ) ) {
			$google_maps_url = add_query_arg( $query_args, $google_maps_url );
		}

		return $google_maps_url;
	}

	/**
	 * Loads the select2 scripts.
	 *
	 * @return array {
	 *     The scripts to be registered.
	 *
	 *     @type string   $name      The name of the asset.
	 *     @type string   $src       The src of the asset.
	 *     @type string[] $deps      The dependenies of the asset.
	 *     @type bool     $in_footer Whether or not the asset should be in the footer.
	 * }
	 */
	protected function load_select2_scripts() {
		$scripts          = [];
		$select2_language = 'en';
		$user_locale      = get_user_locale();
		$language         = WPSEO_Language_Utils::get_language( $user_locale );

		if ( file_exists( WPSEO_LOCAL_PATH . "js/dist/select2/i18n/{$user_locale}.js" ) ) {
			$select2_language = $user_locale; // Chinese and some others useful locale.
		}
		elseif ( file_exists( WPSEO_LOCAL_PATH . "js/dist/select2/i18n/{$language}.js" ) ) {
			$select2_language = $language;
		}

		$scripts['select2']              = [
			'name'    => 'select2',
			'src'     => false,
			'deps'    => [
				self::PREFIX . 'select2-translations',
				self::PREFIX . 'select2-core',
			],
		];
		$scripts['select2-core']         = [
			'name'    => 'select2-core',
			'src'     => 'select2/select2.full.min',
			'deps'    => [
				'jquery',
			],
			'version' => '4.0.13',
		];
		$scripts['select2-translations'] = [
			'name'    => 'select2-translations',
			'src'     => 'select2/i18n/' . $select2_language,
			'deps'    => [
				'jquery',
				self::PREFIX . 'select2-core',
			],
			'version' => '4.0.13',
		];

		return $scripts;
	}
}
