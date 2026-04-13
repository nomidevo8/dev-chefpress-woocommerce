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

		// TODO: Query subscriptions from database once backend is ready
		// For now, return sample data for UI testing
		return self::get_sample_subscriptions();
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
