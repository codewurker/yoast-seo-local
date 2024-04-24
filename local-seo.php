<?php
/**
 * Yoast SEO: Local
 *
 * @package WPSEO_Local\Main
 *
 * @wordpress-plugin
 * Plugin Name: Yoast SEO: Local
 * Version: 15.3
 * Plugin URI: http://yoa.st/4fj
 * Description: This Local SEO module adds all the needed functionality to get your site ready for Local Search Optimization
 * Author: Team Yoast and Arjan Snaterse
 * Author URI: https://yoa.st/team-yoast-local
 * Text Domain: yoast-local-seo
 * Domain Path: /languages/
 * Requires at least: 6.3
 * Requires PHP: 7.2.5
 *
 * WC Tested up to: 8.5
 *
 * Copyright 2012-2024 Joost de Valk & Arjan Snaterse
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Yoast\WP\SEO\Conditionals\Third_Party\Elementor_Edit_Conditional;

/*
 * All functionality for fetching location data and creating an KML file with it.
 */

if ( ! defined( 'WPSEO_LOCAL_VERSION' ) ) {
	define( 'WPSEO_LOCAL_VERSION', '15.3' );
}

if ( ! defined( 'WPSEO_LOCAL_PATH' ) ) {
	define( 'WPSEO_LOCAL_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WPSEO_LOCAL_URI' ) ) {
	define( 'WPSEO_LOCAL_URI', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WPSEO_LOCAL_FILE' ) ) {
	define( 'WPSEO_LOCAL_FILE', __FILE__ );
}

if ( ! function_exists( 'yoast_wpseo_local_deactivate_sibling_plugins' ) ) {
	/**
	 * Deactivate our sibling plugin: "Yoast SEO: Local for WooCommerce", because they may not be active simultaneously
	 *
	 * @since 3.3.1
	 *
	 * @return void
	 */
	function yoast_wpseo_local_deactivate_sibling_plugins() {
		deactivate_plugins( WPSEO_LOCAL_FILE );
	}
}

register_activation_hook( WPSEO_LOCAL_FILE, 'yoast_wpseo_local_deactivate_sibling_plugins' );

/**
 * Bootstrap all further loading of plugin activity here.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			add_action( 'all_admin_notices', 'wpseo_local_missing_error' );

			return;
		}

		if ( file_exists( WPSEO_LOCAL_PATH . 'vendor/autoload.php' ) ) {
			require_once WPSEO_LOCAL_PATH . 'vendor/autoload.php';
		}

		// If you change this version, changing the version in composer.json is not necessary
		// since it will be updated automatically.
		if ( version_compare( WPSEO_VERSION, '22.0-RC0', '<' ) ) {
			add_action( 'all_admin_notices', 'yoast_wpseo_local_upgrade_error' );

			return;
		}

		add_action( 'init', 'wpseo_local_seo_init' );

		add_action( 'widgets_init', 'wpseo_local_seo_init_widgets' );

		// Actions moved from includes/ajax-functions.php and includes/wpseo-local-functions.php
		// so those files can be autoloaded (as they will contain just functions then).
		add_action( 'wp_ajax_wpseo_copy_location', 'wpseo_copy_location_callback' );
		add_action( 'wp_ajax_nopriv_wpseo_copy_location', 'wpseo_copy_location_callback' );

		require_once WPSEO_LOCAL_PATH . 'src/main.php';
	},
	10
);

if ( ! function_exists( 'wpseo_local_seo_init' ) ) {
	/**
	 * Initialize the Local SEO module on plugins loaded, so WP SEO should have set its constants and loaded its main classes.
	 *
	 * @since 0.2
	 *
	 * @return void
	 */
	function wpseo_local_seo_init() {
		global $wpseo_local_core;

		if ( class_exists( '\Yoast\WP\SEO\Conditionals\Third_Party\Elementor_Edit_Conditional' ) ) {
			$elementor_conditional = new Elementor_Edit_Conditional();
			if ( $elementor_conditional->is_met() ) {
				return;
			}
		}

		// Load text domain.
		load_plugin_textdomain( 'yoast-local-seo', false, dirname( plugin_basename( WPSEO_LOCAL_FILE ) ) . '/languages' );

		$wpseo_local_core = new WPSEO_Local_Core();

		new WPSEO_Local_Admin();
		new WPSEO_Local_Metaboxes();
		new WPSEO_Local_Frontend();
		new WPSEO_Local_Schema();
		new WPSEO_Local_Storelocator();
		new WPSEO_Local_Taxonomy();
		new WPSEO_Local_Admin_Page();
		new WPSEO_Local_Admin_General_Settings();
		new WPSEO_Local_Admin_Opening_Hours();
		new WPSEO_Local_Admin_Map_Settings();
		new WPSEO_Local_Admin_API_Keys();
		new WPSEO_Local_Admin_Advanced_Settings();
		new WPSEO_Local_Search();
		new WPSEO_Local_Admin_Assets();
		new WPSEO_Local_Blocks();

		// Load the container.
		if ( ! wp_installing() ) {
			require_once __DIR__ . '/src/functions.php';
			YoastSEOLocal();
		}
	}
}

