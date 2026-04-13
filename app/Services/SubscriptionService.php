<?php
declare( strict_types=1 );

namespace DevChefPress\Services;

/**
 * Class SubscriptionService
 *
 * Handles user meal plan subscriptions display and management.
 */
class SubscriptionService {

	/**
	 * Get all subscriptions for the current user.
	 *
	 * @return array Array of subscription data
	 */
	public static function get_user_subscriptions(): array {
		if ( ! is_user_logged_in() ) {
			return [];
		}

		$user_id = get_current_user_id();

		// Query orders with meal plan data
		$orders = wc_get_orders( [
			'customer_id' => $user_id,
			'meta_key'    => '_meal_plan_state',
			'meta_compare' => 'EXISTS',
			'limit'       => -1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		] );

		$subscriptions = [];

		foreach ( $orders as $order ) {
			$state = $order->get_meta( '_meal_plan_state' );
			$pricing = $order->get_meta( '_meal_plan_pricing' );

			if ( empty( $state ) ) {
				continue;
			}

			$state_data = is_array( $state ) ? $state : json_decode( (string) $state, true );
			$pricing_data = is_array( $pricing ) ? $pricing : json_decode( (string) $pricing, true );

			if ( ! $state_data ) {
				continue;
			}

			$subscription = self::format_subscription_data( $order, $state_data, $pricing_data );
			if ( $subscription ) {
				$subscriptions[] = $subscription;
			}
		}

		return $subscriptions;
	}

	/**
	 * Format subscription data from order and meal plan state.
	 *
	 * @param \WC_Order $order The WooCommerce order
	 * @param array $state_data Meal plan state data
	 * @param array|null $pricing_data Pricing data
	 * @return array|null Formatted subscription data or null if invalid
	 */
	private static function format_subscription_data( \WC_Order $order, array $state_data, ?array $pricing_data ): ?array {
		$order_id = $order->get_id();
		$start_date = $state_data['startDate'] ?? '';
		$plan_duration = $state_data['planDuration'] ?? '';
		$frequency = $state_data['planDuration'] ?? '1 Month'; // Default fallback

		if ( empty( $start_date ) ) {
			return null;
		}

		// Determine plan name
		$plan_name = self::get_plan_name( $state_data );

		// Determine status
		$status = self::determine_subscription_status( $start_date, $plan_duration );

		// Calculate next delivery
		$next_delivery = self::calculate_next_delivery( $start_date, $frequency, $status );

		// Get price
		$price = self::get_subscription_price( $pricing_data, $order );

		return [
			'id'             => $order_id,
			'plan_name'      => $plan_name,
			'status'         => $status,
			'start_date'     => $start_date,
			'next_delivery'  => $next_delivery,
			'frequency'      => $frequency,
			'price'          => $price,
			'primary_action' => self::get_primary_action_text( $status ),
		];
	}

	/**
	 * Get plan name from state data.
	 *
	 * @param array $state_data
	 * @return string
	 */
	private static function get_plan_name( array $state_data ): string {
		$duration = $state_data['planDuration'] ?? '';
		$diet_type = $state_data['dietType'] ?? '';

		if ( $duration && $diet_type ) {
			return $duration . ' ' . str_replace( '+', ' ', $diet_type ) . ' Plan';
		} elseif ( $duration ) {
			return $duration . ' Plan';
		} elseif ( $diet_type ) {
			return str_replace( '+', ' ', $diet_type ) . ' Plan';
		}

		return 'Meal Plan';
	}

	/**
	 * Determine subscription status based on dates.
	 *
	 * @param string $start_date
	 * @param string $plan_duration
	 * @return string
	 */
	private static function determine_subscription_status( string $start_date, string $plan_duration ): string {
		$current_date = current_time( 'Y-m-d' );
		$end_date = self::calculate_end_date( $start_date, $plan_duration );

		if ( $current_date > $end_date ) {
			return 'expired';
		}

		return 'active';
	}

	/**
	 * Calculate end date based on start date and duration.
	 *
	 * @param string $start_date
	 * @param string $duration
	 * @return string
	 */
	private static function calculate_end_date( string $start_date, string $duration ): string {
		$timestamp = strtotime( $start_date );

		if ( strpos( $duration, 'Month' ) !== false ) {
			$months = (int) filter_var( $duration, FILTER_SANITIZE_NUMBER_INT );
			$timestamp = strtotime( "+{$months} months", $timestamp );
		} elseif ( strpos( $duration, 'Week' ) !== false ) {
			$weeks = (int) filter_var( $duration, FILTER_SANITIZE_NUMBER_INT );
			$timestamp = strtotime( "+{$weeks} weeks", $timestamp );
		} elseif ( strpos( $duration, 'Day' ) !== false ) {
			$days = (int) filter_var( $duration, FILTER_SANITIZE_NUMBER_INT );
			$timestamp = strtotime( "+{$days} days", $timestamp );
		}

		return date( 'Y-m-d', $timestamp );
	}

