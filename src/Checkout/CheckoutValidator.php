<?php
/**
 * Validates custom checkout fields for membership products.
 *
 * @package WpShiftStudio\WCMembershipProduct\Checkout
 */

namespace WpShiftStudio\WCMembershipProduct\Checkout;

/**
 * Handles validation of custom checkout fields.
 *
 * @since 1.0.0
 */
class CheckoutValidator {

	/**
	 * Registers hooks for checkout validation.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_membership_fields' ) );
	}

	/**
	 * Validates the membership checkout fields.
	 *
	 * @return void
	 */
	public static function validate_membership_fields() {
		if ( ! CheckoutFields::cart_has_membership() ) {
			return;
		}

		$product = CheckoutFields::get_membership_product_from_cart();
		$fields  = CheckoutFields::get_fields( $product );

		foreach ( $fields as $key => $field ) {
			if ( empty( $field['required'] ) ) {
				continue;
			}

			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';

			if ( empty( $value ) ) {
				$label = isset( $field['label'] ) ? $field['label'] : $key;
				wc_add_notice(
					sprintf(
						/* translators: %s: field label */
						__( '%s is a required field.', 'wc-membership-product' ),
						'<strong>' . esc_html( $label ) . '</strong>'
					),
					'error'
				);
			}
		}
	}
}
