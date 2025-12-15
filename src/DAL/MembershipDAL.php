<?php
/**
 * Data Access Layer for memberships.
 *
 * @package WpShiftStudio\WCMembershipProduct\DAL
 */

namespace WpShiftStudio\WCMembershipProduct\DAL;

/**
 * Handles all database operations for memberships.
 *
 * @since 1.0.0
 */
class MembershipDAL {

	/**
	 * Gets the memberships table name with prefix.
	 *
	 * @return string
	 */
	private function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wcmp_memberships';
	}

	/**
	 * Creates a new membership record.
	 *
	 * @param array $data {
	 *     Membership data.
	 *
	 *     @type int    $user_id    The user ID.
	 *     @type int    $product_id The membership product ID.
	 *     @type int    $order_id   The order ID.
	 *     @type string $tier       The membership tier (default 'standard').
	 *     @type string $status     The status (default 'active').
	 *     @type string $started_at The start date (Y-m-d H:i:s).
	 *     @type string $expires_at The expiration date (Y-m-d H:i:s).
	 * }
	 * @return int|false The new membership ID or false on failure.
	 */
	public function create( array $data ) {
		global $wpdb;

		$now = current_time( 'mysql' );

		$defaults = array(
			'tier'       => 'standard',
			'status'     => 'active',
			'created_at' => $now,
			'updated_at' => $now,
		);

		$data = wp_parse_args( $data, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->get_table_name(),
			array(
				'user_id'    => $data['user_id'],
				'product_id' => $data['product_id'],
				'order_id'   => $data['order_id'],
				'tier'       => $data['tier'],
				'status'     => $data['status'],
				'started_at' => $data['started_at'],
				'expires_at' => $data['expires_at'],
				'created_at' => $data['created_at'],
				'updated_at' => $data['updated_at'],
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Gets a membership by ID.
	 *
	 * @param int $id The membership ID.
	 * @return array|null The membership row or null if not found.
	 */
	public function get_by_id( int $id ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table_name} WHERE id = %d",
				$id
			),
			ARRAY_A
		);
	}

	/**
	 * Gets a membership by order ID.
	 *
	 * @param int $order_id The WooCommerce order ID.
	 * @return array|null The membership row or null if not found.
	 */
	public function get_by_order_id( int $order_id ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table_name} WHERE order_id = %d",
				$order_id
			),
			ARRAY_A
		);
	}

	/**
	 * Gets all memberships for a user.
	 *
	 * @param int         $user_id The user ID.
	 * @param string|null $status  Optional status filter.
	 * @return array Array of membership rows.
	 */
	public function get_by_user_id( int $user_id, ?string $status = null ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		if ( null !== $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$table_name} WHERE user_id = %d AND status = %s ORDER BY created_at DESC",
					$user_id,
					$status
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC",
					$user_id
				),
				ARRAY_A
			);
		}

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Checks if a user has an active membership for a specific product.
	 *
	 * @param int $user_id    The user ID.
	 * @param int $product_id The product ID.
	 * @return bool True if active membership exists.
	 */
	public function user_has_active_membership( int $user_id, int $product_id ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$now        = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(id) FROM {$table_name} 
				WHERE user_id = %d 
				AND product_id = %d 
				AND status = 'active' 
				AND expires_at > %s",
				$user_id,
				$product_id,
				$now
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Checks if a user has any active membership.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if any active membership exists.
	 */
	public function user_has_any_active_membership( int $user_id ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$now        = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(id) FROM {$table_name} 
				WHERE user_id = %d 
				AND status = 'active' 
				AND expires_at > %s",
				$user_id,
				$now
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Updates a membership record.
	 *
	 * @param int   $id   The membership ID.
	 * @param array $data The data to update.
	 * @return bool True on success, false on failure.
	 */
	public function update( int $id, array $data ) {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		$formats = array();
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, array( 'user_id', 'product_id', 'order_id' ), true ) ) {
				$formats[] = '%d';
			} else {
				$formats[] = '%s';
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->get_table_name(),
			$data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Updates the status of a membership.
	 *
	 * @param int    $id     The membership ID.
	 * @param string $status The new status.
	 * @return bool True on success, false on failure.
	 */
	public function update_status( int $id, string $status ) {
		return $this->update( $id, array( 'status' => $status ) );
	}

	/**
	 * Gets all expired memberships that are still marked as active.
	 *
	 * @param int $limit Maximum number of records to return.
	 * @return array Array of expired membership rows.
	 */
	public function get_expired_active_memberships( int $limit = 100 ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$now        = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table_name} 
				WHERE status = 'active' 
				AND expires_at <= %s 
				ORDER BY expires_at ASC 
				LIMIT %d",
				$now,
				$limit
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Deletes a membership record.
	 *
	 * @param int $id The membership ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( int $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->get_table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Gets all memberships with optional filters.
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type string $status     Filter by status.
	 *     @type int    $product_id Filter by product ID.
	 *     @type int    $limit      Maximum records to return.
	 *     @type int    $offset     Offset for pagination.
	 *     @type string $orderby    Column to order by.
	 *     @type string $order      ASC or DESC.
	 * }
	 * @return array Array of membership rows.
	 */
	public function get_all( array $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'     => null,
			'product_id' => null,
			'limit'      => 20,
			'offset'     => 0,
			'orderby'    => 'created_at',
			'order'      => 'DESC',
		);

		$args       = wp_parse_args( $args, $defaults );
		$table_name = $this->get_table_name();
		$where      = array( '1=1' );
		$values     = array();

		if ( null !== $args['status'] ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( null !== $args['product_id'] ) {
			$where[]  = 'product_id = %d';
			$values[] = $args['product_id'];
		}

		$where_clause      = implode( ' AND ', $where );
		$orderby_sanitized = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		$orderby           = $orderby_sanitized ? $orderby_sanitized : 'created_at DESC';

		$values[] = $args['limit'];
		$values[] = $args['offset'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$orderby} LIMIT %d OFFSET %d",
				$values
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Counts memberships with optional filters.
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type string $status     Filter by status.
	 *     @type int    $product_id Filter by product ID.
	 * }
	 * @return int The count.
	 */
	public function count( array $args = array() ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$where      = array( '1=1' );
		$values     = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['product_id'] ) ) {
			$where[]  = 'product_id = %d';
			$values[] = $args['product_id'];
		}

		$where_clause = implode( ' AND ', $where );

		if ( empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(id) FROM {$table_name} WHERE {$where_clause}"
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$count = $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COUNT(id) FROM {$table_name} WHERE {$where_clause}",
					$values
				)
			);
		}

		return (int) $count;
	}
}
