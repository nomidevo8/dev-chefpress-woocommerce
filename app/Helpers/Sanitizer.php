<?php
declare( strict_types=1 );

namespace DevChefPress\Helpers;

/**
 * Class Sanitizer
 *
 * Centralised sanitization helpers for ChefPress.
 */
class Sanitizer {

	/**
	 * Sanitize a plain text string.
	 */
	public static function text( string $value ): string {
		return sanitize_text_field( wp_unslash( $value ) );
	}

	/**
	 * Sanitize a textarea (allow newlines).
	 */
	public static function textarea( string $value ): string {
		return sanitize_textarea_field( wp_unslash( $value ) );
	}

	/**
	 * Sanitize HTML content (for rich fields if added later).
	 */
	public static function html( string $value ): string {
		return wp_kses_post( wp_unslash( $value ) );
	}

	/**
	 * Sanitize URL.
	 */
	public static function url( string $value ): string {
		return esc_url_raw( wp_unslash( $value ) );
	}

	/**
	 * Sanitize a positive float.
	 */
	public static function positive_float( float $value ): float {
		return max( 0.0, $value );
	}
}
