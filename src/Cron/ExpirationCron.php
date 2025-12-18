<?php
/**
 * Expiration Cron for handling membership expirations.
 *
 * @package WpShiftStudio\WCMembershipProduct\Cron
 */

namespace WpShiftStudio\WCMembershipProduct\Cron;

use WpShiftStudio\WCMembershipProduct\DAL\MembershipDAL;

/**
 * Handles scheduled membership expiration checks.
 *
 * @since 1.0.0
 */
class ExpirationCron {

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
	 * Registers hooks for the expiration cron.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		$instance = new self();

		// Hook into the scheduled event.
		add_action( 'wcmp_daily_expiration_check', array( $instance, 'process_expirations' ) );
	}

	/**
	 * Processes expired memberships.
	 *
	 * @return void
	 */
	public function process_expirations() {
		$expired_memberships = $this->membership_dal->get_expired_active_memberships( 100 );

		if ( empty( $expired_memberships ) ) {
			$this->log( 'No expired memberships found.' );
			return;
		}

		$count = 0;

		foreach ( $expired_memberships as $membership ) {
			$result = $this->expire_membership( $membership );

			if ( $result ) {
				++$count;
			}
		}

		$this->log( sprintf( 'Processed %d expired memberships.', $count ) );

		// If we processed a full batch, there might be more.
		if ( count( $expired_memberships ) >= 100 ) {
			$this->schedule_immediate_followup();
		}
	}

	/**
	 * Expires a single membership.
	 *
	 * @param array $membership The membership data.
	 * @return bool True on success, false on failure.
	 */
	private function expire_membership( $membership ) {
		$result = $this->membership_dal->update_status( (int) $membership['id'], 'expired' );

		if ( $result ) {
			/**
			 * Fires when a membership expires.
			 *
			 * @since 1.0.0
			 *
			 * @param int $membership_id The membership ID.
			 * @param int $user_id       The user ID.
			 */
			do_action( 'wcmp_membership_expired', (int) $membership['id'], (int) $membership['user_id'] );

			// Send expiration notification email.
			$this->send_expiration_email( $membership );

			$this->log(
				sprintf(
					'Membership #%d for user #%d expired.',
					$membership['id'],
					$membership['user_id']
				)
			);
		}

		return $result;
	}

	/**
	 * Sends an expiration notification email to the user.
	 *
	 * @param array $membership The membership data.
	 * @return void
	 */
	private function send_expiration_email( $membership ) {
		$user = get_user_by( 'id', $membership['user_id'] );

		if ( ! $user ) {
			return;
		}

		$product = wc_get_product( $membership['product_id'] );
		$product_name = $product ? $product->get_name() : __( 'Membership', 'wc-membership-product' );

		$to      = $user->user_email;
		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Your membership has expired - %s', 'wc-membership-product' ),
			get_bloginfo( 'name' )
		);

		$message = sprintf(
			/* translators: 1: user display name, 2: product name, 3: shop URL */
			__(
				"Hi %1\$s,\n\nYour %2\$s membership has expired.\n\nTo continue enjoying member benefits, please renew your membership:\n%3\$s\n\nThank you for being a valued member!\n\nBest regards,\n%4\$s",
				'wc-membership-product'
			),
			$user->display_name,
			$product_name,
			wc_get_page_permalink( 'shop' ),
			get_bloginfo( 'name' )
		);

		/**
		 * Filters whether to send the expiration email.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $send       Whether to send the email.
		 * @param array $membership The membership data.
		 */
		$send_email = apply_filters( 'wcmp_send_expiration_email', true, $membership );

		if ( $send_email ) {
			wp_mail( $to, $subject, $message );
		}
	}

	/**
	 * Schedules an immediate follow-up if there are more to process.
	 *
	 * @return void
	 */
	private function schedule_immediate_followup() {
		if ( ! wp_next_scheduled( 'wcmp_expiration_followup' ) ) {
			wp_schedule_single_event( time() + 60, 'wcmp_daily_expiration_check' );
		}
	}

	/**
	 * Logs a message for debugging.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WC Membership Product] ' . $message );
		}
	}

	/**
	 * Manually triggers the expiration check (for testing/admin use).
	 *
	 * @return array Results of the expiration check.
	 */
	public static function run_manual_check() {
		$instance = new self();
		
		$expired = $instance->membership_dal->get_expired_active_memberships( 100 );
		$results = array(
			'found'     => count( $expired ),
			'processed' => 0,
			'details'   => array(),
		);

		foreach ( $expired as $membership ) {
			$success = $instance->expire_membership( $membership );
			
			if ( $success ) {
				++$results['processed'];
				$results['details'][] = sprintf(
					'Membership #%d (User #%d) expired.',
					$membership['id'],
					$membership['user_id']
				);
			}
		}

		return $results;
	}
}
