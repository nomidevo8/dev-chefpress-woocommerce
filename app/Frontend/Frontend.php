<?php
declare( strict_types=1 );

namespace DevChefPress\Frontend;

use DevChefPress\Hooks\Loader;
use DevChefPress\Models\UserSubscription;
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

		// AJAX handler for subscription details.
		$this->loader->add_action( 'wp_ajax_devchefpress_get_subscription_details', $this, 'handle_get_subscription_details' );
		$this->loader->add_action( 'wp_ajax_nopriv_devchefpress_get_subscription_details', $this, 'handle_get_subscription_details' );

		// Register shortcodes directly.
		add_shortcode( 'weekly_menu', [ $this, 'render_weekly_menu' ] );
		add_shortcode( 'dev_chefpress_our_plans', [ $this, 'render_our_plans' ] );
		add_shortcode( 'my_subscriptions', [ $this, 'render_my_subscriptions' ] );
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
		$is_my_account_page = is_page( 'my-account' ) || is_account_page();

		if ( ! $is_recipe_page && ! $is_weekly_menu_page && ! $is_our_plans_page && ! $is_my_account_page ) {
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

		if ( $is_my_account_page ) {
			$this->enqueue_assets_my_account();
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
			'nonce'         => wp_create_nonce( 'devchefpress_subscription_nonce' ),
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
	 * Enqueue my-account page assets.
	 */
	private function enqueue_assets_my_account(): void {
		// Get theme colors
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

		// Enqueue WooCommerce My Account CSS
		wp_enqueue_style(
			'dev-chefpress-woocommerce-my-account',
			DEVCHEFPRESS_RESOURCES_URL . 'css/woocommerce-my-account.css',
			[],
			DEVCHEFPRESS_VERSION
		);

		wp_add_inline_style( 'dev-chefpress-woocommerce-my-account', $inline_css );
	}

	public function enqueue_assets_my_subscriptions(): void {
		// Get theme colors
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

		// Enqueue My Subscriptions CSS
		wp_enqueue_style(
			'dev-chefpress-my-subscriptions',
			DEVCHEFPRESS_RESOURCES_URL . 'css/my-subscriptions.css',
			[],
			DEVCHEFPRESS_VERSION
		);

		wp_add_inline_style( 'dev-chefpress-my-subscriptions', $inline_css );

		// Enqueue jQuery
		wp_enqueue_script( 'jquery' );

		// Enqueue My Subscriptions JS
		wp_enqueue_script(
			'dev-chefpress-my-subscriptions',
			DEVCHEFPRESS_RESOURCES_URL . 'js/my-subscriptions.js',
			[ 'jquery' ],
			DEVCHEFPRESS_VERSION,
			true
		);

		// Localize script
		wp_localize_script( 'dev-chefpress-my-subscriptions', 'devchefpress_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'devchefpress_subscription_nonce' )
		) );
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

		$is_edit_mode = isset( $_POST['is_edit_mode'] ) && $_POST['is_edit_mode'] === '1';
		$edit_order_id = isset( $_POST['edit_order_id'] ) ? intval( $_POST['edit_order_id'] ) : 0;

		if ( $is_edit_mode ) {
			$result = $this->update_meal_plan_order( $edit_order_id, $state );
		} else {
			$result = $this->create_meal_plan_order( $state );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
			return;
		}

		// For edit mode, check if payment is needed
		if ( $is_edit_mode ) {
			$order = wc_get_order( $result['order_id'] );
			$needs_payment = $result['price_difference'] > 0;
			
			if ( $needs_payment ) {
				$checkout_url = $order->get_checkout_payment_url();
				wp_send_json_success( array( 
					'needs_payment' => true, 
					'checkout_url' => $checkout_url,
					'price_difference' => $result['price_difference']
				) );
			} else {
				// No payment needed
				wp_send_json_success( array( 
					'needs_payment' => false, 
					'redirect_url' => wc_get_account_endpoint_url( 'orders' ),
					'price_difference' => $result['price_difference']
				) );
			}
		} else {
			// Normal creation flow
			$order = wc_get_order( $result );
			if ( ! $order ) {
				wp_send_json_error( 'Failed to retrieve created order' );
				return;
			}

			// Redirect to the order payment page (pay for pending order)
			$checkout_url = $order->get_checkout_payment_url();

			wp_send_json_success( array( 'checkout_url' => $checkout_url ) );
		}
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

	public function render_my_subscriptions(): string {
		// Enqueue My Subscriptions assets only when shortcode is used
		$this->enqueue_assets_my_subscriptions();
		
		ob_start();
		include DEVCHEFPRESS_PATH . 'templates/my-subscriptions.php';
		return ob_get_clean();
	}

	/**
	 * Create a subscription record in the custom table.
	 */
	private function create_subscription_record( array $state, int $user_id ): int|\WP_Error {
		if ( $user_id <= 0 ) {
			return new \WP_Error( 'invalid_user', 'Valid user is required to create a meal plan subscription.' );
		}

		$plan_name = sanitize_text_field( $state['planDuration'] ?? $state['planName'] ?? 'Meal Plan' );
		$pricing   = $this->calculate_meal_plan_pricing( $state );

		$data = [
			'user_id'          => $user_id,
			'plan_name'        => $plan_name,
			'meals_data'       => $state['slots'] ?? [],
			'delivery_details' => [
				'startDate'    => $state['startDate'] ?? '',
				'deliverySlot' => $state['deliverySlot'] ?? '',
				'address'      => $state['address'] ?? [],
			],
			'original_price'   => $pricing['total'],
			'current_price'    => $pricing['total'],
			'total_paid'       => 0.0,
			'parent_order_id'  => 0,
		];

		return UserSubscription::create_subscription_record( $data );
	}

	/**
	 * Create a WooCommerce order from meal plan state.
	 */
	private function create_meal_plan_order( array $state ): int|\WP_Error {
		if ( ! class_exists( 'WC_Order' ) ) {
			return new \WP_Error( 'woocommerce_not_found', 'WooCommerce is not available' );
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return new \WP_Error( 'user_not_logged_in', 'You must be logged in to create a meal plan order.' );
		}

		$subscription_id = $this->create_subscription_record( $state, $user_id );
		if ( is_wp_error( $subscription_id ) ) {
			return $subscription_id;
		}

		// Calculate pricing
		$pricing = $this->calculate_meal_plan_pricing( $state );

		// Create order
		$order = wc_create_order();
		if ( ! $order ) {
			return new \WP_Error( 'order_creation_failed', 'Failed to create WooCommerce order.' );
		}

		// Assign order to logged-in user
		$order->set_customer_id( $user_id );
		$user = wp_get_current_user();
		$order->set_billing_email( $user->user_email );

		// Add products based on selected recipes
		foreach ( $state['slots'] as $slot ) {
			if ( isset( $slot['recipeSelected'] ) && $slot['recipeSelected'] ) {
				$product_id = intval( $slot['recipeSelected']['id'] );
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
			$order->set_billing_address_1( sanitize_text_field( $address['building'] ?? '' ) );
			$order->set_billing_address_2( sanitize_text_field( ( $address['floor'] ?? '' ) . ' ' . ( $address['flat'] ?? '' ) ) );
			$order->set_billing_city( '' );
			$order->set_billing_postcode( '' );
			$order->set_billing_country( 'AE' );
			$order->set_shipping_address_1( sanitize_text_field( $address['building'] ?? '' ) );
			$order->set_shipping_address_2( sanitize_text_field( ( $address['floor'] ?? '' ) . ' ' . ( $address['flat'] ?? '' ) ) );
			$order->set_shipping_city( '' );
			$order->set_shipping_postcode( '' );
			$order->set_shipping_country( 'AE' );
		}

		// Set delivery date
		if ( isset( $state['startDate'] ) ) {
			$order->update_meta_data( '_delivery_date', sanitize_text_field( $state['startDate'] ) );
		}

		// Set delivery slot
		if ( isset( $state['deliverySlot'] ) ) {
			$order->update_meta_data( '_delivery_slot', sanitize_text_field( $state['deliverySlot'] ) );
		}

		// Set order status to pending payment
		$order->set_status( 'pending' );

		// Save the order
		$order->save();

		$order_id = $order->get_id();
		if ( ! $order_id ) {
			return new \WP_Error( 'order_id_missing', 'The WooCommerce order did not return a valid ID.' );
		}

		$link_result = UserSubscription::link_subscription_to_order( $subscription_id, $order_id );
		if ( is_wp_error( $link_result ) ) {
			return $link_result;
		}

		update_post_meta( $order_id, '_subscription_id', $subscription_id );
		update_post_meta( $order_id, '_order_type', 'meal_plan' );

		return $order_id;
	}

	/**
	 * Update an existing meal plan order.
	 */
	private function update_meal_plan_order( int $order_id, array $state ): array|\WP_Error {
		if ( ! class_exists( 'WC_Order' ) ) {
			return new \WP_Error( 'woocommerce_not_found', 'WooCommerce is not available' );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new \WP_Error( 'order_not_found', 'Order not found' );
		}

		// Check if user owns this order
		if ( $order->get_customer_id() !== get_current_user_id() ) {
			return new \WP_Error( 'unauthorized', 'You do not have permission to edit this order' );
		}

		// Get original pricing
		$original_pricing = $order->get_meta( '_meal_plan_pricing' );
		$original_total = 0;
		if ( is_array( $original_pricing ) && isset( $original_pricing['total'] ) ) {
			$original_total = (float) $original_pricing['total'];
		} elseif ( is_string( $original_pricing ) ) {
			$parsed = json_decode( $original_pricing, true );
			$original_total = (float) ( $parsed['total'] ?? $order->get_total() );
		} else {
			$original_total = (float) $order->get_total();
		}

		// Calculate new pricing
		$new_pricing = $this->calculate_meal_plan_pricing( $state );
		$new_total = (float) $new_pricing['total'];

		// Calculate price difference
		$price_difference = $new_total - $original_total;

		// Remove existing line items
		foreach ( $order->get_items() as $item_id => $item ) {
			$order->remove_item( $item_id );
		}

		// Add new products based on selected recipes
		foreach ( $state['slots'] as $slot ) {
			if ( isset( $slot['recipeSelected'] ) && $slot['recipeSelected'] ) {
				$product_id = $slot['recipeSelected']['id'];
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$order->add_product( $product, 1 );
				}
			}
		}

		// Update order total
		$order->set_total( $new_total );

		// Update order meta with new state and pricing
		$order->update_meta_data( '_meal_plan_state', $state );
		$order->update_meta_data( '_meal_plan_pricing', $new_pricing );

		// Store edit information
		$order->update_meta_data( '_original_order_total', $original_total );
		$order->update_meta_data( '_updated_order_total', $new_total );
		$order->update_meta_data( '_price_difference', $price_difference );
		$order->update_meta_data( '_is_edited_order', 'yes' );

		// Handle refund if new price is lower
		if ( $price_difference < 0 ) {
			$refund_amount = abs( $price_difference );
			$order->update_meta_data( '_refund_pending_amount', $refund_amount );
		} else {
			$order->delete_meta_data( '_refund_pending_amount' );
		}

		// Update billing/shipping address if changed
		if ( isset( $state['address'] ) ) {
			$address = $state['address'];
			$order->set_billing_address_1( $address['building'] ?? '' );
			$order->set_billing_address_2( ( $address['floor'] ?? '' ) . ' ' . ( $address['flat'] ?? '' ) );
			$order->set_billing_city( '' );
			$order->set_billing_postcode( '' );
			$order->set_billing_country( 'AE' );
			$order->set_shipping_address_1( $address['building'] ?? '' );
			$order->set_shipping_address_2( ( $address['floor'] ?? '' ) . ' ' . ( $address['flat'] ?? '' ) );
			$order->set_shipping_city( '' );
			$order->set_shipping_postcode( '' );
			$order->set_shipping_country( 'AE' );
		}

		// Update delivery date
		if ( isset( $state['startDate'] ) ) {
			$order->update_meta_data( '_delivery_date', $state['startDate'] );
		}

		// Update delivery slot
		if ( isset( $state['deliverySlot'] ) ) {
			$order->update_meta_data( '_delivery_slot', $state['deliverySlot'] );
		}

		// Set order status to processing if no payment needed, or pending if payment required
		if ( $price_difference > 0 ) {
			$order->set_status( 'pending' );
		} else {
			$order->set_status( 'processing' );
		}

		// Save the order
		$order->save();

		return array(
			'order_id' => $order_id,
			'price_difference' => $price_difference,
			'original_total' => $original_total,
			'new_total' => $new_total
		);
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

	/**
	 * AJAX handler for getting subscription details.
	 */
	public function handle_get_subscription_details(): void {
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'] ?? '', 'devchefpress_subscription_nonce')) {
			wp_send_json_error(['message' => 'Invalid nonce']);
			return;
		}

		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

		if (!$order_id) {
			wp_send_json_error(['message' => 'Invalid order ID']);
			return;
		}

		$order = wc_get_order($order_id);

		if (!$order) {
			wp_send_json_error(['message' => 'Order not found']);
			return;
		}

		// Check if user owns this order
		if ($order->get_customer_id() !== get_current_user_id()) {
			wp_send_json_error(['message' => 'Unauthorized']);
			return;
		}

		$state = $order->get_meta('_meal_plan_state');
		$pricing = $order->get_meta('_meal_plan_pricing');

		if (empty($state)) {
			wp_send_json_error(['message' => 'No subscription data found']);
			return;
		}

		// Prepare response data
		$response_data = [];

		// Add state data
		if (!empty($state)) {
			$response_data = is_array($state) ? $state : json_decode((string)$state, true);
		}

		// Add pricing data from separate meta if exists
		if (!empty($pricing)) {
			$pricing_data = is_array($pricing) ? $pricing : json_decode((string)$pricing, true);
			$response_data['pricing'] = $pricing_data;
		}

		// If no separate pricing meta, extract from WooCommerce order
		if (empty($response_data['pricing']) || empty($response_data['pricing'])) {
			$response_data['subtotal'] = (float) $order->get_subtotal();
			$response_data['total'] = (float) $order->get_total();

			// Calculate discounts from order coupons
			$coupon_discount = 0;
			foreach ($order->get_coupons() as $coupon) {
				$coupon_discount += (float) $coupon->get_discount();
			}

			if ($coupon_discount > 0) {
				$response_data['couponTotal'] = $coupon_discount;
			}
		}

		// Add order details
		$response_data['order_number'] = $order->get_order_number();
		$response_data['customer_name'] = $order->get_formatted_billing_full_name();
		$response_data['order_id'] = $order_id;

		wp_send_json_success($response_data);
	}
}
