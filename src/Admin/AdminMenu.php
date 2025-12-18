<?php
/**
 * Admin Menu for membership management.
 *
 * @package WpShiftStudio\WCMembershipProduct\Admin
 */

namespace WpShiftStudio\WCMembershipProduct\Admin;

use WpShiftStudio\WCMembershipProduct\DAL\MembershipDAL;
use WpShiftStudio\WCMembershipProduct\Access\AccessManager;

/**
 * Handles admin menu registration and page rendering.
 *
 * @since 1.0.0
 */
class AdminMenu {

	/**
	 * Registers hooks for admin menu.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	/**
	 * Adds the menu pages.
	 *
	 * @return void
	 */
	public static function add_menu_pages() {
		add_submenu_page(
			'woocommerce',
			__( 'Memberships', 'wc-membership-product' ),
			__( 'Memberships', 'wc-membership-product' ),
			'manage_woocommerce',
			'wcmp-memberships',
			array( __CLASS__, 'render_memberships_page' )
		);
	}

	/**
	 * Handles membership actions (revoke, extend, reactivate).
	 *
	 * @return void
	 */
	public static function handle_actions() {
		if ( ! isset( $_GET['page'] ) || 'wcmp-memberships' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['action'] ) ) {
			return;
		}

		$action        = sanitize_text_field( wp_unslash( $_GET['action'] ) );
		$membership_id = isset( $_GET['membership_id'] ) ? absint( $_GET['membership_id'] ) : 0;

		// Handle extend form submission.
		if ( 'extend' === $action && isset( $_POST['extend_duration'] ) ) {
			check_admin_referer( 'wcmp_extend_membership' );

			$duration = absint( $_POST['extend_duration'] );
			$unit     = isset( $_POST['extend_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['extend_unit'] ) ) : 'days';

			if ( $membership_id && $duration > 0 ) {
				$access_manager = new AccessManager();
				$result         = $access_manager->extend_membership( $membership_id, $duration, $unit );

				if ( $result ) {
					self::add_admin_notice( __( 'Membership extended successfully.', 'wc-membership-product' ), 'success' );
				} else {
					self::add_admin_notice( __( 'Failed to extend membership.', 'wc-membership-product' ), 'error' );
				}
			}

			wp_safe_redirect( admin_url( 'admin.php?page=wcmp-memberships' ) );
			exit;
		}

		// Skip if it's just the extend form display.
		if ( 'extend_form' === $action ) {
			return;
		}

		// Verify nonce for other actions.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wcmp_membership_action' ) ) {
			return;
		}

		if ( ! $membership_id ) {
			return;
		}

		$access_manager = new AccessManager();
		$membership_dal = new MembershipDAL();
		$redirect       = admin_url( 'admin.php?page=wcmp-memberships' );

		switch ( $action ) {
			case 'revoke':
				$result = $access_manager->revoke_membership( $membership_id );
				if ( $result ) {
					self::add_admin_notice( __( 'Membership revoked successfully.', 'wc-membership-product' ), 'success' );
				} else {
					self::add_admin_notice( __( 'Failed to revoke membership.', 'wc-membership-product' ), 'error' );
				}
				wp_safe_redirect( $redirect );
				exit;

			case 'reactivate':
				// Reactivate with 30 days extension from now.
				$membership = $membership_dal->get_by_id( $membership_id );
				if ( $membership ) {
					$new_expiration = gmdate( 'Y-m-d H:i:s', strtotime( '+30 days' ) );
					$result         = $membership_dal->update(
						$membership_id,
						array(
							'status'     => 'active',
							'expires_at' => $new_expiration,
						)
					);

					if ( $result ) {
						self::add_admin_notice( __( 'Membership reactivated for 30 days.', 'wc-membership-product' ), 'success' );
					} else {
						self::add_admin_notice( __( 'Failed to reactivate membership.', 'wc-membership-product' ), 'error' );
					}
				}
				wp_safe_redirect( $redirect );
				exit;
		}
	}

