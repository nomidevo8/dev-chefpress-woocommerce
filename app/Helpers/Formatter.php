<?php
declare( strict_types=1 );

namespace DevChefPress\Helpers;

/**
 * Class Formatter
 *
 * Output formatting helpers for frontend rendering.
 */
class Formatter {

	/**
	 * Format a rating as star HTML.
	 */
	public static function stars( float $rating, int $max = 5 ): string {
		$rating  = max( 0.0, min( (float) $max, $rating ) );
		$full    = (int) floor( $rating );
		$half    = ( $rating - $full ) >= 0.5;
		$empty   = $max - $full - ( $half ? 1 : 0 );
		$html    = '<span class="cp-stars" aria-label="' . esc_attr( number_format( $rating, 1 ) . ' / ' . $max ) . '">';
		$html   .= str_repeat( '<span class="cp-star cp-star--full">★</span>', $full );
		if ( $half ) {
			$html .= '<span class="cp-star cp-star--half">★</span>';
		}
		$html   .= str_repeat( '<span class="cp-star cp-star--empty">☆</span>', $empty );
		$html   .= '</span>';
		return $html;
	}

	/**
	 * Format a number for display (remove trailing zeros).
	 */
	public static function number( float $value, int $decimals = 1 ): string {
		$formatted = number_format( $value, $decimals );
		return rtrim( rtrim( $formatted, '0' ), '.' );
	}
}
