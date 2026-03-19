<?php
declare( strict_types=1 );

namespace DevChefPress\Traits;

/**
 * Trait MetaHelpers
 *
 * Shared meta helpers for classes that interact with post meta.
 */
trait MetaHelpers {

	/**
	 * Safe meta update — deletes key if value is empty.
	 *
	 * @param mixed $value
	 */
	protected function safe_update_meta( int $post_id, string $key, $value ): void {
		if ( '' === $value || null === $value || ( is_array( $value ) && empty( $value ) ) ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
