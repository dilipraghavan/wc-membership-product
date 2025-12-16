<?php
/**
 * Access Manager for handling membership grants and revocations.
 *
 * @package WpShiftStudio\WCMembershipProduct\Access
 */

namespace WpShiftStudio\WCMembershipProduct\Access;

use WpShiftStudio\WCMembershipProduct\DAL\MembershipDAL;
use WpShiftStudio\WCMembershipProduct\Product\MembershipProduct;

/**
 * Manages membership access grants and revocations.
 *
 * @since 1.0.0
 */
class AccessManager {

	/**
	 * Membership DAL instance.
	 *
	 * @var MembershipDAL
	 */
	private $membership_dal;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->membership_dal = new MembershipDAL();
	}

	/**
	 * Registers hooks for access management.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		$instance = new self();

		// Grant access on order completion.
		add_action( 'woocommerce_order_status_completed', array( $instance, 'process_completed_order' ), 10, 1 );

		// Also handle processing status for gateways that don't use completed.
		add_action( 'woocommerce_order_status_processing', array( $instance, 'process_completed_order' ), 10, 1 );
	}

	/**
	 * Processes a completed order and grants membership access.
	 *
	 * @param int $order_id The order ID.
	 * @return void
	 */
	public function process_completed_order( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Check if we've already processed this order.
		$processed = $order->get_meta( '_wcmp_membership_processed' );
		if ( 'yes' === $processed ) {
			return;
		}

		$user_id = $order->get_user_id();

		// Guest orders cannot have memberships.
		if ( ! $user_id ) {
			return;
		}

		$membership_granted = false;

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product || 'membership' !== $product->get_type() ) {
				continue;
			}

			// Grant membership for this product.
			$membership_id = $this->grant_membership( $user_id, $product, $order );

			if ( $membership_id ) {
				$membership_granted = true;

				// Add order note.
				$order->add_order_note(
					sprintf(
						/* translators: 1: membership ID, 2: product name */
						__( 'Membership #%1$d granted for "%2$s".', 'wc-membership-product' ),
						$membership_id,
						$product->get_name()
					)
				);
			}
		}

		if ( $membership_granted ) {
			// Mark order as processed to prevent duplicate grants.
			$order->update_meta_data( '_wcmp_membership_processed', 'yes' );
			$order->save();
		}
	}

	/**
	 * Grants a membership to a user.
	 *
	 * @param int               $user_id The user ID.
	 * @param MembershipProduct $product The membership product.
	 * @param \WC_Order         $order   The order object.
	 * @return int|false The membership ID or false on failure.
	 */
	public function grant_membership( $user_id, $product, $order ) {
		$now        = current_time( 'mysql' );
		$expires_at = $product->calculate_expiration_date( $now );

		$data = array(
			'user_id'    => $user_id,
			'product_id' => $product->get_id(),
			'order_id'   => $order->get_id(),
			'tier'       => $product->get_membership_tier(),
			'status'     => 'active',
			'started_at' => $now,
			'expires_at' => $expires_at,
		);

		$membership_id = $this->membership_dal->create( $data );

		if ( $membership_id ) {
			/**
			 * Fires when a membership is granted.
			 *
			 * @since 1.0.0
			 *
			 * @param int $membership_id The membership ID.
			 * @param int $user_id       The user ID.
			 * @param int $product_id    The product ID.
			 * @param int $order_id      The order ID.
			 */
			do_action( 'wcmp_membership_granted', $membership_id, $user_id, $product->get_id(), $order->get_id() );
		}

		return $membership_id;
	}

	/**
	 * Revokes a membership.
	 *
	 * @param int $membership_id The membership ID.
	 * @return bool True on success, false on failure.
	 */
	public function revoke_membership( $membership_id ) {
		$membership = $this->membership_dal->get_by_id( $membership_id );

		if ( ! $membership ) {
			return false;
		}

		$result = $this->membership_dal->update_status( $membership_id, 'cancelled' );

		if ( $result ) {
			/**
			 * Fires when a membership is revoked.
			 *
			 * @since 1.0.0
			 *
			 * @param int $membership_id The membership ID.
			 * @param int $user_id       The user ID.
			 */
			do_action( 'wcmp_membership_revoked', $membership_id, $membership['user_id'] );
		}

		return $result;
	}

	/**
	 * Expires a membership.
	 *
	 * @param int $membership_id The membership ID.
	 * @return bool True on success, false on failure.
	 */
	public function expire_membership( $membership_id ) {
		$membership = $this->membership_dal->get_by_id( $membership_id );

		if ( ! $membership ) {
			return false;
		}

		$result = $this->membership_dal->update_status( $membership_id, 'expired' );

		if ( $result ) {
			/**
			 * Fires when a membership expires.
			 *
			 * @since 1.0.0
			 *
			 * @param int $membership_id The membership ID.
			 * @param int $user_id       The user ID.
			 */
			do_action( 'wcmp_membership_expired', $membership_id, $membership['user_id'] );
		}

		return $result;
	}

	/**
	 * Extends a membership by a given duration.
	 *
	 * @param int    $membership_id The membership ID.
	 * @param int    $duration      The duration value.
	 * @param string $unit          The duration unit (days, weeks, months, years).
	 * @return bool True on success, false on failure.
	 */
	public function extend_membership( $membership_id, $duration, $unit = 'days' ) {
		$membership = $this->membership_dal->get_by_id( $membership_id );

		if ( ! $membership ) {
			return false;
		}

		// Calculate new expiration from current expiration or now if already expired.
		$current_expiration = $membership['expires_at'];
		$now                = current_time( 'mysql' );

		if ( strtotime( $current_expiration ) < strtotime( $now ) ) {
			// Already expired, extend from now.
			$base_date = $now;
		} else {
			// Still active, extend from current expiration.
			$base_date = $current_expiration;
		}

		$new_expiration = gmdate( 'Y-m-d H:i:s', strtotime( "+{$duration} {$unit}", strtotime( $base_date ) ) );

		$data = array(
			'expires_at' => $new_expiration,
			'status'     => 'active',
		);

		return $this->membership_dal->update( $membership_id, $data );
	}

	/**
	 * Gets all active memberships for a user.
	 *
	 * @param int $user_id The user ID.
	 * @return array Array of membership records.
	 */
	public function get_user_active_memberships( $user_id ) {
		return $this->membership_dal->get_by_user_id( $user_id, 'active' );
	}

	/**
	 * Checks if a user has an active membership for a product.
	 *
	 * @param int $user_id    The user ID.
	 * @param int $product_id The product ID.
	 * @return bool True if user has active membership.
	 */
	public function user_has_membership( $user_id, $product_id ) {
		return $this->membership_dal->user_has_active_membership( $user_id, $product_id );
	}

	/**
	 * Checks if a user has any active membership.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if user has any active membership.
	 */
	public function user_has_any_membership( $user_id ) {
		return $this->membership_dal->user_has_any_active_membership( $user_id );
	}
}
