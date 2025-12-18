<?php
/**
 * Membership List Table for admin management.
 *
 * @package WpShiftStudio\WCMembershipProduct\Admin
 */

namespace WpShiftStudio\WCMembershipProduct\Admin;

use WpShiftStudio\WCMembershipProduct\DAL\MembershipDAL;

// Load WP_List_Table if not already loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Displays memberships in a list table format.
 *
 * @since 1.0.0
 */
class MembershipListTable extends \WP_List_Table {

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
		parent::__construct(
			array(
				'singular' => 'membership',
				'plural'   => 'memberships',
				'ajax'     => false,
			)
		);

		$this->membership_dal = new MembershipDAL();
	}

	/**
	 * Gets the list of columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'user'       => __( 'User', 'wc-membership-product' ),
			'product'    => __( 'Membership', 'wc-membership-product' ),
			'status'     => __( 'Status', 'wc-membership-product' ),
			'started_at' => __( 'Started', 'wc-membership-product' ),
			'expires_at' => __( 'Expires', 'wc-membership-product' ),
			'order'      => __( 'Order', 'wc-membership-product' ),
			'actions'    => __( 'Actions', 'wc-membership-product' ),
		);
	}

	/**
	 * Gets the sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'user'       => array( 'user_id', false ),
			'status'     => array( 'status', false ),
			'started_at' => array( 'started_at', false ),
			'expires_at' => array( 'expires_at', true ),
		);
	}

	/**
	 * Gets bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'revoke' => __( 'Revoke', 'wc-membership-product' ),
			'expire' => __( 'Mark as Expired', 'wc-membership-product' ),
		);
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();

		// Get filter values.
		$status     = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : null;
		$product_id = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : null;

		// Get sort values.
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order   = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';

		$args = array(
			'status'     => $status,
			'product_id' => $product_id,
			'limit'      => $per_page,
			'offset'     => ( $current_page - 1 ) * $per_page,
			'orderby'    => $orderby,
			'order'      => $order,
		);

		$this->items = $this->membership_dal->get_all( $args );
		$total_items = $this->membership_dal->count(
			array(
				'status'     => $status,
				'product_id' => $product_id,
			)
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Renders the checkbox column.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="membership_ids[]" value="%d" />',
			$item['id']
		);
	}

	/**
	 * Renders the user column.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_user( $item ) {
		$user = get_user_by( 'id', $item['user_id'] );

		if ( ! $user ) {
			return sprintf( __( 'User #%d (deleted)', 'wc-membership-product' ), $item['user_id'] );
		}

		$user_link = get_edit_user_link( $user->ID );

		return sprintf(
			'<a href="%s"><strong>%s</strong></a><br><small>%s</small>',
			esc_url( $user_link ),
			esc_html( $user->display_name ),
			esc_html( $user->user_email )
		);
	}

	/**
	 * Renders the product column.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_product( $item ) {
		$product = wc_get_product( $item['product_id'] );

		if ( ! $product ) {
			return sprintf( __( 'Product #%d (deleted)', 'wc-membership-product' ), $item['product_id'] );
		}

		$edit_link = get_edit_post_link( $product->get_id() );

		return sprintf(
			'<a href="%s">%s</a><br><small>%s: %s</small>',
			esc_url( $edit_link ),
			esc_html( $product->get_name() ),
			esc_html__( 'Tier', 'wc-membership-product' ),
			esc_html( ucfirst( $item['tier'] ) )
		);
	}

	/**
	 * Renders the status column.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_status( $item ) {
		$status = $item['status'];

		$status_classes = array(
			'active'    => 'status-active',
			'expired'   => 'status-expired',
			'cancelled' => 'status-cancelled',
		);

		$status_labels = array(
			'active'    => __( 'Active', 'wc-membership-product' ),
			'expired'   => __( 'Expired', 'wc-membership-product' ),
			'cancelled' => __( 'Cancelled', 'wc-membership-product' ),
		);

		$class = isset( $status_classes[ $status ] ) ? $status_classes[ $status ] : '';
		$label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( $status );

		return sprintf(
			'<span class="wcmp-status-badge %s">%s</span>',
			esc_attr( $class ),
			esc_html( $label )
		);
	}

	/**
	 * Renders the started_at column.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_started_at( $item ) {
		$date = strtotime( $item['started_at'] );
		return sprintf(
			'%s<br><small>%s</small>',
			esc_html( wp_date( get_option( 'date_format' ), $date ) ),
			esc_html( wp_date( get_option( 'time_format' ), $date ) )
		);
	}

	/**
	 * Renders the expires_at column.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_expires_at( $item ) {
		$date    = strtotime( $item['expires_at'] );
		$now     = current_time( 'timestamp' );
		$expired = $date < $now;

		$output = sprintf(
			'%s<br><small>%s</small>',
			esc_html( wp_date( get_option( 'date_format' ), $date ) ),
			esc_html( wp_date( get_option( 'time_format' ), $date ) )
		);

		if ( $expired && 'active' === $item['status'] ) {
			$output .= '<br><span class="wcmp-overdue">' . esc_html__( 'Overdue', 'wc-membership-product' ) . '</span>';
		}

		return $output;
	}

	/**
	 * Renders the order column.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_order( $item ) {
		$order = wc_get_order( $item['order_id'] );

		if ( ! $order ) {
			return sprintf( '#%d', $item['order_id'] );
		}

		$order_link = $order->get_edit_order_url();

		return sprintf(
			'<a href="%s">#%d</a>',
			esc_url( $order_link ),
			$item['order_id']
		);
	}

	/**
	 * Renders the actions column.
	 *
	 * @param array $item The current item.
	 * @return string
	 */
	public function column_actions( $item ) {
		$actions = array();

		$base_url = admin_url( 'admin.php?page=wcmp-memberships' );

		if ( 'active' === $item['status'] ) {
			$revoke_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'        => 'revoke',
						'membership_id' => $item['id'],
					),
					$base_url
				),
				'wcmp_membership_action'
			);

			$actions[] = sprintf(
				'<a href="%s" class="button button-small" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $revoke_url ),
				esc_js( __( 'Are you sure you want to revoke this membership?', 'wc-membership-product' ) ),
				esc_html__( 'Revoke', 'wc-membership-product' )
			);

			$extend_url = add_query_arg(
				array(
					'action'        => 'extend_form',
					'membership_id' => $item['id'],
				),
				$base_url
			);

			$actions[] = sprintf(
				'<a href="%s" class="button button-small">%s</a>',
				esc_url( $extend_url ),
				esc_html__( 'Extend', 'wc-membership-product' )
			);
		}

		if ( 'expired' === $item['status'] || 'cancelled' === $item['status'] ) {
			$reactivate_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'        => 'reactivate',
						'membership_id' => $item['id'],
					),
					$base_url
				),
				'wcmp_membership_action'
			);

			$actions[] = sprintf(
				'<a href="%s" class="button button-small">%s</a>',
				esc_url( $reactivate_url ),
				esc_html__( 'Reactivate', 'wc-membership-product' )
			);
		}

		return implode( ' ', $actions );
	}

	/**
	 * Message to display when no items are found.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No memberships found.', 'wc-membership-product' );
	}

	/**
	 * Renders extra table navigation (filters).
	 *
	 * @param string $which Top or bottom.
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$current_status     = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';
		$current_product_id = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : '';

		?>
		<div class="alignleft actions">
			<select name="status">
				<option value=""><?php esc_html_e( 'Filter By Status', 'wc-membership-product' ); ?></option>
				<option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Active', 'wc-membership-product' ); ?></option>
				<option value="expired" <?php selected( $current_status, 'expired' ); ?>><?php esc_html_e( 'Expired', 'wc-membership-product' ); ?></option>
				<option value="cancelled" <?php selected( $current_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'wc-membership-product' ); ?></option>
			</select>

			<select name="product_id">
				<option value=""><?php esc_html_e( 'Filter By Product', 'wc-membership-product' ); ?></option>
				<?php
				$products = wc_get_products(
					array(
						'type'   => 'membership',
						'status' => 'publish',
						'limit'  => -1,
					)
				);
				foreach ( $products as $product ) {
					printf(
						'<option value="%d" %s>%s</option>',
						esc_attr( $product->get_id() ),
						selected( $current_product_id, $product->get_id(), false ),
						esc_html( $product->get_name() )
					);
				}
				?>
			</select>

			<?php submit_button( __( 'Filter', 'wc-membership-product' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}
}
