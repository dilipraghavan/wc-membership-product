<?php
/**
 * Plugin Name:       WC Membership Product
 * Plugin URI:        https://github.com/dilipraghavan/wc-membership-product
 * Description:       Adds a custom Membership product type with duration-based access control, conditional checkout fields, and automated expiration handling.
 * Version:           1.0.0
 * Author:            Dilip Raghavan
 * Author URI:        https://www.wpshiftstudio.com
 * Text Domain:       wc-membership-product
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 5.8
 * WC requires at least: 6.0
 * WC tested up to:   8.0
 *
 * @package WpShiftStudio\WCMembershipProduct
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCMP_VERSION', '1.0.0' );
define( 'WCMP_PLUGIN_FILE', __FILE__ );
define( 'WCMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require __DIR__ . '/vendor/autoload.php';

use WpShiftStudio\WCMembershipProduct\Init;

register_activation_hook( __FILE__, array( Init::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Init::class, 'deactivate' ) );
register_uninstall_hook( __FILE__, array( Init::class, 'uninstall' ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					?>
					<div class="notice notice-error">
						<p><?php esc_html_e( 'WC Membership Product requires WooCommerce to be installed and active.', 'wc-membership-product' ); ?></p>
					</div>
					<?php
				}
			);
			return;
		}
		Init::register_hooks();
	}
);
