<?php

namespace Yoast\WP\Local\Integrations;

use WPSEO_Admin_Asset_Manager;
use WPSEO_Options;
use WPSEO_Shortlinker;
use Yoast\WP\Local\Conditionals\Admin_Conditional;
use Yoast\WP\SEO\Helpers\Capability_Helper;
use Yoast\WP\SEO\Integrations\Integration_Interface;
use Yoast\WP\SEO\Presenters\Admin\Notice_Presenter;

/**
 * Local_Pickup_Notification class
 */
class Local_Pickup_Notification implements Integration_Interface {

	/**
	 * The capability helper.
	 *
	 * @var Capability_Helper
	 */
	private $capability_helper;

	/**
	 * The admin asset manager.
	 *
	 * @var WPSEO_Admin_Asset_Manager
	 */
	private $admin_asset_manager;

	/**
	 * {@inheritDoc}
	 */
	public static function get_conditionals() {
		return [ Admin_Conditional::class ];
	}

	/**
	 * Local_Pickup_Notification constructor.
	 *
	 * @param Capability_Helper $capability_helper The capability helper.
	 */
	public function __construct( Capability_Helper $capability_helper ) {
		$this->admin_asset_manager = new WPSEO_Admin_Asset_Manager();
		$this->capability_helper   = $capability_helper;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks() {
		\add_action( 'admin_notices', [ $this, 'local_pickup_notice' ] );
		\add_action( 'wp_ajax_dismiss_local_pickup_notice', [ $this, 'dismiss_local_pickup_notice' ] );
	}

	/**
	 * Shows a notice if Local store pickup option is enabled and it's not being dismissed before.
	 *
	 * @return void
	 */
	public function local_pickup_notice() {
		global $pagenow;
		if ( $pagenow === 'update.php' ) {
			return;
		}

		if ( ! $this->capability_helper->current_user_can( 'wpseo_manage_options' ) ) {
			return;
		}

		if ( WPSEO_Options::get( 'dismiss_local_pickup_notice', false ) ) {
			return;
		}

		if ( $this->local_pickup_is_enabled() ) {
			$this->admin_asset_manager->enqueue_style( 'monorepo' );

			$content = \sprintf(
				/* translators: %1$s link open tag; %2$s link close tag */
				\__( 'Please use the \'Local Pickup\' feature in the latest version of WooCommerce instead. To ensure functionality, please re-enter your settings there. %1$sRead more about setting up%2$s.', 'yoast-local-seo' ),
				'<a href="' . WPSEO_Shortlinker::get( 'https://yoa.st/local-setting-up-notice' ) . '" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output of the title escaped in the Notice_Presenter.
			echo new Notice_Presenter(
				\sprintf(
					/* translators: %1$s expands to Yoast Local SEO */
					\__( 'The \'Local Store Pickup\' feature will soon be deprecated from %1$s', 'yoast-local-seo' ),
					'Yoast Local SEO'
				),
				$content,
				null,
				null,
				true,
				'yoast-local-pickup-notice'
			);
			// phpcs:enable

			// Enable permanently dismissing the notice.
			echo "<script>
                function dismiss_local_pickup_notice(){
                    var data = {
                    'action': 'dismiss_local_pickup_notice',
                    };

                    jQuery.post( ajaxurl, data, function( response ) {
                        jQuery( '#yoast-local-pickup-notice' ).hide();
                    });
                }

                jQuery( document ).ready( function() {
                    jQuery( 'body' ).on( 'click', '#yoast-local-pickup-notice .notice-dismiss', function() {
                        dismiss_local_pickup_notice();
                    } );
                } );
            </script>";
		}
	}

	/**
	 * Dismisses the notice.
	 *
	 * @return bool
	 */
	public function dismiss_local_pickup_notice() {
		return WPSEO_Options::set( 'dismiss_local_pickup_notice', true );
	}

	/**
	 * Returns whether Local store pickup option is enabled.
	 *
	 * @return bool Whether Local store pickup option is enabled.
	 */
	protected function local_pickup_is_enabled() {
		$local_pickup_settings = \get_option( 'woocommerce_yoast_wcseo_local_pickup_settings' );
		if ( isset( $local_pickup_settings['enabled'] ) && $local_pickup_settings['enabled'] === 'yes' ) {
			return true;
		}
		return false;
	}
}
