<?php
declare( strict_types=1 );

namespace DevChefPress\Frontend;

use DevChefPress\Services\SubscriptionManager;

/**
 * Subscription Details Helper
 * 
 * Generates HTML for subscription details modal including history
 */
class SubscriptionDetailsHelper {

	/**
	 * Build complete subscription details HTML with history
	 * 
	 * @param array $data Subscription data
	 * @param int $subscription_id
	 * @param int $order_id
	 * @return string HTML
	 */
	public static function build_subscription_details_html( array $data, int $subscription_id, int $order_id ): string {
		$html = self::build_basic_info( $data );
		$html .= self::build_address_section( $data );
		$html .= self::build_meal_plan_section( $data );
		$html .= self::build_pricing_section( $data, $subscription_id );
		$html .= self::build_history_section( $subscription_id, $order_id );
		$html .= self::build_refund_section( $subscription_id, $order_id );
		$html .= self::build_adjustment_orders_section( $order_id );

		return $html;
	}

	/**
	 * Build basic personal information section
	 * 
	 * @param array $data
	 * @return string
	 */
	private static function build_basic_info( array $data ): string {
		$html = '<div class="devchefpress-section">';
		$html .= '<h3 class="devchefpress-section-title">👤 Personal Information</h3>';
		$html .= '<div class="devchefpress-info-grid-modal">';

		if ( isset( $data['goal'] ) && ! empty( $data['goal'] ) ) {
			$html .= '<div class="devchefpress-info-item-modal">';
			$html .= '<span class="devchefpress-info-label">Goal</span>';
			$html .= '<span class="devchefpress-info-value">' . esc_html( str_replace( '+', ' ', $data['goal'] ) ) . '</span>';
			$html .= '</div>';
		}

		if ( isset( $data['age'] ) && $data['age'] ) {
			$html .= '<div class="devchefpress-info-item-modal">';
			$html .= '<span class="devchefpress-info-label">Age</span>';
			$html .= '<span class="devchefpress-info-value">' . esc_html( $data['age'] ) . ' years</span>';
			$html .= '</div>';
		}

		if ( isset( $data['gender'] ) ) {
			$html .= '<div class="devchefpress-info-item-modal">';
			$html .= '<span class="devchefpress-info-label">Gender</span>';
			$html .= '<span class="devchefpress-info-value">' . esc_html( ucfirst( $data['gender'] ) ) . '</span>';
			$html .= '</div>';
		}

		if ( isset( $data['weight'] ) && $data['weight'] ) {
			$html .= '<div class="devchefpress-info-item-modal">';
			$html .= '<span class="devchefpress-info-label">Weight</span>';
			$html .= '<span class="devchefpress-info-value">' . esc_html( $data['weight'] ) . ' kg</span>';
			$html .= '</div>';
		}

		if ( isset( $data['height'] ) && $data['height'] ) {
			$html .= '<div class="devchefpress-info-item-modal">';
			$html .= '<span class="devchefpress-info-label">Height</span>';
			$html .= '<span class="devchefpress-info-value">' . esc_html( $data['height'] ) . ' cm</span>';
			$html .= '</div>';
		}

		if ( isset( $data['activityLevel'] ) && ! empty( $data['activityLevel'] ) ) {
			$html .= '<div class="devchefpress-info-item-modal">';
			$html .= '<span class="devchefpress-info-label">Activity Level</span>';
			$html .= '<span class="devchefpress-info-value">' . esc_html( str_replace( '+', ' ', $data['activityLevel'] ) ) . '</span>';
			$html .= '</div>';
		}

		if ( isset( $data['planDuration'] ) && ! empty( $data['planDuration'] ) ) {
			$html .= '<div class="devchefpress-info-item-modal">';
			$html .= '<span class="devchefpress-info-label">Plan Duration</span>';
			$html .= '<span class="devchefpress-info-value">' . esc_html( $data['planDuration'] ) . '</span>';
			$html .= '</div>';
		}

		if ( isset( $data['startDate'] ) && ! empty( $data['startDate'] ) ) {
			$html .= '<div class="devchefpress-info-item-modal">';
			$html .= '<span class="devchefpress-info-label">Start Date</span>';
			$html .= '<span class="devchefpress-info-value">' . esc_html( date( 'M j, Y', strtotime( $data['startDate'] ) ) ) . '</span>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build delivery address section
	 * 
	 * @param array $data
	 * @return string
	 */
	private static function build_address_section( array $data ): string {
		if ( ! isset( $data['address'] ) || empty( $data['address'] ) ) {
			return '';
		}

		$address = $data['address'];
		if ( ! is_array( $address ) ) {
			$address = json_decode( $address, true ) ?: array();
		}

		if ( empty( $address ) ) {
			return '';
		}

		$html = '<div class="devchefpress-section">';
		$html .= '<h3 class="devchefpress-section-title">📍 Delivery Address</h3>';
		$html .= '<div class="devchefpress-address-card">';

		if ( ! empty( $address['name'] ) ) {
			$html .= '<div class="devchefpress-address-item"><strong>Name:</strong> ' . esc_html( $address['name'] ) . '</div>';
		}

		if ( ! empty( $address['type'] ) ) {
			$html .= '<div class="devchefpress-address-item"><strong>Type:</strong> ' . esc_html( $address['type'] ) . '</div>';
		}

		if ( ! empty( $address['building'] ) ) {
			$html .= '<div class="devchefpress-address-item"><strong>Building:</strong> ' . esc_html( $address['building'] ) . '</div>';
		}

		if ( ! empty( $address['floor'] ) ) {
			$html .= '<div class="devchefpress-address-item"><strong>Floor:</strong> ' . esc_html( $address['floor'] ) . '</div>';
		}

		if ( ! empty( $address['flat'] ) ) {
			$html .= '<div class="devchefpress-address-item"><strong>Flat:</strong> ' . esc_html( $address['flat'] ) . '</div>';
		}

		if ( ! empty( $address['details'] ) ) {
			$html .= '<div class="devchefpress-address-item"><strong>Details:</strong> ' . esc_html( $address['details'] ) . '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build meal plan schedule section
	 * 
	 * @param array $data
	 * @return string
	 */
	private static function build_meal_plan_section( array $data ): string {
		if ( ! isset( $data['slots'] ) || empty( $data['slots'] ) ) {
			return '';
		}

		$slots = $data['slots'];

		$html = '<div class="devchefpress-section">';
		$html .= '<h3 class="devchefpress-section-title">🍽️ Meal Plan Schedule</h3>';
		$html .= '<div class="devchefpress-meal-plan-tabs">';

		// Group meals by day
		$meals_by_day = array();
		foreach ( $slots as $slot ) {
			if ( isset( $slot['day'] ) && isset( $slot['meal'] ) && isset( $slot['recipeSelected'] ) ) {
				$day = $slot['day'];
				if ( ! isset( $meals_by_day[ $day ] ) ) {
					$meals_by_day[ $day ] = array();
				}
				$meals_by_day[ $day ][] = $slot;
			}
		}

		if ( empty( $meals_by_day ) ) {
			$html .= '<p>No meal plan selected</p>';
		} else {
			$days_order = array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' );
			$days_display = array(
				'Mon' => 'Monday',
				'Tue' => 'Tuesday',
				'Wed' => 'Wednesday',
				'Thu' => 'Thursday',
				'Fri' => 'Friday',
				'Sat' => 'Saturday',
				'Sun' => 'Sunday',
			);

			$html .= '<div class="devchefpress-tabs-sidebar">';
			$first_day = true;
			foreach ( $days_order as $day_code ) {
				if ( ! isset( $meals_by_day[ $day_code ] ) ) {
					continue;
				}

				$active_class = $first_day ? ' active' : '';
				$html .= '<div class="devchefpress-tab-item' . esc_attr( $active_class ) . '" data-day="' . esc_attr( $day_code ) . '">' . esc_html( $days_display[ $day_code ] ?? $day_code ) . '</div>';
				$first_day = false;
			}
			$html .= '</div>';

			$html .= '<div class="devchefpress-tabs-content">';
			$first_day = true;
			foreach ( $days_order as $day_code ) {
				if ( ! isset( $meals_by_day[ $day_code ] ) ) {
					continue;
				}

				$active_class = $first_day ? ' active' : '';
				$html .= '<div class="devchefpress-tab-content' . esc_attr( $active_class ) . '" data-day="' . esc_attr( $day_code ) . '">';
				$html .= '<h4 class="devchefpress-day-title">' . esc_html( $days_display[ $day_code ] ?? $day_code ) . '</h4>';

				foreach ( $meals_by_day[ $day_code ] as $slot ) {
					$meal_type = $slot['meal'] ?? '';
					$recipe = $slot['recipeSelected'] ?? array();
					$recipe_name = 'Unknown';

					if ( is_array( $recipe ) ) {
						$recipe_name = $recipe['title'] ?? $recipe['name'] ?? 'Unknown';
					} elseif ( is_object( $recipe ) ) {
						$recipe_name = $recipe->title ?? $recipe->name ?? 'Unknown';
					}

					$html .= '<div class="devchefpress-meal-item">';
					$html .= '<span class="devchefpress-meal-type">' . esc_html( $meal_type ) . '</span>';
					$html .= '<span class="devchefpress-recipe-name">' . esc_html( $recipe_name ) . '</span>';
					$html .= '</div>';
				}

				$html .= '</div>';
				$first_day = false;
			}
			$html .= '</div>';
		}

		

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build pricing section
	 * 
	 * @param array $data
	 * @return string
	 */
	private static function build_pricing_section( array $data, int $subscription_id ): string {
		$html = '<div class="devchefpress-section">';
		$html .= '<h3 class="devchefpress-section-title">💰 Pricing Summary</h3>';
		$html .= '<div class="devchefpress-pricing-card">';
		$subscription = \DevChefPress\Models\UserSubscription::get_by_id( $subscription_id );
		// Subtotal
		$base_price = 0;
		if ( isset( $data['pricing']['subtotal'] ) ) {
			$base_price = (float) $data['pricing']['subtotal'];
			$html .= '<div class="devchefpress-pricing-row">';
			$html .= '<span>Subtotal:</span>';
			$html .= '<span>$' . number_format( (float) $data['pricing']['subtotal'], 2 ) . '</span>';
			$html .= '</div>';
			$html .= '<hr>';
			
			
		}

		// Plan Discount
		if ( isset( $data['pricing']['discount'] ) && $data['pricing']['discount'] > 0 ) {
			$html .= '<div class="devchefpress-pricing-row">';
			$html .= '<span>Plan Discount:</span>';
			$html .= '<span>-$' . number_format( (float) $data['pricing']['discount'], 2 ) . '</span>';
			$html .= '</div>';
		}

		// Promo Discount
		if ( isset( $data['pricing']['promo_discount'] ) && $data['pricing']['promo_discount'] > 0 ) {
			$html .= '<div class="devchefpress-pricing-row">';
			$html .= '<span>Promo Discount:</span>';
			$html .= '<span>-$' . number_format( (float) $data['pricing']['promo_discount'], 2 ) . '</span>';
			$html .= '</div>';
		}

		// Total
		if ( isset( $data['pricing']['total'] ) ) {
			$html .= '<div class="devchefpress-pricing-row devchefpress-pricing-total">';
			$html .= '<span><strong>Total:</strong></span>';
			$html .= '<span><strong>$' . number_format( (float) $data['pricing']['total'], 2 ) . '</strong></span>';
			$html .= '</div>';
			$html .= '<div class="devchefpress-pricing-row devchefpress-pricing-total">';
			$html .= '<span><strong>Current price :</strong></span> <span><strong>$'. $subscription->get_current_price() . '</span></strong>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build subscription history section
	 * 
	 * @param int $subscription_id
	 * @param int $order_id
	 * @return string
	 */
	private static function build_history_section( int $subscription_id, int $order_id ): string {
		if ( ! $subscription_id ) {
			return '';
		}

		$history = SubscriptionManager::get_history( $subscription_id );

		if ( empty( $history ) ) {
			return '';
		}

		$html = '<div class="devchefpress-section">';
		$html .= '<h3 class="devchefpress-section-title">📋 Change History</h3>';
		$html .= '<div class="devchefpress-history-list">';

		foreach ( $history as $record ) {
			$html .= '<div class="devchefpress-history-item">';
			$html .= '<div class="devchefpress-history-header">';
			$html .= '<span class="devchefpress-history-type">' . esc_html( self::format_change_type( $record['change_type'] ) ) . '</span>';
			$html .= '<span class="devchefpress-history-date">' . esc_html( date( 'M j, Y g:i A', strtotime( $record['created_at'] ) ) ) . '</span>';
			$html .= '</div>';

			$html .= '<div class="devchefpress-history-details">';

			if ( $record['old_price'] || $record['new_price'] ) {
				$html .= '<div><strong>Price Change:</strong> $' . number_format( (float) $record['old_price'], 2 ) . ' → $' . number_format( (float) $record['new_price'], 2 ) . '</div>';
			}

			if ( isset( $record['difference'] ) && $record['difference'] !== '' ) {
				// Normalize and fix floating precision
				$diff = round( (float) $record['difference'], 2 );
				
				// Add + only for positive values
				$prefix = $diff > 0 ? '+' : '';
			
				$html .= '<div><strong>testDifference:</strong> ' 
					. $prefix . '$' . number_format( abs( $diff ), 2, '.', '' ) 
					. '</div>';
			}

			if ( $record['refund_amount'] > 0 ) {
				$html .= '<div><strong>Refund Amount:</strong> $' . number_format( (float) $record['refund_amount'], 2 ) . ' (' . $record['refund_status'] . ')</div>';
			}

			if ( ! empty( $record['notes'] ) ) {
				$html .= '<div><strong>Notes:</strong> ' . wp_kses_post( $record['notes'] ) . '</div>';
			}

			$html .= '</div>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build refund section
	 * 
	 * @param int $subscription_id
	 * @param int $order_id
	 * @return string
	 */
	private static function build_refund_section( int $subscription_id, int $order_id ): string {
		if ( ! class_exists( '\DevChefPress\Models\UserSubscription' ) ) {
			return '';
		}

		$subscription = \DevChefPress\Models\UserSubscription::get_by_id( $subscription_id );
		if ( ! $subscription || 0 === $subscription->get_total_refund_pending() ) {
			return '';
		}

		$html = '<div class="devchefpress-section devchefpress-refund-section">';
		$html .= '<h3 class="devchefpress-section-title">💸 Pending Refunds</h3>';
		$html .= '<div class="devchefpress-refund-card">';
		$html .= '<p><strong>Refund Amount Pending:</strong> $' . number_format( $subscription->get_total_refund_pending(), 2 ) . '</p>';
		$html .= '<p class="devchefpress-refund-notice">This refund will be processed within 5-7 business days to your original payment method.</p>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build adjustment orders section
	 * 
	 * @param int $order_id
	 * @return string
	 */
	private static function build_adjustment_orders_section( int $order_id ): string {
		$adjustment_orders = SubscriptionManager::get_adjustment_orders( $order_id );

		if ( empty( $adjustment_orders ) ) {
			return '';
		}

		$html = '<div class="devchefpress-section">';
		$html .= '<h3 class="devchefpress-section-title">🔗 Adjustment Orders</h3>';
		$html .= '<div class="devchefpress-adjustments-list">';

		foreach ( $adjustment_orders as $adj_order_id ) {
			$adj_order = wc_get_order( $adj_order_id );
			if ( ! $adj_order ) {
				continue;
			}

			$html .= '<div class="devchefpress-adjustment-item">';
			$html .= '<div class="devchefpress-adjustment-header">';
			$html .= '<span><strong>Adjustment Order #' . esc_html( $adj_order_id ) . '</strong></span>';
			$html .= '<span>$' . esc_html( $adj_order->get_total() ) . '</span>';
			$html .= '</div>';

			$html .= '<div class="devchefpress-adjustment-details">';
			$html .= '<div><strong>Status:</strong> ' . esc_html( wc_get_order_status_name( $adj_order->get_status() ) ) . '</div>';
			$html .= '<div><strong>Created:</strong> ' . esc_html( $adj_order->get_date_created()->date( 'M j, Y g:i A' ) ) . '</div>';

			$adj_type = $adj_order->get_meta( '_adjustment_type' );
			if ( ! empty( $adj_type ) ) {
				$html .= '<div><strong>Type:</strong> ' . esc_html( ucwords( str_replace( '_', ' ', $adj_type ) ) ) . '</div>';
			}

			$html .= '</div>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Format change type for display
	 * 
	 * @param string $type
	 * @return string
	 */
	private static function format_change_type( string $type ): string {
		$types = array(
			'edit_unpaid' => '✏️ Edited (Before Payment)',
			'price_increase' => '📈 Price Increased',
			'refund' => '💰 Refund Issued',
			'price_decrease' => '📉 Price Decreased',
		);

		return $types[ $type ] ?? ucwords( str_replace( '_', ' ', $type ) );
	}
}
