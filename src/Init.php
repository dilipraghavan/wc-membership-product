<?php
/**
 * Plugin initialization and lifecycle management.
 *
 * @package WpShiftStudio\WCMembershipProduct
 */

namespace WpShiftStudio\WCMembershipProduct;

use WpShiftStudio\WCMembershipProduct\Database\Migrator;
use WpShiftStudio\WCMembershipProduct\Admin\ProductAdmin;
use WpShiftStudio\WCMembershipProduct\Access\AccessManager;

/**
 * Handles plugin activation, deactivation, and hook registration.
 *
 * @since 1.0.0
 */
class Init {

	/**
	 * Runs on plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( WCMP_PLUGIN_FILE ) );
			wp_die(
				esc_html__( 'WC Membership Product requires WooCommerce to be installed and active.', 'wc-membership-product' ),
				'Plugin Activation Error',
				array( 'back_link' => true )
			);
		}

		$migrator = new Migrator();
		$migrator->run();

		// Schedule cron for expiration checks.
		if ( ! wp_next_scheduled( 'wcmp_daily_expiration_check' ) ) {
			wp_schedule_event( time(), 'daily', 'wcmp_daily_expiration_check' );
		}

		flush_rewrite_rules();
	}

	/**
	 * Runs on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'wcmp_daily_expiration_check' );
		flush_rewrite_rules();
	}

	/**
	 * Runs on plugin uninstall.
	 *
	 * @return void
	 */
	public static function uninstall() {
		// Only clean up if user has opted in (future setting).
		// For now, we preserve data on uninstall.
	}

	/**
	 * Registers all plugin hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		// Declare HPOS compatibility.
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_hpos_compatibility' ) );

		// Product type admin.
		ProductAdmin::register_hooks();

		// Access management (order completion, grants, revocations).
		AccessManager::register_hooks();
	}

	/**
	 * Declares compatibility with WooCommerce High-Performance Order Storage.
	 *
	 * @return void
	 */
	public static function declare_hpos_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WCMP_PLUGIN_FILE, true );
		}
	}
}
