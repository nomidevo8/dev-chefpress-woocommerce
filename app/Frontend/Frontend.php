<?php
declare( strict_types=1 );

namespace DevChefPress\Frontend;

use DevChefPress\Hooks\Loader;
use DevChefPress\Services\PluginSettings;

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

		// AJAX handlers for recipe filtering.
		$this->loader->add_action( 'wp_ajax_chefpress_filter_recipes', $this, 'handle_ajax_filter' );
		$this->loader->add_action( 'wp_ajax_nopriv_chefpress_filter_recipes', $this, 'handle_ajax_filter' );

		// AJAX handlers for getting recipe details (modal).
		$this->loader->add_action( 'wp_ajax_chefpress_get_recipe_details', $this, 'handle_ajax_get_recipe_details' );
		$this->loader->add_action( 'wp_ajax_nopriv_chefpress_get_recipe_details', $this, 'handle_ajax_get_recipe_details' );

		// AJAX handler for creating meal plan order.
		$this->loader->add_action( 'wp_ajax_create_meal_plan_order', $this, 'handle_create_meal_plan_order' );
		$this->loader->add_action( 'wp_ajax_nopriv_create_meal_plan_order', $this, 'handle_create_meal_plan_order' );

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
		$is_our_plans_page = get_query_var( 'our_plans' );

		if ( ! $is_recipe_page && ! $is_weekly_menu_page && ! $is_our_plans_page ) {
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

		if ( $is_our_plans_page ) {
			$this->enqueue_assets_our_plans();
		}
	}

	public function enqueue_assets_our_plans(): void {
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

		// Enqueue recipe product page assets for styling consistency
		$this->enqueue_recipe_assets( $inline_css );

		// Ensure jQuery is enqueued first
		wp_enqueue_script( 'jquery' );

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

		// Lucide Icons (jQuery as dependency)
		wp_enqueue_script(
			'lucide-icons',
			'https://unpkg.com/lucide@latest',
			[ 'jquery' ],
			null,
			true
		);

		// Flatpickr JS (jQuery as dependency)
		wp_enqueue_script(
			'flatpickr-js',
			'https://cdn.jsdelivr.net/npm/flatpickr',
			[ 'jquery' ],
			null,
			true
		);

		// Leaflet JS (jQuery as dependency)
		wp_enqueue_script(
			'leaflet-js',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			[ 'jquery' ],
			null,
			true
		);

		// Your Custom JS (depends on jQuery and all external libraries)
		wp_enqueue_script(
			'dev-chefpress-our-plans',
			DEVCHEFPRESS_RESOURCES_URL . 'js/frontend-our-plans.js',
			[ 'jquery', 'flatpickr-js', 'leaflet-js', 'lucide-icons' ],
			DEVCHEFPRESS_VERSION,
			true
		);

		// Enqueue weekly menu assets for menu selection step
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

		// Localize config for weekly menu
		wp_localize_script( 'dev-chefpress-weekly-menu', 'ChefPressConfig', [
			'nonce'       => wp_create_nonce( 'chefpress_filter_nonce' ),
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
		] );

		// Fetch allergen tags dynamically
		$allergen_tags = get_terms( [
			'taxonomy' => 'chefpress_allergen_tag',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC',
		] );

		$allergens = array_map( function( $tag ) {
			return [
				'name' => $tag->name,
			];
		}, $allergen_tags );

		// Get pricing settings
		$pricing_presets = PluginSettings::get_presets_for_js();

		// Localize the script with dynamic allergens and pricing
		wp_localize_script( 'dev-chefpress-our-plans', 'ChefPressOurPlans', [
			'allergens'     => $allergens,
			'mealPrices'    => $pricing_presets['mealPrices'] ?? [],
			'planDiscounts' => $pricing_presets['planDiscounts'] ?? [],
			'promoCodes'    => $pricing_presets['promoCodes'] ?? [],
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'isLoggedIn'    => is_user_logged_in(),
		] );
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
	 * AJAX handler for creating meal plan order.
	 */
	public function handle_create_meal_plan_order(): void {
		// Verify nonce if needed
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'meal_plan_order_nonce' ) ) {
			// For now, skip nonce check as it's not implemented in JS
		}

		$state_json = urldecode( wp_unslash( $_POST['state'] ?? '' ) );
		if ( empty( $state_json ) ) {
			wp_send_json_error( 'No state data provided' );
			return;
		}

		$state = json_decode( $state_json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( 'Invalid JSON data: ' . json_last_error_msg() );
			return;
		}

		// Create the WooCommerce order
		$order_id = $this->create_meal_plan_order( $state );
		if ( is_wp_error( $order_id ) ) {
			wp_send_json_error( $order_id->get_error_message() );
			return;
		}

		// Get the order and redirect to its payment page
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( 'Failed to retrieve created order' );
			return;
		}

		// Redirect to the order payment page (pay for pending order)
		$checkout_url = $order->get_checkout_payment_url();

		wp_send_json_success( array( 'checkout_url' => $checkout_url ) );
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
		// Enqueue OurPlans assets only when shortcode is used
		$this->enqueue_assets_our_plans();
		
		ob_start();
		include DEVCHEFPRESS_PATH . 'templates/our-plans.php';
		return ob_get_clean();
	}

	/**
	 * Create a WooCommerce order from meal plan state.
	 */
	private function create_meal_plan_order( array $state ): int|\WP_Error {
		if ( ! class_exists( 'WC_Order' ) ) {
			return new \WP_Error( 'woocommerce_not_found', 'WooCommerce is not available' );
		}

		// Calculate pricing
		$pricing = $this->calculate_meal_plan_pricing( $state );

		// Create order
		$order = wc_create_order();

		// Assign order to logged-in user if available
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			$order->set_customer_id( $user_id );
			$user = wp_get_current_user();
			$order->set_billing_email( $user->user_email );
		}

		// Add products based on selected recipes
		foreach ( $state['slots'] as $slot ) {
			if ( isset( $slot['recipeSelected'] ) && $slot['recipeSelected'] ) {
				$product_id = $slot['recipeSelected']['id'];
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$order->add_product( $product, 1 );
				}
			}
		}

		// Set custom price if needed
		if ( $pricing['total'] > 0 ) {
			$order->set_total( $pricing['total'] );
		}

		// Add order meta with all state details
		$order->update_meta_data( '_meal_plan_state', $state );
		$order->update_meta_data( '_meal_plan_pricing', $pricing );

		// Set billing/shipping address if available
		if ( isset( $state['address'] ) ) {
			$address = $state['address'];
			$order->set_billing_address_1( $address['building'] ?? '' );
			$order->set_billing_address_2( $address['floor'] . ' ' . $address['flat'] );
			$order->set_billing_city( '' ); // Not provided
			$order->set_billing_postcode( '' ); // Not provided
			$order->set_billing_country( 'AE' ); // Assuming UAE
			$order->set_shipping_address_1( $address['building'] ?? '' );
			$order->set_shipping_address_2( $address['floor'] . ' ' . $address['flat'] );
			$order->set_shipping_city( '' );
			$order->set_shipping_postcode( '' );
			$order->set_shipping_country( 'AE' );
		}

		// Set delivery date
		if ( isset( $state['startDate'] ) ) {
			$order->update_meta_data( '_delivery_date', $state['startDate'] );
		}

		// Set delivery slot
		if ( isset( $state['deliverySlot'] ) ) {
			$order->update_meta_data( '_delivery_slot', $state['deliverySlot'] );
		}

		// Set order status to pending payment
		$order->set_status( 'pending' );

		// Save the order
		$order->save();

		return $order->get_id();
	}

	/**
	 * Calculate pricing for meal plan.
	 */
	private function calculate_meal_plan_pricing( array $state ): array {
		$meal_prices = array(
			'Breakfast' => 5,
			'Lunch' => 12,
			'Dinner' => 15,
			'Snacks' => 4
		);

		$total = 0;
		$meal_counts = array();

		foreach ( $state['slots'] as $slot ) {
			if ( isset( $slot['recipeSelected'] ) && $slot['recipeSelected'] ) {
				$meal_type = $slot['meal'];
				if ( isset( $meal_prices[$meal_type] ) ) {
					$total += $meal_prices[$meal_type];
					if ( ! isset( $meal_counts[$meal_type] ) ) {
						$meal_counts[$meal_type] = 0;
					}
					$meal_counts[$meal_type]++;
				}
			}
		}

		// Apply plan discount
		$plan_duration = $state['planDuration'] ?? '1 Week';
		$discounts = array(
			'1 Week' => 0,
			'1 Month' => 10,
			'3 Months' => 20,
			'6 Months' => 25
		);

		$discount_percent = $discounts[$plan_duration] ?? 0;
		$discount_amount = $total * ( $discount_percent / 100 );
		$total -= $discount_amount;

		// Apply promo discount
		if ( isset( $state['isPromoApplied'] ) && $state['isPromoApplied'] && isset( $state['promoDiscount'] ) ) {
			$promo_discount = $total * ( $state['promoDiscount'] / 100 );
			$total -= $promo_discount;
		}

		return array(
			'subtotal' => $total + $discount_amount, // Before discounts
			'discount' => $discount_amount,
			'promo_discount' => $promo_discount ?? 0,
			'total' => $total,
			'meal_counts' => $meal_counts
		);
	}
}
