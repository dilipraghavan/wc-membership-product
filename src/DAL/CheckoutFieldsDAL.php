<?php
/**
 * Data Access Layer for checkout fields.
 *
 * @package WpShiftStudio\WCMembershipProduct\DAL
 */

namespace WpShiftStudio\WCMembershipProduct\DAL;

/**
 * Handles all database operations for custom checkout fields.
 *
 * @since 1.0.0
 */
class CheckoutFieldsDAL {

	/**
	 * Gets the checkout fields table name with prefix.
	 *
	 * @return string
	 */
	private function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wcmp_checkout_fields';
	}

	/**
	 * Saves a single checkout field.
	 *
	 * @param int    $order_id    The order ID.
	 * @param string $field_key   The field key/name.
	 * @param string $field_value The field value.
	 * @return int|false The new record ID or false on failure.
	 */
	public function save_field( int $order_id, string $field_key, string $field_value ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->get_table_name(),
			array(
				'order_id'    => $order_id,
				'field_key'   => $field_key,
				'field_value' => $field_value,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Saves multiple checkout fields for an order.
	 *
	 * @param int   $order_id The order ID.
	 * @param array $fields   Associative array of field_key => field_value.
	 * @return bool True if all fields saved successfully, false otherwise.
	 */
	public function save_fields( int $order_id, array $fields ) {
		$success = true;

		foreach ( $fields as $key => $value ) {
			$result = $this->save_field( $order_id, $key, $value );
			if ( false === $result ) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Gets all checkout fields for an order.
	 *
	 * @param int $order_id The order ID.
	 * @return array Associative array of field_key => field_value.
	 */
	public function get_fields_by_order_id( int $order_id ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT field_key, field_value FROM {$table_name} WHERE order_id = %d",
				$order_id
			),
			ARRAY_A
		);

		if ( ! is_array( $results ) ) {
			return array();
		}

		$fields = array();
		foreach ( $results as $row ) {
			$fields[ $row['field_key'] ] = $row['field_value'];
		}

		return $fields;
	}

	/**
	 * Gets a specific field value for an order.
	 *
	 * @param int    $order_id  The order ID.
	 * @param string $field_key The field key to retrieve.
	 * @return string|null The field value or null if not found.
	 */
	public function get_field( int $order_id, string $field_key ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$value = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT field_value FROM {$table_name} WHERE order_id = %d AND field_key = %s",
				$order_id,
				$field_key
			)
		);

		return $value;
	}

	/**
	 * Updates a specific field value for an order.
	 *
	 * @param int    $order_id    The order ID.
	 * @param string $field_key   The field key.
	 * @param string $field_value The new field value.
	 * @return bool True on success, false on failure.
	 */
	public function update_field( int $order_id, string $field_key, string $field_value ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->get_table_name(),
			array( 'field_value' => $field_value ),
			array(
				'order_id'  => $order_id,
				'field_key' => $field_key,
			),
			array( '%s' ),
			array( '%d', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Deletes all checkout fields for an order.
	 *
	 * @param int $order_id The order ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_fields_by_order_id( int $order_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->get_table_name(),
			array( 'order_id' => $order_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Deletes a specific field for an order.
	 *
	 * @param int    $order_id  The order ID.
	 * @param string $field_key The field key to delete.
	 * @return bool True on success, false on failure.
	 */
	public function delete_field( int $order_id, string $field_key ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->get_table_name(),
			array(
				'order_id'  => $order_id,
				'field_key' => $field_key,
			),
			array( '%d', '%s' )
		);

		return false !== $result;
	}
}