	/**
	 * Renders the memberships admin page.
	 *
	 * @return void
	 */
	public static function render_memberships_page() {
		// Check if showing extend form.
		if ( isset( $_GET['action'] ) && 'extend_form' === $_GET['action'] && isset( $_GET['membership_id'] ) ) {
			self::render_extend_form( absint( $_GET['membership_id'] ) );
			return;
		}

		$list_table = new MembershipListTable();
		$list_table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Memberships', 'wc-membership-product' ); ?></h1>
			<hr class="wp-header-end">

			<?php self::display_admin_notices(); ?>

			<form method="get">
				<input type="hidden" name="page" value="wcmp-memberships">
				<?php
				$list_table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the extend membership form.
	 *
	 * @param int $membership_id The membership ID.
	 * @return void
	 */
	private static function render_extend_form( $membership_id ) {
		$membership_dal = new MembershipDAL();
		$membership     = $membership_dal->get_by_id( $membership_id );

		if ( ! $membership ) {
			wp_die( esc_html__( 'Membership not found.', 'wc-membership-product' ) );
		}

		$user    = get_user_by( 'id', $membership['user_id'] );
		$product = wc_get_product( $membership['product_id'] );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Extend Membership', 'wc-membership-product' ); ?></h1>

			<div class="wcmp-extend-form-wrap">
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'User', 'wc-membership-product' ); ?></th>
						<td><?php echo esc_html( $user ? $user->display_name . ' (' . $user->user_email . ')' : 'Unknown' ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Membership', 'wc-membership-product' ); ?></th>
						<td><?php echo esc_html( $product ? $product->get_name() : 'Unknown' ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Current Expiration', 'wc-membership-product' ); ?></th>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $membership['expires_at'] ) ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Status', 'wc-membership-product' ); ?></th>
						<td><?php echo esc_html( ucfirst( $membership['status'] ) ); ?></td>
					</tr>
				</table>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wcmp-memberships&action=extend&membership_id=' . $membership_id ) ); ?>">
					<?php wp_nonce_field( 'wcmp_extend_membership' ); ?>

					<table class="form-table">
						<tr>
							<th><label for="extend_duration"><?php esc_html_e( 'Extend By', 'wc-membership-product' ); ?></label></th>
							<td>
								<input type="number" name="extend_duration" id="extend_duration" value="30" min="1" class="small-text">
								<select name="extend_unit">
									<option value="days"><?php esc_html_e( 'Days', 'wc-membership-product' ); ?></option>
									<option value="weeks"><?php esc_html_e( 'Weeks', 'wc-membership-product' ); ?></option>
									<option value="months"><?php esc_html_e( 'Months', 'wc-membership-product' ); ?></option>
									<option value="years"><?php esc_html_e( 'Years', 'wc-membership-product' ); ?></option>
								</select>
							</td>
						</tr>
					</table>

					<p class="submit">
						<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Extend Membership', 'wc-membership-product' ); ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcmp-memberships' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wc-membership-product' ); ?></a>
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueues admin styles.
	 *
	 * @param string $hook The current admin page.
	 * @return void
	 */
	public static function enqueue_styles( $hook ) {
		if ( 'woocommerce_page_wcmp-memberships' !== $hook ) {
			return;
		}

		wp_add_inline_style(
			'woocommerce_admin_styles',
			'
			.wcmp-status-badge {
				display: inline-block;
				padding: 3px 8px;
				border-radius: 3px;
				font-size: 12px;
				font-weight: 600;
				text-transform: uppercase;
			}
			.wcmp-status-badge.status-active {
				background: #c6e1c6;
				color: #5b841b;
			}
			.wcmp-status-badge.status-expired {
				background: #f8dda7;
				color: #94660c;
			}
			.wcmp-status-badge.status-cancelled {
				background: #eba3a3;
				color: #761919;
			}
			.wcmp-overdue {
				color: #d63638;
				font-weight: 600;
				font-size: 11px;
			}
			.wcmp-extend-form-wrap {
				background: #fff;
				padding: 20px;
				border: 1px solid #ccd0d4;
				margin-top: 20px;
				max-width: 600px;
			}
			.column-actions .button {
				margin-right: 5px;
			}
			'
		);
	}

	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @param string $message The message.
	 * @param string $type    The type (success, error, warning, info).
	 * @return void
	 */
	private static function add_admin_notice( $message, $type = 'success' ) {
		set_transient( 'wcmp_admin_notice_' . get_current_user_id(), array(
			'message' => $message,
			'type'    => $type,
		), 30 );
	}

	/**
	 * Displays admin notices.
	 *
	 * @return void
	 */
	private static function display_admin_notices() {
		$notice = get_transient( 'wcmp_admin_notice_' . get_current_user_id() );

		if ( $notice ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);

			delete_transient( 'wcmp_admin_notice_' . get_current_user_id() );
		}
	}
}
