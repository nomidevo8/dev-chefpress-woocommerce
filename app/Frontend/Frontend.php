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
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_assets' , 999 );

		// Override product page for recipe_product.
		$this->loader->add_action( 'template_redirect', $this, 'maybe_override_product_page', 1 );
	}

	/**
	 * If product is recipe_product, override the content.
	 */
	public function maybe_override_product_page(): void {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		global $post;
		$product = wc_get_product( $post->ID );
		if ( ! $product || 'recipe_product' !== $product->get_type() ) {
			return;
		}

		// Remove all default WooCommerce single product actions.
		remove_all_actions( 'woocommerce_before_single_product' );
		remove_all_actions( 'woocommerce_before_single_product_summary' );
		remove_all_actions( 'woocommerce_single_product_summary' );
		remove_all_actions( 'woocommerce_after_single_product_summary' );
		remove_all_actions( 'woocommerce_after_single_product' );

		// Add our custom output.
		add_action( 'woocommerce_before_single_product', [ $this, 'render_recipe_override' ], 1 );
	}

	/**
	 * Render our custom template for recipe_product.
	 */
	public function render_recipe_override(): void {
		$loader = new \DevChefPress\Frontend\TemplateLoader();
		$loader->render( 'single-recipe' );
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

		// Dequeue WooCommerce styles
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );

		// Optional: prevent them from loading at all
		wp_deregister_style( 'woocommerce-general' );
		wp_deregister_style( 'woocommerce-layout' );
		wp_deregister_style( 'woocommerce-smallscreen' );

		wp_enqueue_style(
			'dev-chefpress-frontend',
			DEVCHEFPRESS_RESOURCES_URL . 'css/frontend.css',
			[],
			DEVCHEFPRESS_VERSION
		);

		// Add user-configured theme colors as CSS variables for recipe pages.
		$theme_colors = \DevChefPress\Services\PluginSettings::get_theme_colors();
		$inline_css = ':root {' .
			'--cp_product_color-brand: ' . esc_html( $theme_colors['brand'] ) . ';' .
			'--cp_product_color-brand-light: ' . esc_html( $theme_colors['brand_light'] ) . ';' .
			'--cp_product_color-text-main: ' . esc_html( $theme_colors['text_main'] ) . ';' .
			'--cp_product_color-text-muted: ' . esc_html( $theme_colors['text_muted'] ) . ';' .
			'--cp_product_color-bg-light: ' . esc_html( $theme_colors['bg_light'] ) . ';' .
			'--cp_product_color-border: ' . esc_html( $theme_colors['border'] ) . ';' .
			'--cp_product_color-white: ' . esc_html( $theme_colors['white'] ) . ';' .
			'}';
		wp_add_inline_style( 'dev-chefpress-frontend', $inline_css );




		wp_enqueue_style(
			'font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
			[],
			'6.5.0'
		);
		
		wp_enqueue_script(
			'dev-chefpress-frontend',
			DEVCHEFPRESS_RESOURCES_URL . 'js/frontend.js',
			[ 'jquery' ],
			DEVCHEFPRESS_VERSION,
			true
		);
	}
}
