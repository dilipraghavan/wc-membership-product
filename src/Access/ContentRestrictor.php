<?php
/**
 * Content Restrictor for handling membership-based content access.
 *
 * @package WpShiftStudio\WCMembershipProduct\Access
 */

namespace WpShiftStudio\WCMembershipProduct\Access;

/**
 * Handles content restriction via shortcode and meta box.
 *
 * @since 1.0.0
 */
class ContentRestrictor {

	/**
	 * Access Checker instance.
	 *
	 * @var AccessChecker
	 */
	private $access_checker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->access_checker = new AccessChecker();
	}

	/**
	 * Registers hooks for content restriction.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		$instance = new self();

		// Register shortcode.
		add_shortcode( 'wcmp_restricted', array( $instance, 'restricted_shortcode' ) );

		// Register meta box.
		add_action( 'add_meta_boxes', array( $instance, 'add_restriction_meta_box' ) );
		add_action( 'save_post', array( $instance, 'save_restriction_meta' ), 10, 2 );

		// Filter content for restricted posts/pages.
		add_filter( 'the_content', array( $instance, 'filter_restricted_content' ), 99 );
	}

	/**
	 * Handles the [wcmp_restricted] shortcode.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content The content inside the shortcode.
	 * @return string
	 */
	public function restricted_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'product_id' => 0,
				'message'    => '',
			),
			$atts,
			'wcmp_restricted'
		);

		$product_id = absint( $atts['product_id'] );
		$has_access = $this->access_checker->has_access( 0, null, $product_id ? $product_id : null );

		if ( $has_access ) {
			return do_shortcode( $content );
		}

		// Return restricted message.
		$message = $atts['message'];
		if ( empty( $message ) ) {
			$message = $this->get_default_restricted_message();
		}

		return $this->render_restricted_message( $message );
	}

	/**
	 * Adds the restriction meta box to posts and pages.
	 *
	 * @return void
	 */
	public function add_restriction_meta_box() {
		$post_types = apply_filters( 'wcmp_restricted_post_types', array( 'post', 'page' ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wcmp_content_restriction',
				__( 'Membership Restriction', 'wc-membership-product' ),
				array( $this, 'render_restriction_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Renders the restriction meta box.
	 *
	 * @param \WP_Post $post The post object.
	 * @return void
	 */
	public function render_restriction_meta_box( $post ) {
		$is_restricted = get_post_meta( $post->ID, '_wcmp_is_restricted', true );
		$product_id    = get_post_meta( $post->ID, '_wcmp_required_product', true );

		wp_nonce_field( 'wcmp_restriction_meta', 'wcmp_restriction_nonce' );
		?>
		<p>
			<label>
				<input 
					type="checkbox" 
					name="_wcmp_is_restricted" 
					value="1" 
					<?php checked( $is_restricted, '1' ); ?>
				>
				<?php esc_html_e( 'Restrict to members only', 'wc-membership-product' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'When enabled, only users with an active membership can view this content.', 'wc-membership-product' ); ?>
		</p>

		<p>
			<label for="_wcmp_required_product">
				<?php esc_html_e( 'Required Membership (optional):', 'wc-membership-product' ); ?>
			</label>
			<select name="_wcmp_required_product" id="_wcmp_required_product" style="width: 100%; margin-top: 5px;">
				<option value=""><?php esc_html_e( 'Any membership', 'wc-membership-product' ); ?></option>
				<?php
				$membership_products = $this->get_membership_products();
				foreach ( $membership_products as $product ) {
					printf(
						'<option value="%d" %s>%s</option>',
						esc_attr( $product->get_id() ),
						selected( $product_id, $product->get_id(), false ),
						esc_html( $product->get_name() )
					);
				}
				?>
			</select>
		</p>
		<p class="description">
			<?php esc_html_e( 'Optionally require a specific membership product.', 'wc-membership-product' ); ?>
		</p>
		<?php
	}

	/**
	 * Saves the restriction meta.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 * @return void
	 */
	public function save_restriction_meta( $post_id, $post ) {
		// Verify nonce.
		if ( ! isset( $_POST['wcmp_restriction_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcmp_restriction_nonce'] ) ), 'wcmp_restriction_meta' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save restriction setting.
		$is_restricted = isset( $_POST['_wcmp_is_restricted'] ) ? '1' : '';
		update_post_meta( $post_id, '_wcmp_is_restricted', $is_restricted );

		// Save required product.
		$product_id = isset( $_POST['_wcmp_required_product'] ) ? absint( $_POST['_wcmp_required_product'] ) : '';
		update_post_meta( $post_id, '_wcmp_required_product', $product_id );
	}

	/**
	 * Filters the content for restricted posts/pages.
	 *
	 * @param string $content The post content.
	 * @return string
	 */
	public function filter_restricted_content( $content ) {
		// Only filter on single posts/pages in main query.
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id       = get_the_ID();
		$is_restricted = get_post_meta( $post_id, '_wcmp_is_restricted', true );

		if ( '1' !== $is_restricted ) {
			return $content;
		}

		$product_id = get_post_meta( $post_id, '_wcmp_required_product', true );
		$product_id = $product_id ? absint( $product_id ) : null;

		$has_access = $this->access_checker->has_access( 0, $post_id, $product_id );

		if ( $has_access ) {
			return $content;
		}

		return $this->render_restricted_message( $this->get_default_restricted_message() );
	}

	/**
	 * Gets the default restricted content message.
	 *
	 * @return string
	 */
	private function get_default_restricted_message() {
		$message = __( 'This content is for members only. Please purchase a membership to access.', 'wc-membership-product' );

		/**
		 * Filters the default restricted content message.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message The default message.
		 */
		return apply_filters( 'wcmp_restricted_message', $message );
	}

	/**
	 * Renders the restricted message HTML.
	 *
	 * @param string $message The message to display.
	 * @return string
	 */
	private function render_restricted_message( $message ) {
		$shop_url = wc_get_page_permalink( 'shop' );

		ob_start();
		?>
		<div class="wcmp-restricted-content">
			<div class="wcmp-restricted-message">
				<span class="wcmp-lock-icon">&#128274;</span>
				<p><?php echo esc_html( $message ); ?></p>
				<?php if ( ! is_user_logged_in() ) : ?>
					<p>
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="button">
							<?php esc_html_e( 'Log In', 'wc-membership-product' ); ?>
						</a>
					</p>
				<?php else : ?>
					<p>
						<a href="<?php echo esc_url( $shop_url ); ?>" class="button">
							<?php esc_html_e( 'View Memberships', 'wc-membership-product' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<style>
			.wcmp-restricted-content {
				background: #f8f8f8;
				border: 1px solid #e0e0e0;
				border-radius: 8px;
				padding: 40px 20px;
				text-align: center;
				margin: 20px 0;
			}
			.wcmp-lock-icon {
				font-size: 48px;
				display: block;
				margin-bottom: 15px;
			}
			.wcmp-restricted-message p {
				margin: 10px 0;
				color: #666;
			}
			.wcmp-restricted-message .button {
				margin-top: 10px;
			}
		</style>
		<?php
		return ob_get_clean();
	}

	/**
	 * Gets all membership products.
	 *
	 * @return array Array of WC_Product objects.
	 */
	private function get_membership_products() {
		$args = array(
			'type'   => 'membership',
			'status' => 'publish',
			'limit'  => -1,
		);

		return wc_get_products( $args );
	}
}
