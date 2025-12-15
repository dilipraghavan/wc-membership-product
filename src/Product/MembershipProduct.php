<?php
/**
 * Custom Membership product type for WooCommerce.
 *
 * @package WpShiftStudio\WCMembershipProduct\Product
 */

namespace WpShiftStudio\WCMembershipProduct\Product;

/**
 * Membership Product class extending WC_Product.
 *
 * @since 1.0.0
 */
class MembershipProduct extends \WC_Product {

	/**
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'membership';

	/**
	 * Constructor.
	 *
	 * @param int|WC_Product|object $product Product ID, post object, or product object.
	 */
	public function __construct( $product = 0 ) {
		parent::__construct( $product );
	}

	/**
	 * Get the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'membership';
	}

	/**
	 * Membership products are virtual by default.
	 *
	 * @return bool
	 */
	public function is_virtual() {
		return true;
	}

	/**
	 * Membership products are not shippable.
	 *
	 * @return bool
	 */
	public function needs_shipping() {
		return false;
	}

	/**
	 * Membership products are sold individually by default.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_sold_individually( $context = 'view' ) {
		return true;
	}

	/**
	 * Get the membership duration value.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_membership_duration( $context = 'view' ) {
		return (int) $this->get_meta( '_wcmp_membership_duration', true, $context );
	}

	/**
	 * Get the membership duration unit.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_membership_duration_unit( $context = 'view' ) {
		$unit = $this->get_meta( '_wcmp_membership_duration_unit', true, $context );
		return $unit ? $unit : 'days';
	}

	/**
	 * Get the membership tier.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_membership_tier( $context = 'view' ) {
		$tier = $this->get_meta( '_wcmp_membership_tier', true, $context );
		return $tier ? $tier : 'standard';
	}

	/**
	 * Set the membership duration value.
	 *
	 * @param int $value Duration value.
	 */
	public function set_membership_duration( $value ) {
		$this->update_meta_data( '_wcmp_membership_duration', absint( $value ) );
	}

	/**
	 * Set the membership duration unit.
	 *
	 * @param string $value Duration unit (days, weeks, months, years).
	 */
	public function set_membership_duration_unit( $value ) {
		$allowed = array( 'days', 'weeks', 'months', 'years' );
		if ( in_array( $value, $allowed, true ) ) {
			$this->update_meta_data( '_wcmp_membership_duration_unit', $value );
		}
	}

	/**
	 * Set the membership tier.
	 *
	 * @param string $value Tier name.
	 */
	public function set_membership_tier( $value ) {
		$this->update_meta_data( '_wcmp_membership_tier', sanitize_text_field( $value ) );
	}

	/**
	 * Calculate the expiration date based on duration settings.
	 *
	 * @param string $start_date Optional start date in Y-m-d H:i:s format. Defaults to now.
	 * @return string Expiration date in Y-m-d H:i:s format.
	 */
	public function calculate_expiration_date( $start_date = '' ) {
		if ( empty( $start_date ) ) {
			$start_date = current_time( 'mysql' );
		}

		$duration = $this->get_membership_duration();
		$unit     = $this->get_membership_duration_unit();

		if ( $duration <= 0 ) {
			$duration = 1;
		}

		$timestamp  = strtotime( $start_date );
		$expiration = strtotime( "+{$duration} {$unit}", $timestamp );

		return gmdate( 'Y-m-d H:i:s', $expiration );
	}

	/**
	 * Membership products are always in stock.
	 *
	 * @param string $context What the value is for.
	 * @return string
	 */
	public function get_stock_status( $context = 'view' ) {
		return 'instock';
	}

	/**
	 * Membership products are always purchasable.
	 *
	 * @return bool
	 */
	public function is_in_stock() {
		return true;
	}

	/**
	 * Membership products are purchasable if they have a price.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		return $this->exists() && $this->get_price() !== '';
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		return __( 'Join Now', 'wc-membership-product' );
	}

	/**
	 * Get the add to cart URL.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		return $this->is_purchasable() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->get_id() ) ) : get_permalink( $this->get_id() );
	}

	/**
	 * Supports AJAX add to cart.
	 *
	 * @return bool
	 */
	public function supports( $feature ) {
		if ( 'ajax_add_to_cart' === $feature ) {
			return true;
		}
		return parent::supports( $feature );
	}
}
