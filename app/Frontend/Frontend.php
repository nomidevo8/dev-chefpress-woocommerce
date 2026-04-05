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
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_assets_our_plans' , 999 );

		// Override product page for recipe_product.
		$this->loader->add_action( 'template_redirect', $this, 'maybe_override_product_page', 1 );

		// AJAX handlers for recipe filtering.
		$this->loader->add_action( 'wp_ajax_chefpress_filter_recipes', $this, 'handle_ajax_filter' );
		$this->loader->add_action( 'wp_ajax_nopriv_chefpress_filter_recipes', $this, 'handle_ajax_filter' );

		// AJAX handlers for getting recipe details (modal).
		$this->loader->add_action( 'wp_ajax_chefpress_get_recipe_details', $this, 'handle_ajax_get_recipe_details' );
		$this->loader->add_action( 'wp_ajax_nopriv_chefpress_get_recipe_details', $this, 'handle_ajax_get_recipe_details' );

		// Register shortcodes directly.
		add_shortcode( 'weekly_menu', [ $this, 'render_weekly_menu' ] );
		add_shortcode( 'dev_chefpress_our_plans', [ $this, 'render_our_plans' ] );
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
		$is_recipe_page = false;
		if ( is_singular( 'product' ) ) {
			global $post;
			$product = wc_get_product( $post->ID );
			$is_recipe_page = $product && 'recipe_product' === $product->get_type();
		}

		$is_weekly_menu_page = ( is_page() && has_shortcode( get_post()->post_content ?? '', 'weekly_menu' ) ) || get_query_var( 'weekly_menu' );

		if ( ! $is_recipe_page && ! $is_weekly_menu_page ) {
			return;
		}
		

		// Get theme colors once
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

		if ( $is_recipe_page ) {
			// Dequeue WooCommerce styles
			wp_dequeue_style( 'woocommerce-general' );
			wp_dequeue_style( 'woocommerce-layout' );
			wp_dequeue_style( 'woocommerce-smallscreen' );

			// Optional: prevent them from loading at all
			wp_deregister_style( 'woocommerce-general' );
			wp_deregister_style( 'woocommerce-layout' );
			wp_deregister_style( 'woocommerce-smallscreen' );

			$this->enqueue_recipe_assets( $inline_css );
		}

		if ( $is_weekly_menu_page ) {
			// Enqueue recipe assets for modal display
			$this->enqueue_recipe_assets( $inline_css );

			// Also enqueue weekly menu specific styles
			wp_enqueue_style(
				'dev-chefpress-weekly-menu',
				DEVCHEFPRESS_RESOURCES_URL . 'css/frontend-weekly-menu.css',
				[],
				DEVCHEFPRESS_VERSION
			);

			wp_add_inline_style( 'dev-chefpress-weekly-menu', $inline_css );

			wp_enqueue_script(
				'dev-chefpress-weekly-menu',
				DEVCHEFPRESS_RESOURCES_URL . 'js/frontend-weekly-menu.js',
				[ 'jquery' ],
				DEVCHEFPRESS_VERSION,
				true
			);

			// Localize config for frontend.
			wp_localize_script( 'dev-chefpress-weekly-menu', 'ChefPressConfig', [
				'nonce'       => wp_create_nonce( 'chefpress_filter_nonce' ),
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
			] );
		}
	}

	public function enqueue_assets_our_plans(): void {

		// Google Fonts (Inter + Outfit)
		wp_enqueue_style(
			'dev-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap',
			[],
			null
		);

		// Flatpickr CSS
		wp_enqueue_style(
			'flatpickr-css',
			'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
			[],
			null
		);

		// Leaflet CSS
		wp_enqueue_style(
			'leaflet-css',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			[],
			null
		);

		// Your Custom CSS
		wp_enqueue_style(
			'dev-chefpress-our-plans',
			DEVCHEFPRESS_RESOURCES_URL . 'css/frontend-our-plans.css',
			[],
			DEVCHEFPRESS_VERSION
		);

		// jQuery (WordPress already includes it, just enqueue)
		wp_enqueue_script('jquery');

		// Lucide Icons
		wp_enqueue_script(
			'lucide-icons',
			'https://unpkg.com/lucide@latest',
			[],
			null,
			true
		);

		// Flatpickr JS
		wp_enqueue_script(
			'flatpickr-js',
			'https://cdn.jsdelivr.net/npm/flatpickr',
			[],
			null,
			true
		);

		// Leaflet JS
		wp_enqueue_script(
			'leaflet-js',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			[],
			null,
			true
		);

		// Your Custom JS
		wp_enqueue_script(
			'dev-chefpress-our-plans',
			DEVCHEFPRESS_RESOURCES_URL . 'js/frontend-our-plans.js',
			['jquery', 'flatpickr-js', 'leaflet-js'],
			DEVCHEFPRESS_VERSION,
			true
		);
	}

	/**
	 * Enqueue recipe product page assets.
	 *
	 * @param string $inline_css Theme color CSS variables
	 */
	private function enqueue_recipe_assets( string $inline_css ): void {
		wp_enqueue_style(
			'dev-chefpress-frontend',
			DEVCHEFPRESS_RESOURCES_URL . 'css/frontend.css',
			[],
			DEVCHEFPRESS_VERSION
		);

		wp_enqueue_style(
			'dev-chefpress-filters',
			DEVCHEFPRESS_RESOURCES_URL . 'css/filters.css',
			[ 'dev-chefpress-frontend' ],
			DEVCHEFPRESS_VERSION
		);

		wp_add_inline_style( 'dev-chefpress-frontend', $inline_css );

		wp_enqueue_style(
			'font-awesome-6',
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

		// Localize config for frontend.
		wp_localize_script( 'dev-chefpress-frontend', 'ChefPressConfig', [
			'nonce'       => wp_create_nonce( 'chefpress_filter_nonce' ),
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
		] );
	}

	/**
	 * AJAX handler for recipe filtering.
	 */
	public function handle_ajax_filter(): void {
	
		\DevChefPress\Services\FilterService::handle_ajax_filter();
	}

	/**
	 * AJAX handler for getting recipe modal details.
	 */
	public function handle_ajax_get_recipe_details(): void {
		\DevChefPress\Services\FilterService::handle_ajax_get_recipe_details();
	}

	/**
	 * Render the weekly menu shortcode.
	 */
	public function render_weekly_menu(): string {
		ob_start();
		include DEVCHEFPRESS_PATH . 'app/Frontend/WeeklyMenu/WeeklyMenu.php';
		return ob_get_clean();
	}

	/**
	 * Render the OurPlans shortcode.
	 */
	public function render_our_plans(): string {
		ob_start();
		include DEVCHEFPRESS_PATH . 'templates/our-plans.php';
		return ob_get_clean();
	}
}
