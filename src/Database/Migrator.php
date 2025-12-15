<?php
/**
 * Manages all database migrations for the plugin.
 *
 * @package WpShiftStudio\WCMembershipProduct\Database
 */

namespace WpShiftStudio\WCMembershipProduct\Database;

use WpShiftStudio\WCMembershipProduct\Database\Migrations\CreateMembershipsTable;
use WpShiftStudio\WCMembershipProduct\Database\Migrations\CreateCheckoutFieldsTable;

/**
 * Runs database migrations on plugin activation.
 *
 * @since 1.0.0
 */
class Migrator {

	/**
	 * Array of migration classes to run.
	 *
	 * @var array
	 */
	protected $migrations = array(
		CreateMembershipsTable::class,
		CreateCheckoutFieldsTable::class,
	);

	/**
	 * Runs all the database migrations.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->migrations as $migration_class ) {
			( new $migration_class() )->up();
		}
	}
}
