<?php
/**
 * Renders custom checkout fields for membership products.
 *
 * @package WpShiftStudio\WCMembershipProduct\Checkout
 */

namespace WpShiftStudio\WCMembershipProduct\Checkout;

/**
 * Handles rendering of custom checkout fields.
 *
 * @since 1.0.0
 */
class CheckoutFields {

	/**
	 * Registers hooks for checkout fields.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'render_membership_fields' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Checks if cart contains a membership product.
	 *
	 * @return bool
	 */
	public static function cart_has_membership() {
		if ( ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( $product && 'membership' === $product->get_type() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the membership product from cart.
	 *
	 * @return \WC_Product|null
	 */
	public static function get_membership_product_from_cart() {
		if ( ! WC()->cart ) {
			return null;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( $product && 'membership' === $product->get_type() ) {
				return $product;
			}
		}

		return null;
	}

	/**
	 * Gets the checkout fields configuration.
	 *
	 * @param \WC_Product|null $product The membership product.
	 * @return array
	 */
	public static function get_fields( $product = null ) {
		$fields = array(
			'wcmp_company_name'    => array(
				'type'        => 'text',
				'label'       => __( 'Company Name', 'wc-membership-product' ),
				'placeholder' => __( 'Your company or organization', 'wc-membership-product' ),
				'required'    => false,
				'class'       => array( 'form-row-wide' ),
				'priority'    => 10,
			),
			'wcmp_referral_source' => array(
				'type'     => 'select',
				'label'    => __( 'How did you hear about us?', 'wc-membership-product' ),
				'required' => true,
				'class'    => array( 'form-row-wide' ),
				'priority' => 20,
				'options'  => array(
					''              => __( 'Select an option...', 'wc-membership-product' ),
					'search'        => __( 'Search Engine (Google, Bing, etc.)', 'wc-membership-product' ),
					'social'        => __( 'Social Media', 'wc-membership-product' ),
					'friend'        => __( 'Friend or Colleague', 'wc-membership-product' ),
					'advertisement' => __( 'Advertisement', 'wc-membership-product' ),
					'other'         => __( 'Other', 'wc-membership-product' ),
				),
			),
			'wcmp_agree_terms'     => array(
				'type'     => 'checkbox',
				'label'    => __( 'I agree to the membership terms and conditions', 'wc-membership-product' ),
				'required' => true,
				'class'    => array( 'form-row-wide' ),
				'priority' => 30,
			),
		);

		/**
		 * Filters the checkout fields for membership products.
		 *
		 * @since 1.0.0
		 *
		 * @param array            $fields  The checkout fields.
		 * @param \WC_Product|null $product The membership product.
		 */
		return apply_filters( 'wcmp_checkout_fields', $fields, $product );
	}

	/**
	 * Renders the membership checkout fields.
	 *
	 * @param \WC_Checkout $checkout The checkout object.
	 * @return void
	 */
	public static function render_membership_fields( $checkout ) {
		if ( ! self::cart_has_membership() ) {
			return;
		}

		$product = self::get_membership_product_from_cart();
		$fields  = self::get_fields( $product );

		if ( empty( $fields ) ) {
			return;
		}

		echo '<div id="wcmp-membership-fields" class="wcmp-checkout-fields">';
		echo '<h3>' . esc_html__( 'Membership Information', 'wc-membership-product' ) . '</h3>';

		foreach ( $fields as $key => $field ) {
			$value = '';
			if ( isset( $_POST[ $key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}

			self::render_field( $key, $field, $value );
		}

		echo '</div>';
	}

	/**
	 * Renders a single checkout field.
	 *
	 * @param string $key   The field key.
	 * @param array  $field The field configuration.
	 * @param string $value The current value.
	 * @return void
	 */
	private static function render_field( $key, $field, $value ) {
		$field = wp_parse_args(
			$field,
			array(
				'type'        => 'text',
				'label'       => '',
				'placeholder' => '',
				'required'    => false,
				'class'       => array(),
				'options'     => array(),
			)
		);

		$required_html = $field['required'] ? ' <abbr class="required" title="required">*</abbr>' : '';
		$class_string  = implode( ' ', $field['class'] );

		echo '<p class="form-row ' . esc_attr( $class_string ) . '" id="' . esc_attr( $key ) . '_field">';

		switch ( $field['type'] ) {
			case 'text':
				echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . $required_html . '</label>';
				echo '<span class="woocommerce-input-wrapper">';
				echo '<input type="text" class="input-text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $value ) . '">';
				echo '</span>';
				break;

			case 'select':
				echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . $required_html . '</label>';
				echo '<span class="woocommerce-input-wrapper">';
				echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" class="select">';
				foreach ( $field['options'] as $option_value => $option_label ) {
					$selected = selected( $value, $option_value, false );
					echo '<option value="' . esc_attr( $option_value ) . '"' . $selected . '>' . esc_html( $option_label ) . '</option>';
				}
				echo '</select>';
				echo '</span>';
				break;

			case 'checkbox':
				echo '<label class="checkbox">';
				$checked = checked( $value, '1', false );
				echo '<input type="checkbox" class="input-checkbox" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="1"' . $checked . '> ';
				echo '<span>' . esc_html( $field['label'] ) . $required_html . '</span>';
				echo '</label>';
				break;
		}

		echo '</p>';
	}

	/**
	 * Enqueues checkout scripts.
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		if ( ! is_checkout() ) {
			return;
		}

		wp_add_inline_style(
			'woocommerce-layout',
			'
			.wcmp-checkout-fields {
				background: #f8f8f8;
				padding: 20px;
				margin-bottom: 20px;
				border-radius: 4px;
			}
			.wcmp-checkout-fields h3 {
				margin-top: 0;
				margin-bottom: 15px;
			}
			'
		);
	}
}