if ( ! function_exists( 'yoast_seo_local_init_woocommerce ' ) ) {
	/**
	 * Boots the Local WooCommerce functionality. But first check whether WooCommerce and Yoast: SEO plugins are activated.
	 *
	 * @return void
	 */
	function yoast_seo_local_init_woocommerce() {
		if ( class_exists( 'WooCommerce' ) && class_exists( 'WPSEO_Utils' ) ) {
			new WPSEO_Local_WooCommerce();
		}
	}

	add_action( 'plugins_loaded', 'yoast_seo_local_init_woocommerce', 11 );
}

if ( ! function_exists( 'wpseo_local_init_rest_api' ) ) {
	/**
	 * Loads the rest api endpoints.
	 *
	 * @return void
	 */
	function wpseo_local_init_rest_api() {
		// We can't do anything when requirements are not met.
		if ( class_exists( 'WPSEO_Utils' ) === false || ! WPSEO_Utils::is_api_available() ) {
			return;
		}

		$wpseo_locations_endpoint = new WPSEO_Local_Endpoint_Locations();
		$wpseo_locations_endpoint->register();
	}

	add_action( 'rest_api_init', 'wpseo_local_init_rest_api' );
}

if ( ! function_exists( 'wpseo_local_seo_init_widgets' ) ) {
	/**
	 * Register all widgets used for Local SEO plugin
	 *
	 * @since 3.1
	 *
	 * @return void
	 */
	function wpseo_local_seo_init_widgets() {
		$widgets = [
			'WPSEO_Show_Address',
			'WPSEO_Show_Map',
			'WPSEO_Show_OpeningHours',
			'WPSEO_Show_Open_Closed',
		];

		if ( wpseo_has_multiple_locations() ) {
			$widgets[] = 'WPSEO_Storelocator_Form';
			$widgets[] = 'WPSEO_Show_Locations_By_Category';
		}

		foreach ( $widgets as $widget ) {
			register_widget( $widget );
		}
	}
}


if ( ! function_exists( 'wpseo_local_missing_error' ) ) {
	/**
	 * Throw an error if Yoast SEO is not installed.
	 *
	 * @since 0.2
	 *
	 * @return void
	 */
	function wpseo_local_missing_error() {

		echo '<div class="error"><p>';
		printf(
			/* translators: %1$s resolves to the link to search the plugin directory for Yoast SEO, %2$s resolves to the closing tag for this link, %3$s resolves to Local SEO */
			esc_html__( 'Please %1$sinstall & activate Yoast SEO%2$s to allow the %3$s module to work.', 'yoast-local-seo' ),
			'<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&type=term&s=yoast+seo&plugin-search-input=Search+Plugins' ) ) . '">',
			'</a>',
			'Local SEO'
		);
		echo '</p></div>';
	}
}

if ( ! function_exists( 'yoast_wpseo_local_upgrade_error' ) ) {
	/**
	 * Throw an error if Yoast SEO is out of date.
	 *
	 * @since 1.5.4
	 *
	 * @return void
	 */
	function yoast_wpseo_local_upgrade_error() {
		/* translators: %1$s resolves to Yoast SEO, %2$s resolves to Local SEO */
		echo '<div class="error"><p>' . sprintf( esc_html__( 'Please upgrade the %1$s plugin to the latest version to allow the %2$s module to work.', 'yoast-local-seo' ), 'Yoast SEO', 'Local SEO' ) . '</p></div>';
	}
}

/* ***************************** PLUGIN (DE-)ACTIVATION *************************** */
if ( ! function_exists( 'yoast_wpseo_local_activate' ) ) {
	/**
	 * Run single site / network-wide activation of the plugin.
	 *
	 * @param bool $networkwide Whether the plugin is being activated network-wide.
	 *
	 * @return void
	 */
	function yoast_wpseo_local_activate( $networkwide = false ) {
		if ( file_exists( WPSEO_LOCAL_PATH . 'vendor/autoload.php' ) ) {
			require_once WPSEO_LOCAL_PATH . 'vendor/autoload.php';
		}

		if ( ! is_multisite() || ! $networkwide ) {
			_yoast_wpseo_local_activate();
		}
		else {
			/* Multi-site network activation - activate the plugin for all blogs */
			yoast_wpseo_local_network_activate_deactivate( true );
		}

		// Enable tracking.
		if ( class_exists( 'WPSEO_Options' ) && method_exists( 'WPSEO_Options', 'set' ) ) {
			WPSEO_Options::set( 'tracking', true );
		}
	}
}

