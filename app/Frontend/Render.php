<?php
declare( strict_types=1 );

namespace DevChefPress\Frontend;

use DevChefPress\Hooks\Loader;
use DevChefPress\Models\Recipe;

/**
 * Class Render
 *
 * Handles frontend output rendering for recipe products.
 */
class Render {

	public function __construct( Loader $loader ) {
		$loader->add_action( 'woocommerce_after_single_product_summary', $this, 'render_recipe', 5 );
	}

	/**
	 * Render the full recipe below the product summary.
	 */
	public function render_recipe(): void {
		global $post;
		$product = wc_get_product( $post->ID );

		if ( ! $product || 'recipe_product' !== $product->get_type() ) {
			return;
		}
	
		$recipe = new Recipe( $post->ID );
		$loader = new TemplateLoader();
		$loader->render( 'single-recipe', [ 'recipe' => $recipe, 'product' => $product ] );
	}
}
