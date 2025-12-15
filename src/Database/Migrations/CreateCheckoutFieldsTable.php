<?php
/**
 * Creates the checkout fields database table.
 *
 * @package WpShiftStudio\WCMembershipProduct\Database\Migrations
 */

namespace WpShiftStudio\WCMembershipProduct\Database\Migrations;

/**
 * Migration for the wp_wcmp_checkout_fields table.
 *
 * @since 1.0.0
 */
class CreateCheckoutFieldsTable implements MigrationInterface {

	/**
	 * Creates the checkout fields table.
	 *
	 * @return void
	 */
	public function up() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'wcmp_checkout_fields';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id bigint(20) UNSIGNED NOT NULL,
			field_key varchar(100) NOT NULL,
			field_value text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY order_id_idx (order_id),
			KEY field_key_idx (field_key)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drops the checkout fields table.
	 *
	 * @return void
	 */
	public function down() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wcmp_checkout_fields';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"DROP TABLE IF EXISTS {$table_name}"
		);
	}
}
