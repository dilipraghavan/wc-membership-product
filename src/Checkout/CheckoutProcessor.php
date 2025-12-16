<?php
/**
 * Processes and saves custom checkout fields for membership products.
 *
 * @package WpShiftStudio\WCMembershipProduct\Checkout
 */

namespace WpShiftStudio\WCMembershipProduct\Checkout;

use WpShiftStudio\WCMembershipProduct\DAL\CheckoutFieldsDAL;

/**
 * Handles saving of custom checkout fields.
 *
 * @since 1.0.0
 */
class CheckoutProcessor {

	/**
	 * Registers hooks for checkout processing.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_membership_fields' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_fields_in_admin' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'display_fields_on_thankyou' ) );
		add_action( 'woocommerce_email_after_order_table', array( __CLASS__, 'display_fields_in_email' ), 10, 1 );
	}

	/**
	 * Saves the membership checkout fields to the database.
	 *
	 * @param \WC_Order $order The order object.
	 * @param array     $data  The posted data.
	 * @return void
	 */
	public static function save_membership_fields( $order, $data ) {
		if ( ! CheckoutFields::cart_has_membership() ) {
			return;
		}

		$product = CheckoutFields::get_membership_product_from_cart();
		$fields  = CheckoutFields::get_fields( $product );

		if ( empty( $fields ) ) {
			return;
		}

		$dal          = new CheckoutFieldsDAL();
		$fields_saved = array();

		foreach ( $fields as $key => $field ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

			// Convert checkbox value to readable format.
			if ( 'checkbox' === $field['type'] ) {
				$value = ! empty( $value ) ? 'Yes' : 'No';
			}

			// Convert select value to label.
			if ( 'select' === $field['type'] && ! empty( $field['options'][ $value ] ) ) {
				$value = $field['options'][ $value ];
			}

			if ( ! empty( $value ) ) {
				$fields_saved[ $key ] = $value;
			}
		}

		if ( ! empty( $fields_saved ) ) {
			// Store order ID in meta for later retrieval.
			$order->update_meta_data( '_wcmp_has_membership_fields', 'yes' );

			// We need to save after order is created, so hook into order created.
			add_action(
				'woocommerce_checkout_order_created',
				function ( $order ) use ( $fields_saved, $dal ) {
					$dal->save_fields( $order->get_id(), $fields_saved );
				}
			);
		}
	}

	/**
	 * Displays the membership fields in the admin order page.
	 *
	 * @param \WC_Order $order The order object.
	 * @return void
	 */
	public static function display_fields_in_admin( $order ) {
		$has_fields = $order->get_meta( '_wcmp_has_membership_fields' );

		if ( 'yes' !== $has_fields ) {
			return;
		}

		$dal    = new CheckoutFieldsDAL();
		$fields = $dal->get_fields_by_order_id( $order->get_id() );

		if ( empty( $fields ) ) {
			return;
		}

		$field_config = CheckoutFields::get_fields();

		echo '<div class="wcmp-membership-fields-admin">';
		echo '<h3>' . esc_html__( 'Membership Information', 'wc-membership-product' ) . '</h3>';

		foreach ( $fields as $key => $value ) {
			$label = isset( $field_config[ $key ]['label'] ) ? $field_config[ $key ]['label'] : $key;
			echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Displays the membership fields on the thank you page.
	 *
	 * @param \WC_Order $order The order object.
	 * @return void
	 */
	public static function display_fields_on_thankyou( $order ) {
		$has_fields = $order->get_meta( '_wcmp_has_membership_fields' );

		if ( 'yes' !== $has_fields ) {
			return;
		}

		$dal    = new CheckoutFieldsDAL();
		$fields = $dal->get_fields_by_order_id( $order->get_id() );

		if ( empty( $fields ) ) {
			return;
		}

		$field_config = CheckoutFields::get_fields();

		echo '<section class="woocommerce-membership-details">';
		echo '<h2>' . esc_html__( 'Membership Information', 'wc-membership-product' ) . '</h2>';
		echo '<table class="woocommerce-table shop_table">';
		echo '<tbody>';

		foreach ( $fields as $key => $value ) {
			$label = isset( $field_config[ $key ]['label'] ) ? $field_config[ $key ]['label'] : $key;
			echo '<tr>';
			echo '<th>' . esc_html( $label ) . '</th>';
			echo '<td>' . esc_html( $value ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</section>';
	}

	/**
	 * Displays the membership fields in order emails.
	 *
	 * @param \WC_Order $order The order object.
	 * @return void
	 */
	public static function display_fields_in_email( $order ) {
		$has_fields = $order->get_meta( '_wcmp_has_membership_fields' );

		if ( 'yes' !== $has_fields ) {
			return;
		}

		$dal    = new CheckoutFieldsDAL();
		$fields = $dal->get_fields_by_order_id( $order->get_id() );

		if ( empty( $fields ) ) {
			return;
		}

		$field_config = CheckoutFields::get_fields();

		echo '<h2>' . esc_html__( 'Membership Information', 'wc-membership-product' ) . '</h2>';
		echo '<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; margin-bottom: 20px;">';

		foreach ( $fields as $key => $value ) {
			$label = isset( $field_config[ $key ]['label'] ) ? $field_config[ $key ]['label'] : $key;
			echo '<tr>';
			echo '<th style="text-align: left;">' . esc_html( $label ) . '</th>';
			echo '<td>' . esc_html( $value ) . '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}
}