if ( ! function_exists( 'yoast_wpseo_local_deactivate' ) ) {
	/**
	 * Run single site / network-wide de-activation of the plugin.
	 *
	 * @param bool $networkwide Whether the plugin is being de-activated network-wide.
	 *
	 * @return void
	 */
	function yoast_wpseo_local_deactivate( $networkwide = false ) {
		if ( ! is_multisite() || ! $networkwide ) {
			_yoast_wpseo_local_deactivate();
		}
		else {
			/* Multi-site network activation - de-activate the plugin for all blogs */
			yoast_wpseo_local_network_activate_deactivate( false );
		}

		// Clean up notifications set bij Local SEO from Yoast Notification Center.
		if ( class_exists( 'Yoast_Notification_Center' ) ) {
			$notifications = [
				'LocalSEOBrowserKey',
				'PersonOrCompanySettingUpdate',
				'PersonOrCompanySettingError',
			];

			$notification_center = Yoast_Notification_Center::get();

			foreach ( $notifications as $notification_id ) {
				$notification = $notification_center->get_notification_by_id( $notification_id );

				if ( $notification instanceof Yoast_Notification ) {
					$notification_center->remove_notification( $notification );
				}
			}
		}
	}
}

if ( ! function_exists( 'yoast_wpseo_local_network_activate_deactivate' ) ) {
	/**
	 * Run network-wide (de-)activation of the plugin
	 *
	 * @param bool $activate True for plugin activation, false for de-activation.
	 *
	 * @return void
	 */
	function yoast_wpseo_local_network_activate_deactivate( $activate = true ) {
		global $wpdb;
		$network_blogs = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d", $wpdb->siteid ) );
		if ( is_array( $network_blogs ) && $network_blogs !== [] ) {
			foreach ( $network_blogs as $blog_id ) {
				switch_to_blog( $blog_id );
				if ( $activate === true ) {
					_yoast_wpseo_local_activate();
				}
				else {
					_yoast_wpseo_local_deactivate();
				}
				restore_current_blog();
			}
		}
	}
}

if ( ! function_exists( '_yoast_wpseo_local_activate' ) ) {
	/**
	 * Runs on activation of the plugin.
	 *
	 * @return void
	 */
	function _yoast_wpseo_local_activate() {
		WPSEO_Local_Core::update_sitemap();

		if ( method_exists( 'WPSEO_Sitemaps_Cache', 'clear' ) ) {
			WPSEO_Sitemaps_Cache::clear( [ WPSEO_Sitemaps::SITEMAP_INDEX_TYPE ] );
		}
	}
}

if ( ! function_exists( '_yoast_wpseo_local_deactivate' ) ) {
	/**
	 * Runs on deactivation of the plugin.
	 *
	 * @return void
	 */
	function _yoast_wpseo_local_deactivate() {
		if ( file_exists( WPSEO_LOCAL_PATH . 'vendor/autoload.php' ) ) {
			require_once WPSEO_LOCAL_PATH . 'vendor/autoload.php';
		}

		WPSEO_Local_Core::update_sitemap();

		if ( method_exists( 'WPSEO_Sitemaps_Cache', 'clear' ) ) {
			WPSEO_Sitemaps_Cache::clear( [ WPSEO_Sitemaps::SITEMAP_INDEX_TYPE ] );
		}
	}
}

if ( ! function_exists( 'yoast_wpseo_local_on_activate_blog' ) ) {
	/**
	 * Run yoast wpseo local activation routine on creation / activation of a multisite blog if Yoast WPSEO Local is activated
	 * network-wide.
	 *
	 * Will only be called by multisite actions.
	 *
	 * @internal Unfortunately will fail if the plugin is in the must-use directory
	 * @see      https://core.trac.wordpress.org/ticket/24205
	 *
	 * @param int $blog_id Blog ID.
	 *
	 * @return void
	 */
	function yoast_wpseo_local_on_activate_blog( $blog_id ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active_for_network( plugin_basename( WPSEO_FILE ) ) ) {
			switch_to_blog( $blog_id );
			yoast_wpseo_local_activate( false );
			restore_current_blog();
		}
	}
}

// Activation and de-activation hooks.
if ( function_exists( 'yoast_wpseo_local_activate' ) ) {
	register_activation_hook( WPSEO_LOCAL_FILE, 'yoast_wpseo_local_activate' );
}

if ( function_exists( 'yoast_wpseo_local_deactivate' ) ) {
	register_deactivation_hook( WPSEO_LOCAL_FILE, 'yoast_wpseo_local_deactivate' );
}

if ( function_exists( 'yoast_wpseo_local_on_activate_blog' ) ) {
	add_action( 'wpmu_new_blog', 'yoast_wpseo_local_on_activate_blog' );
	add_action( 'activate_blog', 'yoast_wpseo_local_on_activate_blog' );
}
