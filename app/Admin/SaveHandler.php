<?php
declare( strict_types=1 );

namespace DevChefPress\Admin;

use DevChefPress\Hooks\Loader;
use DevChefPress\Helpers\Sanitizer;
use DevChefPress\Services\RecipeService;

/**
 * Class SaveHandler
 *
 * Handles saving all ChefPress recipe meta data on post save.
 */
class SaveHandler {
	/** @var array<int, bool> */
	private static array $saved_post_ids = [];

	public function __construct( Loader $loader ) {
		$loader->add_action( 'save_post_product', $this, 'save_recipe_data', 10, 1 );
		// WooCommerce also fires woocommerce_process_product_meta.
		$loader->add_action( 'woocommerce_process_product_meta', $this, 'save_recipe_data', 10, 1 );
	}

	/**
	 * Main save handler.
	 */
	public function save_recipe_data( int $post_id ): void {
		// This callback is attached to two save hooks; guard duplicate execution.
		if ( isset( self::$saved_post_ids[ $post_id ] ) ) {
			return;
		}

		// Verify nonce.
		if (
			! isset( $_POST['_chefpress_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_chefpress_nonce'] ) ), 'dev_chefpress_save' )
		) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Prevent autosave interference.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Only save for product post type.
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		$service = new RecipeService( $post_id );
		$service->process_and_save( $_POST );
		self::$saved_post_ids[ $post_id ] = true;
	}
}
