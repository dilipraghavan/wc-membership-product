<?php
/**
 * Admin functionality for Membership product type.
 *
 * @package WpShiftStudio\WCMembershipProduct\Admin
 */

namespace WpShiftStudio\WCMembershipProduct\Admin;

/**
 * Handles admin UI for Membership products.
 *
 * @since 1.0.0
 */
class ProductAdmin {

	/**
	 * Registers hooks for product admin.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		// Register product type class.
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'product_class' ), 10, 2 );

		// Add product type to selector.
		add_filter( 'product_type_selector', array( __CLASS__, 'add_product_type' ) );

		// Add product data tab.
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_product_tab' ) );

		// Add product data panel.
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'add_product_panel' ) );

		// Save product meta.
		add_action( 'woocommerce_process_product_meta_membership', array( __CLASS__, 'save_product_meta' ) );

		// Show/hide product data tabs for membership type.
		add_action( 'admin_footer', array( __CLASS__, 'product_type_js' ) );

		// Add price input to general tab for membership products.
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_general_fields' ) );
	}

	/**
	 * Returns the custom product class for membership products.
	 *
	 * @param string $classname Current class name.
	 * @param string $product_type Product type.
	 * @return string
	 */
	public static function product_class( $classname, $product_type ) {
		if ( 'membership' === $product_type ) {
			return 'WpShiftStudio\WCMembershipProduct\Product\MembershipProduct';
		}
		return $classname;
	}

	/**
	 * Adds Membership to the product type selector dropdown.
	 *
	 * @param array $types Existing product types.
	 * @return array
	 */
	public static function add_product_type( $types ) {
		$types['membership'] = __( 'Membership', 'wc-membership-product' );
		return $types;
	}

	/**
	 * Adds the Membership tab to product data tabs.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public static function add_product_tab( $tabs ) {
		$tabs['membership'] = array(
			'label'    => __( 'Membership', 'wc-membership-product' ),
			'target'   => 'membership_product_data',
			'class'    => array( 'show_if_membership' ),
			'priority' => 21,
		);
		return $tabs;
	}

	/**
	 * Renders the Membership product data panel.
	 *
	 * @return void
	 */
	public static function add_product_panel() {
		global $post;

		$product = wc_get_product( $post->ID );

		$duration      = '';
		$duration_unit = 'days';
		$tier          = 'standard';

		if ( $product && 'membership' === $product->get_type() ) {
			$duration      = $product->get_membership_duration( 'edit' );
			$duration_unit = $product->get_membership_duration_unit( 'edit' );
			$tier          = $product->get_membership_tier( 'edit' );
		}

		?>
		<div id="membership_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<p class="form-field">
					<label for="_wcmp_membership_duration">
						<?php esc_html_e( 'Duration', 'wc-membership-product' ); ?>
					</label>
					<input 
						type="number" 
						id="_wcmp_membership_duration" 
						name="_wcmp_membership_duration" 
						value="<?php echo esc_attr( $duration ); ?>" 
						min="1" 
						step="1" 
						style="width: 80px;"
					/>
					<select 
						id="_wcmp_membership_duration_unit" 
						name="_wcmp_membership_duration_unit"
						style="width: auto;"
					>
						<option value="days" <?php selected( $duration_unit, 'days' ); ?>>
							<?php esc_html_e( 'Days', 'wc-membership-product' ); ?>
						</option>
						<option value="weeks" <?php selected( $duration_unit, 'weeks' ); ?>>
							<?php esc_html_e( 'Weeks', 'wc-membership-product' ); ?>
						</option>
						<option value="months" <?php selected( $duration_unit, 'months' ); ?>>
							<?php esc_html_e( 'Months', 'wc-membership-product' ); ?>
						</option>
						<option value="years" <?php selected( $duration_unit, 'years' ); ?>>
							<?php esc_html_e( 'Years', 'wc-membership-product' ); ?>
						</option>
					</select>
					<?php
					echo wc_help_tip( __( 'How long the membership will be active after purchase.', 'wc-membership-product' ) );
					?>
				</p>
			</div>

			<div class="options_group">
				<?php
				woocommerce_wp_select(
					array(
						'id'          => '_wcmp_membership_tier',
						'label'       => __( 'Membership Tier', 'wc-membership-product' ),
						'description' => __( 'The tier/level of this membership.', 'wc-membership-product' ),
						'desc_tip'    => true,
						'value'       => $tier,
						'options'     => array(
							'standard' => __( 'Standard', 'wc-membership-product' ),
							'premium'  => __( 'Premium', 'wc-membership-product' ),
							'vip'      => __( 'VIP', 'wc-membership-product' ),
						),
					)
				);
				?>
			</div>

			<div class="options_group">
				<p class="form-field">
					<span class="description">
						<?php
						esc_html_e(
							'Membership products are automatically virtual and sold individually.',
							'wc-membership-product'
						);
						?>
					</span>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Shows price field for membership products in the General tab.
	 *
	 * @return void
	 */
	public static function add_general_fields() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Show pricing fields for membership products.
				$('.product_data_tabs .general_tab').addClass('show_if_membership');
				$('#general_product_data .pricing').addClass('show_if_membership');
			});
		</script>
		<?php
	}

	/**
	 * Saves the membership product meta.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function save_product_meta( $post_id ) {
		$product = wc_get_product( $post_id );

		if ( ! $product || 'membership' !== $product->get_type() ) {
			return;
		}

		// Duration.
		if ( isset( $_POST['_wcmp_membership_duration'] ) ) {
			$duration = absint( wp_unslash( $_POST['_wcmp_membership_duration'] ) );
			$product->set_membership_duration( $duration > 0 ? $duration : 1 );
		}

		// Duration unit.
		if ( isset( $_POST['_wcmp_membership_duration_unit'] ) ) {
			$unit = sanitize_text_field( wp_unslash( $_POST['_wcmp_membership_duration_unit'] ) );
			$product->set_membership_duration_unit( $unit );
		}

		// Tier.
		if ( isset( $_POST['_wcmp_membership_tier'] ) ) {
			$tier = sanitize_text_field( wp_unslash( $_POST['_wcmp_membership_tier'] ) );
			$product->set_membership_tier( $tier );
		}

		// Ensure virtual and sold individually.
		$product->set_virtual( true );
		$product->set_sold_individually( true );

		$product->save();
	}

	/**
	 * JavaScript to show/hide tabs for membership product type.
	 *
	 * @return void
	 */
	public static function product_type_js() {
		global $post;

		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Handle product type change.
				$('select#product-type').on('change', function() {
					var productType = $(this).val();
					
					if (productType === 'membership') {
						// Show membership-specific elements.
						$('.show_if_membership').show();
						
						// Hide irrelevant tabs.
						$('.show_if_simple:not(.show_if_membership)').hide();
						$('.inventory_options').hide();
						$('.shipping_options').hide();
						$('.linked_product_options').show();
						$('.attribute_options').hide();
						
						// Show general tab for pricing.
						$('.general_options').show();
					}
				}).trigger('change');

				// On page load, if membership type, trigger visibility.
				if ($('select#product-type').val() === 'membership') {
					$('select#product-type').trigger('change');
				}
			});
		</script>
		<?php
	}
}
