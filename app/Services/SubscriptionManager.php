<?php
declare( strict_types=1 );

namespace DevChefPress\Services;

use DevChefPress\Models\UserSubscription;

/**
 * Subscription Manager Service
 * 
 * Handles subscription edits, billing, refunds, and history tracking
 */
class SubscriptionManager {

	/**
	 * Determine payment status of an order
	 * 
	 * @param \WC_Order $order
	 * @return string 'paid' or 'unpaid'
	 */
	public static function get_payment_status( $order ): string {
		$status = $order->get_status();
		
		// Paid statuses
		if ( in_array( $status, array( 'processing', 'completed' ), true ) ) {
			return 'paid';
		}
		
		// Unpaid statuses
		return 'unpaid';
	}

	/**
	 * Determine if an order has any associated adjustment orders
	 * 
	 * @param int $order_id
	 * @return array Array of adjustment order IDs
	 */
	public static function get_adjustment_orders( int $order_id ): array {
		global $wpdb;
		
		$query = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} 
			WHERE meta_key = '_parent_order_id' AND meta_value = %d
			AND post_id IN (
				SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order'
			)",
			$order_id
		);
		
		$adjustment_order_ids = $wpdb->get_col( $query );
		return array_map( 'intval', $adjustment_order_ids );
	}

	/**
	 * Resolve original order total for pending subscription edits.
	 *
	 * Prefer current_price from the custom subscription table,
	 * then the latest history record new_price if available.
	 *
	 * @param \WC_Order $order
	 * @return float
	 */
	private static function resolve_original_order_total( $order ): float {
		$original_total = 0.0;

		$subscription_id = $order->get_meta( '_subscription_id' );
		$subscription = null;

		if ( $subscription_id ) {
			$subscription = UserSubscription::get_by_id( (int) $subscription_id );
		}

		if ( ! $subscription ) {
			$subscription = UserSubscription::get_by_order_id( (int) $order->get_id() );
		}

		if ( $subscription ) {
			$original_total = $subscription->get_current_price();

			$history = self::get_history( $subscription->get_id() );
			if ( ! empty( $history ) && isset( $history[0]['new_price'] ) && is_numeric( $history[0]['new_price'] ) ) {
				$original_total = (float) $history[0]['new_price'];
			}

			return $original_total;
		}

		$original_pricing = $order->get_meta( '_meal_plan_pricing' );
		if ( is_array( $original_pricing ) ) {
			$original_total = (float) ( $original_pricing['total'] ?? 0 );
		} elseif ( is_string( $original_pricing ) ) {
			$parsed = json_decode( $original_pricing, true );
			$original_total = (float) ( $parsed['total'] ?? $order->get_total() );
		} else {
			$original_total = (float) $order->get_total();
		}

		return $original_total;
	}

	/**
	 * Calculate refund amount based on remaining days
	 * 
	 * @param float $price_difference The absolute difference (positive number)
	 * @param array $delivery_details Delivery details with startDate and duration
	 * @param array $state State array with planDuration
	 * @return array Contains refund_amount and calculation details
	 */
	public static function calculate_smart_refund( float $price_difference, array $delivery_details, array $state ): array {
		$start_date = $delivery_details['startDate'] ?? $state['startDate'] ?? date( 'Y-m-d' );
		$plan_duration = $state['planDuration'] ?? '1 Week';
		
		// Parse duration to days
		$total_days = self::parse_duration_to_days( $plan_duration );
		
		// Calculate remaining days from today
		$start_ts = strtotime( $start_date );
		$end_ts = $start_ts + ( $total_days * 24 * 60 * 60 );
		$today_ts = current_time( 'timestamp' );
		
		// If subscription already ended, no refund
		if ( $today_ts >= $end_ts ) {
			return array(
				'refund_amount' => 0,
				'remaining_days' => 0,
				'total_days' => $total_days,
				'reason' => 'subscription_ended'
			);
		}
		
		$remaining_days = max( 1, (int) ceil( ( $end_ts - $today_ts ) / ( 24 * 60 * 60 ) ) );
		
		// Calculate refund ratio
		$refund_ratio = $remaining_days / $total_days;
		$refund_amount = abs( $price_difference ) * $refund_ratio;
		
		return array(
			'refund_amount' => round( $refund_amount, 2 ),
			'remaining_days' => $remaining_days,
			'total_days' => $total_days,
			'refund_ratio' => round( $refund_ratio, 2 ),
			'reason' => 'pro_rata_refund'
		);
	}

	/**
	 * Parse plan duration string to number of days
	 * 
	 * @param string $duration e.g., '1 Week', '1 Month', '3 Months', '6 Months'
	 * @return int Number of days
	 */
	private static function parse_duration_to_days( string $duration ): int {
		$duration = strtolower( trim( $duration ) );
		
		if ( strpos( $duration, 'week' ) !== false ) {
			$weeks = (int) preg_replace( '/[^0-9]/', '', $duration );
			return max( 1, $weeks ) * 7;
		}
		
		if ( strpos( $duration, 'month' ) !== false ) {
			$months = (int) preg_replace( '/[^0-9]/', '', $duration );
			return max( 1, $months ) * 30; // Approximate
		}
		
		// Default to 7 days
		return 7;
	}

	/**
	 * Handle edit for UNPAID order
	 * 
	 * Updates existing WooCommerce order and subscription record
	 * 
	 * @param int $order_id
	 * @param array $state New state data
	 * @param array $new_pricing New pricing data
	 * @return array|WP_Error Returns array with order_id and price_difference or error
	 */
	public static function handle_unpaid_edit( int $order_id, array $state, array $new_pricing ): array|\WP_Error {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new \WP_Error( 'order_not_found', 'Order not found' );
		}

		// Get original pricing from subscription records or history, then fallback to order meta
		$original_total = self::resolve_original_order_total( $order );
		$new_total = (float) $new_pricing['total'];
		$price_difference = $new_total - $original_total;

		// Remove existing line items
		foreach ( $order->get_items() as $item_id => $item ) {
			$order->remove_item( $item_id );
		}

		// Add new products from slots
		foreach ( $state['slots'] as $slot ) {
			if ( isset( $slot['recipeSelected'] ) && $slot['recipeSelected'] ) {
				$product_id = $slot['recipeSelected']['id'];
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$order->add_product( $product, 1 );
				}
			}
		}

		// Remove any existing meal plan fees
		foreach ( $order->get_items( 'fee' ) as $fee_id => $fee_item ) {
			$fee_name = $fee_item->get_name();
			if ( in_array( $fee_name, array( 'Meal Plan Adjustment', 'Meal Plan Discount', 'Meal Plan Package' ), true ) ) {
				$order->remove_item( $fee_id );
			}
		}

		// Add package pricing as a fee item (source of truth for totals)
		if ( (float) $new_pricing['total'] > 0 ) {
			$fee = new \WC_Order_Item_Fee();
			$fee->set_name( 'Meal Plan Package' );
			$fee->set_amount( (float) $new_pricing['total'] );
			$fee->set_total( (float) $new_pricing['total'] );
			$fee->set_tax_status( 'none' ); // No tax on package fee
			$order->add_item( $fee );
		}

		// Recalculate the order total based on new products and package fee
		$order->calculate_totals( false );

		// Update order meta
		$order->update_meta_data( '_meal_plan_state', $state );
		$order->update_meta_data( '_meal_plan_pricing', $new_pricing );
		$order->update_meta_data( '_original_order_total', $original_total );
		$order->update_meta_data( '_updated_order_total', $new_total );
		$order->update_meta_data( '_price_difference', $price_difference );
		$order->update_meta_data( '_is_edited_order', 'yes' );

		// Update address if provided
		if ( isset( $state['address'] ) ) {
			$address = $state['address'];
			$order->set_billing_address_1( $address['building'] ?? '' );
			$order->set_billing_address_2( ( $address['floor'] ?? '' ) . ' ' . ( $address['flat'] ?? '' ) );
			$order->set_shipping_address_1( $address['building'] ?? '' );
			$order->set_shipping_address_2( ( $address['floor'] ?? '' ) . ' ' . ( $address['flat'] ?? '' ) );
		}

		// Update dates if provided
		if ( isset( $state['startDate'] ) ) {
			$order->update_meta_data( '_delivery_date', $state['startDate'] );
		}

		if ( isset( $state['deliverySlot'] ) ) {
			$order->update_meta_data( '_delivery_slot', $state['deliverySlot'] );
		}

		// Keep order status as pending (no payment received yet)
		$order->set_status( 'pending' );
		$order->save();

		// Update subscription record if it exists
		$subscription_id = $order->get_meta( '_subscription_id' );
		if ( $subscription_id ) {
			$subscription = UserSubscription::get_by_id( (int) $subscription_id );
			if ( $subscription ) {
				$subscription->set_meals_data( $state['slots'] ?? array() )
					->set_delivery_details( $state['address'] ?? array() )
					->set_current_price( $new_total )
					->save();
			}
		}

		// Record history
		if ( $subscription_id ) {
			self::insert_history( array(
				'subscription_id' => (int) $subscription_id,
				'order_id' => $order_id,
				'change_type' => 'edit_unpaid',
				'old_price' => $original_total,
				'new_price' => $new_total,
				'difference' => $price_difference,
				'notes' => 'Order updated before payment'
			) );
		}

		return array(
			'order_id' => $order_id,
			'price_difference' => $price_difference,
			'original_total' => $original_total,
			'new_total' => $new_total
		);
	}

	/**
	 * Handle edit for PAID order with PRICE INCREASE
	 * 
	 * Creates new adjustment order
	 * 
	 * @param int $order_id Original order ID
	 * @param int $subscription_id Subscription ID
	 * @param float $difference Price difference (positive for increase)
	 * @param array $new_pricing New pricing data
	 * @return int|\WP_Error New adjustment order ID or error
	 */
	public static function create_adjustment_order( int $order_id, int $subscription_id, float $difference, array $new_pricing ): int|\WP_Error {
		$original_order = wc_get_order( $order_id );
		if ( ! $original_order ) {
			return new \WP_Error( 'order_not_found', 'Original order not found' );
		}

		$user_id = $original_order->get_customer_id();

		// Create new order
		$adjustment_order = wc_create_order( array(
			'customer_id' => $user_id,
			'status' => 'pending',
		) );

		if ( is_wp_error( $adjustment_order ) ) {
			return $adjustment_order;
		}

		// Add adjustment line item with correct pricing
		$adjustment_amount = abs( $difference );
		$line_item_id = $adjustment_order->add_product(
			self::get_or_create_adjustment_product(),
			1
		);

		// Get the actual line item object and set correct pricing
		if ( $line_item_id ) {
			$line_item = $adjustment_order->get_item( $line_item_id );
			if ( $line_item ) {
				$line_item->set_subtotal( $adjustment_amount );
				$line_item->set_total( $adjustment_amount );
				$line_item->save();
			}
		}

		// Clear any tax/fees and set exact order total
		$adjustment_order->remove_coupon( true );
		$adjustment_order->set_shipping_total( 0 );
		$adjustment_order->set_discount_total( 0 );
		$adjustment_order->set_discount_tax( 0 );
		$adjustment_order->set_cart_tax( 0 );
		$adjustment_order->set_total( $adjustment_amount );

		// Copy billing/shipping from original order
		$original_order_data = $original_order->get_data();
		if ( isset( $original_order_data['billing'] ) ) {
			$adjustment_order->set_billing_address_1( $original_order_data['billing']['address_1'] ?? '' );
			$adjustment_order->set_billing_address_2( $original_order_data['billing']['address_2'] ?? '' );
			$adjustment_order->set_billing_city( $original_order_data['billing']['city'] ?? '' );
			$adjustment_order->set_billing_postcode( $original_order_data['billing']['postcode'] ?? '' );
			$adjustment_order->set_billing_country( $original_order_data['billing']['country'] ?? 'AE' );
		}

		if ( isset( $original_order_data['shipping'] ) ) {
			$adjustment_order->set_shipping_address_1( $original_order_data['shipping']['address_1'] ?? '' );
			$adjustment_order->set_shipping_address_2( $original_order_data['shipping']['address_2'] ?? '' );
			$adjustment_order->set_shipping_city( $original_order_data['shipping']['city'] ?? '' );
			$adjustment_order->set_shipping_postcode( $original_order_data['shipping']['postcode'] ?? '' );
			$adjustment_order->set_shipping_country( $original_order_data['shipping']['country'] ?? 'AE' );
		}

		// Link to original order and subscription
		$adjustment_order->update_meta_data( '_parent_order_id', $order_id );
		$adjustment_order->update_meta_data( '_subscription_id', $subscription_id );
		$adjustment_order->update_meta_data( '_order_type', 'adjustment' );
		$adjustment_order->update_meta_data( '_adjustment_type', 'price_increase' );
		$adjustment_order->update_meta_data( '_adjustment_amount', abs( $difference ) );

		$adjustment_order->save();

		// Record history
		self::insert_history( array(
			'subscription_id' => $subscription_id,
			'order_id' => $order_id,
			'change_type' => 'price_increase',
			'difference' => $difference,
			'adjustment_order_id' => $adjustment_order->get_id(),
			'notes' => 'Adjustment order created for price increase'
		) );

		return $adjustment_order->get_id();
	}

	/**
	 * Handle edit for PAID order with PRICE DECREASE
	 * 
	 * Records pending refund
	 * 
	 * @param int $subscription_id
	 * @param int $order_id
	 * @param float $difference Price difference (negative for decrease)
	 * @param array $delivery_details From subscription
	 * @param array $state Current state
	 * @return array|\WP_Error Contains refund details or error
	 */
	public static function handle_price_decrease( int $subscription_id, int $order_id, float $difference, array $delivery_details, array $state ): array|\WP_Error {
		// Calculate smart refund
		$refund_calc = self::calculate_smart_refund( abs( $difference ), $delivery_details, $state );

		$refund_amount = $refund_calc['refund_amount'];
		$remaining_days = $refund_calc['remaining_days'];
		$total_days = $refund_calc['total_days'];

		// Update subscription
		$subscription = UserSubscription::get_by_id( $subscription_id );
		if ( ! $subscription ) {
			return new \WP_Error( 'subscription_not_found', 'Subscription not found' );
		}

		$subscription->add_refund_pending( $refund_amount )
			->set_current_price( $subscription->get_current_price() + $difference ) // difference is negative
			->save();

		// Record history
		self::insert_history( array(
			'subscription_id' => $subscription_id,
			'order_id' => $order_id,
			'change_type' => 'refund',
			'difference' => $difference,
			'refund_amount' => $refund_amount,
			'remaining_days' => $remaining_days,
			'total_days' => $total_days,
			'refund_status' => 'pending',
			'notes' => 'Pro-rata refund pending approval: ' . $refund_calc['refund_ratio'] * 100 . '% of difference'
		) );

		return array(
			'refund_amount' => $refund_amount,
			'remaining_days' => $remaining_days,
			'total_days' => $total_days,
			'refund_ratio' => $refund_calc['refund_ratio']
		);
	}

	/**
	 * Insert history record
	 * 
	 * @param array $data History data
	 * @return int|\WP_Error History record ID or error
	 */
	public static function insert_history( array $data ): int|\WP_Error {
		global $wpdb;

		$required = array( 'subscription_id', 'order_id', 'change_type' );
		foreach ( $required as $field ) {
			if ( ! isset( $data[$field] ) ) {
				return new \WP_Error( 'missing_field', "Missing required field: $field" );
			}
		}

		$inserted = $wpdb->insert(
			$wpdb->prefix . 'chefpress_subscription_history',
			array(
				'subscription_id' => (int) $data['subscription_id'],
				'order_id' => (int) $data['order_id'],
				'change_type' => sanitize_text_field( $data['change_type'] ),
				'old_price' => isset( $data['old_price'] ) ? (float) $data['old_price'] : 0,
				'new_price' => isset( $data['new_price'] ) ? (float) $data['new_price'] : 0,
				'difference' => isset( $data['difference'] ) ? (float) $data['difference'] : 0,
				'refund_amount' => isset( $data['refund_amount'] ) ? (float) $data['refund_amount'] : 0,
				'adjustment_order_id' => isset( $data['adjustment_order_id'] ) ? (int) $data['adjustment_order_id'] : null,
				'remaining_days' => isset( $data['remaining_days'] ) ? (int) $data['remaining_days'] : null,
				'total_days' => isset( $data['total_days'] ) ? (int) $data['total_days'] : null,
				'refund_status' => isset( $data['refund_status'] ) ? sanitize_text_field( $data['refund_status'] ) : 'pending',
				'notes' => isset( $data['notes'] ) ? wp_kses_post( $data['notes'] ) : '',
			),
			array( '%d', '%d', '%s', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new \WP_Error( 'db_insert_error', 'Failed to insert history record' );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get subscription history
	 * 
	 * @param int $subscription_id
	 * @return array Array of history records
	 */
	public static function get_history( int $subscription_id ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}chefpress_subscription_history 
				WHERE subscription_id = %d 
				ORDER BY created_at DESC",
				$subscription_id
			)
		);

		return $results ? array_map( function( $row ) {
			return (array) $row;
		}, $results ) : array();
	}

	/**
	 * Get or create adjustment product
	 * 
	 * Used for adjustment orders
	 * 
	 * @return WC_Product|false
	 */
	private static function get_or_create_adjustment_product() {
		// Look for existing adjustment product
		$args = array(
			'post_type' => 'product',
			'meta_key' => '_is_adjustment_product',
			'meta_value' => '1',
			'posts_per_page' => 1,
		);

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			$query->the_post();
			return wc_get_product( get_the_ID() );
		}

		// Create new adjustment product
		$product = new \WC_Product_Simple();
		$product->set_name( 'Meal Plan Adjustment' );
		$product->set_description( 'Adjustment charge for meal plan modifications' );
		$product->set_regular_price( 0 );
		$product->set_price( 0 );
		$product->set_status( 'publish' );
		$product->save();

		// Mark as adjustment product
		update_post_meta( $product->get_id(), '_is_adjustment_product', '1' );

		return $product;
	}

	/**
	 * Get main subscription orders (filter out adjustments)
	 * 
	 * @param int $user_id
	 * @return array Array of order IDs
	 */
	public static function get_main_subscription_orders( int $user_id ): array {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} pm1
			JOIN {$wpdb->posts} p ON pm1.post_id = p.ID
			LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_order_type'
			WHERE pm1.meta_key = '_customer_user' AND pm1.meta_value = %d
			AND (pm2.meta_value IS NULL OR pm2.meta_value = 'meal_plan')
			ORDER BY p.post_date DESC",
			$user_id
		);

		$order_ids = $wpdb->get_col( $query );
		return array_map( 'intval', $order_ids ?: array() );
	}
}
