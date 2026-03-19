<?php
declare( strict_types=1 );

namespace DevChefPress\Frontend;

use DevChefPress\Hooks\Loader;

/**
 * Class Frontend
 *
 * Bootstraps all frontend hooks.
 */
class Frontend {

	private Loader $loader;
	private Render $render;

	public function __construct( Loader $loader ) {
		$this->loader = $loader;
		$this->render = new Render( $loader );
		$this->register_hooks();
	}

	private function register_hooks(): void {
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_assets' );
	}

	public function enqueue_assets(): void {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		global $post;
		$product = wc_get_product( $post->ID );
		if ( ! $product || 'recipe_product' !== $product->get_type() ) {
			return;
		}

		wp_enqueue_style(
			'dev-chefpress-frontend',
			DEVCHEFPRESS_ASSETS_URL . 'css/frontend.css',
			[],
			DEVCHEFPRESS_VERSION
		);

		wp_enqueue_script(
			'dev-chefpress-frontend',
			DEVCHEFPRESS_ASSETS_URL . 'js/frontend.js',
			[ 'jquery' ],
			DEVCHEFPRESS_VERSION,
			true
		);
	}
}
