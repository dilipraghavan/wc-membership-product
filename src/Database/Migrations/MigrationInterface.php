<?php
/**
 * Migration interface for database tables.
 *
 * @package WpShiftStudio\WCMembershipProduct\Database\Migrations
 */

namespace WpShiftStudio\WCMembershipProduct\Database\Migrations;

/**
 * Interface for database migrations.
 *
 * @since 1.0.0
 */
interface MigrationInterface {

	/**
	 * Runs the migration (creates the table).
	 *
	 * @return void
	 */
	public function up();

	/**
	 * Reverses the migration (drops the table).
	 *
	 * @return void
	 */
	public function down();
}
