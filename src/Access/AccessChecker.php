<?php
/**
 * Access Checker for verifying user membership status.
 *
 * @package WpShiftStudio\WCMembershipProduct\Access
 */

namespace WpShiftStudio\WCMembershipProduct\Access;

use WpShiftStudio\WCMembershipProduct\DAL\MembershipDAL;

/**
 * Checks if users have access to restricted content.
 *
 * @since 1.0.0
 */
class AccessChecker {

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
	 * Checks if a user has access to restricted content.
	 *
	 * @param int      $user_id    The user ID. Defaults to current user.
	 * @param int|null $content_id Optional content ID for specific restrictions.
	 * @param int|null $product_id Optional product ID to check specific membership.
	 * @return bool True if user has access.
	 */
	public function has_access( $user_id = 0, $content_id = null, $product_id = null ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Not logged in = no access.
		if ( ! $user_id ) {
			return false;
		}

		// Check for specific product membership.
		if ( $product_id ) {
			$has_access = $this->membership_dal->user_has_active_membership( $user_id, $product_id );
		} else {
			// Check for any active membership.
			$has_access = $this->membership_dal->user_has_any_active_membership( $user_id );
		}

		/**
		 * Filters whether a user has access to restricted content.
		 *
		 * @since 1.0.0
		 *
		 * @param bool     $has_access Whether user has access.
		 * @param int      $user_id    The user ID.
		 * @param int|null $content_id The content ID.
		 * @param int|null $product_id The product ID.
		 */
		return apply_filters( 'wcmp_has_access', $has_access, $user_id, $content_id, $product_id );
	}

	/**
	 * Checks if current user has any active membership.
	 *
	 * @return bool
	 */
	public function current_user_has_membership() {
		return $this->has_access( get_current_user_id() );
	}

	/**
	 * Gets the active membership for current user.
	 *
	 * @return array|null The membership data or null.
	 */
	public function get_current_user_membership() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return null;
		}

		$memberships = $this->membership_dal->get_by_user_id( $user_id, 'active' );

		return ! empty( $memberships ) ? $memberships[0] : null;
	}

	/**
	 * Gets all active memberships for current user.
	 *
	 * @return array Array of membership records.
	 */
	public function get_current_user_memberships() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return array();
		}

		return $this->membership_dal->get_by_user_id( $user_id, 'active' );
	}
}
