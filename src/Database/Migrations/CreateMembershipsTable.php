<?php
/**
 * Creates the memberships database table.
 *
 * @package WpShiftStudio\WCMembershipProduct\Database\Migrations
 */

namespace WpShiftStudio\WCMembershipProduct\Database\Migrations;

/**
 * Migration for the wp_wcmp_memberships table.
 *
 * @since 1.0.0
 */
class CreateMembershipsTable implements MigrationInterface {

	/**
	 * Creates the memberships table.
	 *
	 * @return void
	 */
	public function up() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'wcmp_memberships';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			product_id bigint(20) UNSIGNED NOT NULL,
			order_id bigint(20) UNSIGNED NOT NULL,
			tier varchar(50) NOT NULL DEFAULT 'standard',
			status varchar(20) NOT NULL DEFAULT 'active',
			started_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY user_status_idx (user_id, status),
			KEY expires_status_idx (expires_at, status),
			KEY order_id_idx (order_id),
			KEY product_id_idx (product_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drops the memberships table.
	 *
	 * @return void
	 */
	public function down() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wcmp_memberships';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"DROP TABLE IF EXISTS {$table_name}"
		);
	}
}
