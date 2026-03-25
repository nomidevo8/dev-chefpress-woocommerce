<?php
declare( strict_types=1 );

namespace DevChefPress\Helpers;

use DevChefPress\Services\PluginSettings;

/**
 * Handles dynamic week calculations based on configurable start date.
 */
final class WeekCalculator {

	/**
	 * Get the configured weekly start date.
	 *
	 * @return \DateTime
	 */
	public static function get_start_date(): \DateTime {
		$start_date_str = PluginSettings::all()['weekly_start_date'];
		return \DateTime::createFromFormat( 'Y-m-d', $start_date_str ) ?? new \DateTime();
	}

	/**
	 * Calculate weeks passed since start date.
	 *
	 * @return int Number of complete weeks passed
	 */
	public static function weeks_passed(): int {
		$start_date = self::get_start_date();
		$today = new \DateTime();
		
		$interval = $start_date->diff( $today );
		$days_passed = (int) $interval->format( '%a' );
		
		return (int) floor( $days_passed / 7 );
	}

	/**
	 * Determine the current active week index (1-based).
	 *
	 * @param int $total_weeks Total number of weeks available
	 * @return int Active week index (1 to total_weeks, with looping)
	 */
	public static function get_active_week_index( int $total_weeks ): int {
		if ( $total_weeks <= 0 ) {
			return 1;
		}

		$weeks_passed = self::weeks_passed();
		
		// Use modulo to loop weeks if needed
		$week_index = ( $weeks_passed % $total_weeks ) + 1;
		
		return (int) $week_index;
	}

	/**
	 * Calculate date ranges for all weeks starting from the configured start date.
	 *
	 * @param int $total_weeks Number of weeks to calculate
	 * @return array<int, array{start: \DateTime, end: \DateTime, range: string, month: string}>
	 */
	public static function calculate_week_ranges( int $total_weeks ): array {
		$start_date = self::get_start_date();
		$ranges = [];

		for ( $i = 0; $i < $total_weeks; $i++ ) {
			$week_start = clone $start_date;
			$week_start->modify( '+' . ( $i * 7 ) . ' days' );
			
			$week_end = clone $week_start;
			$week_end->modify( '+6 days' );

			$start_day = (int) $week_start->format( 'j' );
			$end_day = (int) $week_end->format( 'j' );
			$start_month = $week_start->format( 'M' );
			$end_month = $week_end->format( 'M' );

			if ( $start_month === $end_month ) {
				$range = $start_day . ' – ' . $end_day;
				$month_display = $start_month;
			} else {
				$range = $start_day . ' – ' . $end_day;
				$month_display = $start_month . ' – ' . $end_month;
			}

			$ranges[ $i + 1 ] = [
				'start' => $week_start,
				'end' => $week_end,
				'range' => $range,
				'month' => $month_display,
			];
		}

		return $ranges;
	}

	/**
	 * Calculate weeks to display in the carousel.
	 * Shows: up to 3 past weeks + current week + future weeks (minimum = total_weeks) + looping
	 *
	 * @param int $total_weeks Total weeks available
	 * @return array<int, array{label: string, range: string, month: string, index: int, is_active: bool, is_past: bool, is_future: bool}>
	 */
	public static function get_carousel_weeks( int $total_weeks ): array {
		$active_week_index = self::get_active_week_index( $total_weeks );
		$carousel_weeks = [];
		$max_past_weeks = 3;

		// Calculate starting point: show up to 3 past weeks
		$start_offset = min( $max_past_weeks, $active_week_index - 1 );
		$start_index = $active_week_index - $start_offset;

		// Calculate end point: show future weeks (at least total_weeks worth)
		$end_offset = $total_weeks;
		$end_index = $active_week_index + $end_offset;

		// Generate weeks from start to end, with looping
		for ( $i = $start_index; $i <= $end_index; $i++ ) {
			// Map to actual week index with looping
			$actual_week_index = ( ( $i - 1 ) % $total_weeks ) + 1;
			
			// Calculate start date for this actual week
			$start_date = self::get_start_date();
			$weeks_offset = $i - 1;
			$week_start = clone $start_date;
			$week_start->modify( '+' . ( $weeks_offset * 7 ) . ' days' );
			
			$week_end = clone $week_start;
			$week_end->modify( '+6 days' );

			$start_day = (int) $week_start->format( 'j' );
			$end_day = (int) $week_end->format( 'j' );
			$start_month = $week_start->format( 'M' );
			$end_month = $week_end->format( 'M' );

			if ( $start_month === $end_month ) {
				$range = $start_day . ' – ' . $end_day;
				$month_display = $start_month;
			} else {
				$range = $start_day . ' – ' . $end_day;
				$month_display = $start_month . ' – ' . $end_month;
			}

			$is_active = ( $i === $active_week_index );
			$is_past = ( $i < $active_week_index );
			$is_future = ( $i > $active_week_index );

			$carousel_weeks[] = [
				'label' => 'Week ' . $actual_week_index,
				'range' => $range,
				'month' => $month_display,
				'index' => $actual_week_index,
				'week_number_display' => $i,
				'is_active' => $is_active,
				'is_past' => $is_past,
				'is_future' => $is_future,
			];
		}

		return $carousel_weeks;
	}

	/**
	 * Get description of current week status.
	 *
	 * @param int $total_weeks Total weeks available
	 * @return string Human-readable week info
	 */
	public static function get_current_week_info( int $total_weeks ): string {
		$active_week = self::get_active_week_index( $total_weeks );
		$weeks_passed = self::weeks_passed();
		
		$info = sprintf(
			'Week %d of %d (total weeks passed: %d)',
			$active_week,
			$total_weeks,
			$weeks_passed
		);

		return $info;
	}
}
