<?php
declare( strict_types=1 );

namespace DevChefPress\Services;

/**
 * Notifications Service
 * 
 * Handles in-app and email notifications for subscriptions
 */
class NotificationService {

	/**
	 * Send user notification for unpaid order edit
	 * 
	 * @param int $user_id
	 * @param int $order_id
	 * @param float $price_difference
	 */
	public static function notify_edit_unpaid( int $user_id, int $order_id, float $price_difference ): void {
		if ( $price_difference === 0.0 ) {
			$message = 'Your meal plan has been updated successfully. No additional payment is required.';
		} elseif ( $price_difference > 0 ) {
			$message = sprintf(
				'Your meal plan has been updated. Additional amount of $%.2f is required at checkout.',
				$price_difference
			);
		} else {
			$message = sprintf(
				'Your meal plan has been updated. New amount is $%.2f.',
				abs( $price_difference )
			);
		}

		self::add_admin_notice( $user_id, 'info', $message );
	}

	/**
	 * Send user notification for paid order with price increase
	 * 
	 * @param int $user_id
	 * @param int $adjustment_order_id
	 * @param float $adjustment_amount
	 */
	public static function notify_price_increase( int $user_id, int $adjustment_order_id, float $adjustment_amount ): void {
		$order = wc_get_order( $adjustment_order_id );
		$checkout_url = $order ? $order->get_checkout_payment_url() : '';

		$message = sprintf(
			'Your meal plan changes require an additional payment of $%.2f. <a href="%s" class="button">Complete Payment</a>',
			$adjustment_amount,
			esc_url( $checkout_url )
		);

		self::add_admin_notice( $user_id, 'warning', $message );
	}

	/**
	 * Send user notification for refund pending
	 * 
	 * @param int $user_id
	 * @param float $refund_amount
	 * @param int $remaining_days
	 */
	public static function notify_refund_pending( int $user_id, float $refund_amount, int $remaining_days ): void {
		$message = sprintf(
			'Your meal plan has been updated. A refund of $%.2f is pending based on %d remaining days. This will be processed within 5-7 business days.',
			$refund_amount,
			$remaining_days
		);

		self::add_admin_notice( $user_id, 'success', $message );
	}

	/**
	 * Send admin notification
	 * 
	 * @param int $order_id
	 * @param string $notification_type 'price_increase' | 'refund_required'
	 */
	public static function notify_admin( int $order_id, string $notification_type ): void {
		$admin_email = get_option( 'admin_email' );
		$order = wc_get_order( $order_id );

		if ( ! $order || ! $admin_email ) {
			return;
		}

		$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$customer_email = $order->get_billing_email();
		$order_url = admin_url( "post.php?post=$order_id&action=edit" );

		// Get subscription ID and build subscription details URL
		$subscription_id = (int) $order->get_meta( '_subscription_id' );
		$subscription_url = '';
		if ( $subscription_id > 0 && class_exists( '\DevChefPress\Admin\SubscriptionsAdminPage' ) ) {
			$subscription_url = \DevChefPress\Admin\SubscriptionsAdminPage::subscription_details_url( $subscription_id );
		}

		if ( 'price_increase' === $notification_type ) {
			$subject = sprintf( 'Meal Plan Updated: Additional Payment Required (Order #%d)', $order_id );
			$message = sprintf(
				'A customer has updated their meal plan subscription and an additional payment is required.' . "\n\n" .
				'Customer: %s' . "\n" .
				'Email: %s' . "\n" .
				'Order: %s',
				esc_html( $customer_name ),
				esc_html( $customer_email ),
				$order_url
			);
			if ( $subscription_url ) {
				$message .= sprintf( "\n" . 'Subscription Details: %s', $subscription_url );
			}
		} elseif ( 'refund_required' === $notification_type ) {
			$subject = sprintf( 'Meal Plan Updated: Refund Processing Required (Order #%d)', $order_id );
			$message = sprintf(
				'A customer has updated their meal plan subscription. A refund needs to be processed.' . "\n\n" .
				'Customer: %s' . "\n" .
				'Email: %s' . "\n" .
				'Order: %s',
				esc_html( $customer_name ),
				esc_html( $customer_email ),
				$order_url
			);
			if ( $subscription_url ) {
				$message .= sprintf( "\n" . 'Subscription Details: %s', $subscription_url );
			}
		} else {
			return;
		}

		wp_mail( $admin_email, $subject, wp_kses_post( $message ) );
	}

	/**
	 * Add admin notice (stored in user meta)
	 * 
	 * @param int $user_id
	 * @param string $type 'success' | 'error' | 'warning' | 'info'
	 * @param string $message
	 */
	private static function add_admin_notice( int $user_id, string $type, string $message ): void {
		$notices = (array) get_user_meta( $user_id, 'devchefpress_notices', true );

		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		$notices[] = array(
			'type' => sanitize_text_field( $type ),
			'message' => wp_kses_post( $message ),
			'time' => current_time( 'timestamp' ),
		);

		// Keep only last 10 notices
		$notices = array_slice( $notices, -10 );

		update_user_meta( $user_id, 'devchefpress_notices', $notices );
	}

	/**
	 * Get user notices (and optionally clear them)
	 * 
	 * @param int $user_id
	 * @param bool $clear Clear after retrieving
	 * @return array
	 */
	public static function get_user_notices( int $user_id, bool $clear = false ): array {
		$notices = (array) get_user_meta( $user_id, 'devchefpress_notices', true );

		if ( $clear && $notices ) {
			delete_user_meta( $user_id, 'devchefpress_notices' );
		}

		return $notices ?: array();
	}

	/**
	 * Send order confirmation email
	 * 
	 * @param \WC_Order $order
	 * @param bool $is_edit Whether this is an edit confirmation
	 */
	public static function send_order_email( $order, bool $is_edit = false ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// WooCommerce handles this, but we could customize if needed
		if ( $is_edit ) {
			do_action( 'woocommerce_order_status_changed', $order->get_id(), 'pending', $order->get_status(), $order );
		}
	}
}