	/**
	 * Calculate next delivery date.
	 *
	 * @param string $start_date
	 * @param string $frequency
	 * @param string $status
	 * @return string
	 */
	private static function calculate_next_delivery( string $start_date, string $frequency, string $status ): string {
		if ( $status === 'expired' ) {
			return self::calculate_end_date( $start_date, $frequency );
		}

		$current_date = current_time( 'Y-m-d' );
		$timestamp = strtotime( $start_date );

		// For simplicity, assume next delivery is based on frequency from start
		// In a real implementation, you'd track last delivery
		if ( strpos( $frequency, 'Month' ) !== false ) {
			$months = (int) filter_var( $frequency, FILTER_SANITIZE_NUMBER_INT );
			while ( date( 'Y-m-d', $timestamp ) <= $current_date ) {
				$timestamp = strtotime( "+{$months} months", $timestamp );
			}
		} elseif ( strpos( $frequency, 'Week' ) !== false ) {
			$weeks = (int) filter_var( $frequency, FILTER_SANITIZE_NUMBER_INT );
			while ( date( 'Y-m-d', $timestamp ) <= $current_date ) {
				$timestamp = strtotime( "+{$weeks} weeks", $timestamp );
			}
		}

		return date( 'Y-m-d', $timestamp );
	}

	/**
	 * Get subscription price.
	 *
	 * @param array|null $pricing_data
	 * @param \WC_Order $order
	 * @return string
	 */
	private static function get_subscription_price( ?array $pricing_data, \WC_Order $order ): string {
		if ( $pricing_data && isset( $pricing_data['planDiscount'] ) && $pricing_data['planDiscount'] > 0 ) {
			return 'AED ' . number_format( $pricing_data['planDiscount'], 0 );
		}

		$subtotal = $order->get_subtotal();
		return 'AED ' . number_format( $subtotal, 0 );
	}

	/**
	 * Get sample subscription data for UI testing.
	 *
	 * @return array
	 */
	private static function get_sample_subscriptions(): array {
		return [
			[
				'id'               => 1,
				'plan_name'        => 'Premium Plan',
				'status'           => 'active', // active | paused | expired
				'start_date'       => '2026-01-15',
				'next_delivery'    => '2026-04-21',
				'frequency'        => '1 Month',
				'price'            => 'AED 450/mo',
				'primary_action'   => 'Book Current Week',
			],
			[
				'id'               => 2,
				'plan_name'        => 'Standard Plan',
				'status'           => 'paused',
				'start_date'       => '2026-02-01',
				'next_delivery'    => '2026-04-10',
				'frequency'        => '2 Weeks',
				'price'            => 'AED 280/biweek',
				'primary_action'   => 'Resume',
			],
			[
				'id'               => 3,
				'plan_name'        => 'Basic Plan',
				'status'           => 'expired',
				'start_date'       => '2025-12-15',
				'next_delivery'    => '2026-03-15',
				'frequency'        => '3 Months',
				'price'            => 'AED 360',
				'primary_action'   => 'Renew Subscription',
			],
		];
	}

	/**
	 * Get status badge class based on subscription status.
	 *
	 * @param string $status Subscription status
	 * @return string CSS class for the badge
	 */
	public static function get_status_class( string $status ): string {
		switch ( $status ) {
			case 'active':
				return 'dev_chefpress_subscription_status_active';
			case 'paused':
				return 'dev_chefpress_subscription_status_paused';
			case 'expired':
				return 'dev_chefpress_subscription_status_expired';
			default:
				return 'dev_chefpress_subscription_status_active';
		}
	}

	/**
	 * Get status badge icon based on subscription status.
	 *
	 * @param string $status Subscription status
	 * @return string Lucide icon name
	 */
	public static function get_status_icon( string $status ): string {
		switch ( $status ) {
			case 'active':
				return 'check-circle';
			case 'paused':
				return 'pause-circle';
			case 'expired':
				return 'alert-circle';
			default:
				return 'check-circle';
		}
	}

	/**
	 * Get status badge text.
	 *
	 * @param string $status Subscription status
	 * @return string Status text
	 */
	public static function get_status_text( string $status ): string {
		switch ( $status ) {
			case 'active':
				return 'Active';
			case 'paused':
				return 'Paused';
			case 'expired':
				return 'Expired';
			default:
				return 'Active';
		}
	}

	/**
	 * Get primary action button text based on subscription status.
	 *
	 * @param string $status Subscription status
	 * @return string Button text
	 */
	public static function get_primary_action_text( string $status ): string {
		switch ( $status ) {
			case 'active':
				return 'Book Current Week';
			case 'paused':
				return 'Resume';
			case 'expired':
				return 'Renew Subscription';
			default:
				return 'Book Current Week';
		}
	}

	/**
	 * Get primary action button icon based on subscription status.
	 *
	 * @param string $status Subscription status
	 * @return string Lucide icon name
	 */
	public static function get_primary_action_icon( string $status ): string {
		switch ( $status ) {
			case 'active':
				return 'calendar-check';
			case 'paused':
				return 'play-circle';
			case 'expired':
				return 'plus-circle';
			default:
				return 'calendar-check';
		}
	}

	/**
	 * Format a date string to readable format.
	 *
	 * @param string $date_string Date in YYYY-MM-DD format
	 * @return string Formatted date
	 */
	public static function format_date( string $date_string ): string {
		$timestamp = strtotime( $date_string );
		return wp_date( 'M d, Y', $timestamp );
	}
}
