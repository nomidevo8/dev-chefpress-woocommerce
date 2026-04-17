<?php
declare( strict_types=1 );

namespace DevChefPress\Frontend;

use DevChefPress\Hooks\Loader;
use DevChefPress\Models\UserSubscription;
use DevChefPress\Services\PluginSettings;
use DevChefPress\Services\SubscriptionManager;
use DevChefPress\Services\NotificationService;

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

		$this->loader->add_action( 'wp_ajax_devchefpress_save_menu_selection', $this, 'handle_save_menu_selection' );
		$this->loader->add_action( 'wp_ajax_nopriv_devchefpress_save_menu_selection', $this, 'handle_save_menu_selection' );

		// AJAX handler for updating subscription address from the modal.
		$this->loader->add_action( 'wp_ajax_devchefpress_update_subscription_address', $this, 'handle_update_subscription_address' );
		$this->loader->add_action( 'wp_ajax_nopriv_devchefpress_update_subscription_address', $this, 'handle_update_subscription_address' );

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
			'--chefpress-primary-color: ' . esc_html( $theme_colors['brand'] ) . ';' .
			'}';

		// Enqueue My Subscriptions CSS
		wp_enqueue_style(
			'dev-chefpress-my-subscriptions',
			DEVCHEFPRESS_RESOURCES_URL . 'css/my-subscriptions.css',
			array(),
			DEVCHEFPRESS_VERSION
		);

		// Enqueue Subscription Details CSS
		wp_enqueue_style(
			'dev-chefpress-subscription-details',
			DEVCHEFPRESS_RESOURCES_URL . 'css/subscription-details.css',
			array(),
			DEVCHEFPRESS_VERSION
		);

		wp_add_inline_style( 'dev-chefpress-my-subscriptions', $inline_css );

		// Enqueue jQuery
		wp_enqueue_script( 'jquery' );

		// Enqueue My Subscriptions JS
		wp_enqueue_script(
			'dev-chefpress-my-subscriptions',
			DEVCHEFPRESS_RESOURCES_URL . 'js/my-subscriptions.js',
			array( 'jquery' ),
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

		// Add products based on selected recipes (structure only, prices are 0)
		foreach ( $state['slots'] as $slot ) {
			if ( isset( $slot['recipeSelected'] ) && $slot['recipeSelected'] ) {
				$product_id = intval( $slot['recipeSelected']['id'] );
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$order->add_product( $product, 1 );
				}
			}
		}

		// Add package pricing as a fee item (source of truth for totals)
		if ( $pricing['total'] > 0 ) {
			$fee = new \WC_Order_Item_Fee();
			$fee->set_name( 'Meal Plan Package' );
			$fee->set_amount( $pricing['total'] );
			$fee->set_total( $pricing['total'] );
			$fee->set_tax_status( 'none' ); // No tax on package fee
			$order->add_item( $fee );
		}

		// Calculate totals after adding all items
		$order->calculate_totals();

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

		// Set subscription and order type in meta BEFORE saving
		$order->update_meta_data( '_subscription_id', $subscription_id );
		$order->update_meta_data( '_order_type', 'meal_plan' );

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

		return $order_id;
	}

	/**
	 * Update an existing meal plan order with comprehensive billing logic
	 */
	private function update_meal_plan_order( int $order_id, array $state ): array|\WP_Error {
		if ( ! class_exists( 'WC_Order' ) ) {
			return new \WP_Error( 'woocommerce_not_found', 'WooCommerce is not available' );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new \WP_Error( 'order_not_found', 'Order not found' );
		}

		$user_id = get_current_user_id();

		// Check if user owns this order
		if ( $order->get_customer_id() !== $user_id ) {
			return new \WP_Error( 'unauthorized', 'You do not have permission to edit this order' );
		}

		// Calculate new pricing
		$new_pricing = $this->calculate_meal_plan_pricing( $state );

		// Get payment status
		$payment_status = SubscriptionManager::get_payment_status( $order );

		// Get subscription ID
		$subscription_id = (int) $order->get_meta( '_subscription_id' );

		// CASE A: Order NOT PAID - Update existing order
		if ( 'unpaid' === $payment_status ) {
			$result = SubscriptionManager::handle_unpaid_edit( $order_id, $state, $new_pricing );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$price_difference = $result['price_difference'];

			// Update order meta with new state details
			$order->update_meta_data( '_meal_plan_state', $state );
			$order->update_meta_data( '_meal_plan_pricing', $new_pricing );

			// Update billing/shipping address if available
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

			// Update delivery date and slot
			if ( isset( $state['startDate'] ) ) {
				$order->update_meta_data( '_delivery_date', sanitize_text_field( $state['startDate'] ) );
			}
			if ( isset( $state['deliverySlot'] ) ) {
				$order->update_meta_data( '_delivery_slot', sanitize_text_field( $state['deliverySlot'] ) );
			}

			$order->save();

			// Notify user
			NotificationService::notify_edit_unpaid( $user_id, $order_id, $price_difference );

			return $result;
		}

		// CASE B: Order ALREADY PAID - Handle based on price difference
		// Get the accurate original total including all historical adjustments
		$subscription = UserSubscription::get_by_id( $subscription_id );
		$original_total = (float) $order->get_total();
		
		if ( $subscription ) {
			$original_total = SubscriptionManager::calculate_current_price_from_history( 
				$subscription_id, 
				$original_total 
			);
		}
		
		$new_total = (float) $new_pricing['total'];
		$price_difference = $new_total - $original_total;
		// echo "Original Total: $original_total, New Total: $new_total, Price Difference: $price_difference"; // Debug log
		// die;
		if ( $price_difference > 0 ) {
			// CASE B1: Price Increase - Create adjustment order
			$adjustment_order_id = SubscriptionManager::create_adjustment_order(
				$order_id,
				$subscription_id,
				$price_difference,
				$new_pricing
			);

			if ( is_wp_error( $adjustment_order_id ) ) {
				return $adjustment_order_id;
			}

			// Update subscription with new meal plan data
			if ( $subscription_id > 0 ) {
				$subscription = UserSubscription::get_by_id( $subscription_id );
				if ( $subscription ) {
					$plan_name = sanitize_text_field( $state['planDuration'] ?? $state['planName'] ?? 'Meal Plan' );
					$subscription->set_plan_name( $plan_name )
						->set_meals_data( $state['slots'] ?? array() )
						->set_delivery_details( [
							'startDate'    => $state['startDate'] ?? '',
							'deliverySlot' => $state['deliverySlot'] ?? '',
							'address'      => $state['address'] ?? [],
						] )
						->save();
				}
			}

			// Update order meta with new state details
			$order->update_meta_data( '_meal_plan_state', $state );
			$order->update_meta_data( '_meal_plan_pricing', $new_pricing );

			// Update billing/shipping address if available
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

			// Update delivery date and slot
			if ( isset( $state['startDate'] ) ) {
				$order->update_meta_data( '_delivery_date', sanitize_text_field( $state['startDate'] ) );
			}
			if ( isset( $state['deliverySlot'] ) ) {
				$order->update_meta_data( '_delivery_slot', sanitize_text_field( $state['deliverySlot'] ) );
			}

			$order->save();

			// Notify user and admin
			NotificationService::notify_price_increase( $user_id, $adjustment_order_id, $price_difference );
			NotificationService::notify_admin( $order_id, 'price_increase' );

			return array(
				'order_id' => $adjustment_order_id,
				'price_difference' => $price_difference,
				'original_total' => $original_total,
				'new_total' => $new_total,
				'adjustment_order' => true
			);

		} elseif ( $price_difference < 0 ) {
			// CASE B2: Price Decrease - Handle refund
			$subscription = UserSubscription::get_by_id( $subscription_id );

			if ( ! $subscription ) {
				return new \WP_Error( 'subscription_not_found', 'Subscription record not found' );
			}

			$refund_result = SubscriptionManager::handle_price_decrease(
				$subscription_id,
				$order_id,
				$price_difference,
				$subscription->get_delivery_details(),
				$state
			);

			if ( is_wp_error( $refund_result ) ) {
				return $refund_result;
			}

			// Reload subscription after handle_price_decrease updated it
			$subscription = UserSubscription::get_by_id( $subscription_id );

			// Update subscription with new meal plan data
			$plan_name = sanitize_text_field( $state['planDuration'] ?? $state['planName'] ?? 'Meal Plan' );
			$subscription->set_plan_name( $plan_name )
				->set_meals_data( $state['slots'] ?? array() )
				->set_delivery_details( [
					'startDate'    => $state['startDate'] ?? '',
					'deliverySlot' => $state['deliverySlot'] ?? '',
					'address'      => $state['address'] ?? [],
				] )
				->save();

			// Update order meta with new state details
			$order->update_meta_data( '_meal_plan_state', $state );
			$order->update_meta_data( '_meal_plan_pricing', $new_pricing );

			// Update billing/shipping address if available
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

			// Update delivery date and slot
			if ( isset( $state['startDate'] ) ) {
				$order->update_meta_data( '_delivery_date', sanitize_text_field( $state['startDate'] ) );
			}
			if ( isset( $state['deliverySlot'] ) ) {
				$order->update_meta_data( '_delivery_slot', sanitize_text_field( $state['deliverySlot'] ) );
			}

			$order->save();

			// Notify user and admin
			NotificationService::notify_refund_pending(
				$user_id,
				$refund_result['refund_amount'],
				$refund_result['remaining_days']
			);
			NotificationService::notify_admin( $order_id, 'refund_required' );

			return array(
				'order_id' => $order_id,
				'price_difference' => $price_difference,
				'original_total' => $original_total,
				'new_total' => $new_total,
				'refund' => $refund_result
			);

		} else {
			// No price difference - just update subscription
			if ( $subscription_id > 0 ) {
				$subscription = UserSubscription::get_by_id( $subscription_id );
				if ( $subscription ) {
					$plan_name = sanitize_text_field( $state['planDuration'] ?? $state['planName'] ?? 'Meal Plan' );
					$subscription->set_plan_name( $plan_name )
						->set_meals_data( $state['slots'] ?? array() )
						->set_delivery_details( [
							'startDate'    => $state['startDate'] ?? '',
							'deliverySlot' => $state['deliverySlot'] ?? '',
							'address'      => $state['address'] ?? [],
						] )
						->save();
				}
			}

			// Update order meta with new state details
			$order->update_meta_data( '_meal_plan_state', $state );
			$order->update_meta_data( '_meal_plan_pricing', $new_pricing );

			// Update billing/shipping address if available
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

			// Update delivery date and slot
			if ( isset( $state['startDate'] ) ) {
				$order->update_meta_data( '_delivery_date', sanitize_text_field( $state['startDate'] ) );
			}
			if ( isset( $state['deliverySlot'] ) ) {
				$order->update_meta_data( '_delivery_slot', sanitize_text_field( $state['deliverySlot'] ) );
			}

			$order->save();

			// Record history for the edit
			if ( $subscription_id > 0 ) {
				SubscriptionManager::insert_history( array(
					'subscription_id' => $subscription_id,
					'order_id' => $order_id,
					'change_type' => 'edit_no_change',
					'old_price' => $original_total,
					'new_price' => $new_total,
					'difference' => 0,
					'notes' => 'Subscription updated with no price change'
				) );
			}

			return array(
				'order_id' => $order_id,
				'price_difference' => 0,
				'original_total' => $original_total,
				'new_total' => $new_total
			);
		}
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
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'devchefpress_subscription_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => 'Invalid order ID' ) );
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'Order not found' ) );
			return;
		}

		// Check if user owns this order
		if ( $order->get_customer_id() !== get_current_user_id() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
			return;
		}

		$state = $order->get_meta( '_meal_plan_state' );
		$pricing = $order->get_meta( '_meal_plan_pricing' );

		if ( empty( $state ) ) {
			wp_send_json_error( array( 'message' => 'No subscription data found' ) );
			return;
		}

		// Prepare response data
		$response_data = array();

		// Add state data
		if ( ! empty( $state ) ) {
			$response_data = is_array( $state ) ? $state : json_decode( (string) $state, true );
		}

		// Add pricing data from separate meta if exists
		if ( ! empty( $pricing ) ) {
			$pricing_data = is_array( $pricing ) ? $pricing : json_decode( (string) $pricing, true );
			$response_data['pricing'] = $pricing_data;
		}

		// If no separate pricing meta, extract from WooCommerce order
		if ( empty( $response_data['pricing'] ) ) {
			$response_data['pricing'] = array(
				'subtotal' => (float) $order->get_subtotal(),
				'total' => (float) $order->get_total(),
			);

			// Calculate discounts from order coupons
			$coupon_discount = 0;
			foreach ( $order->get_coupons() as $coupon ) {
				$coupon_discount += (float) $coupon->get_discount();
			}

			if ( $coupon_discount > 0 ) {
				$response_data['pricing']['coupon_discount'] = $coupon_discount;
			}
		}

		// Add order details
		$response_data['order_number'] = $order->get_order_number();
		$response_data['customer_name'] = $order->get_formatted_billing_full_name();
		$response_data['order_id'] = $order_id;

		// Get subscription ID and build detailed HTML
		$subscription_id = $order->get_meta( '_subscription_id' );
		if ( $subscription_id ) {
			$response_data['subscription_id'] = $subscription_id;
			// Build detailed HTML with history
			$html = SubscriptionDetailsHelper::build_subscription_details_html(
				$response_data,
				(int) $subscription_id,
				$order_id
			);
			$response_data['html'] = $html;
		}

		wp_send_json_success( $response_data );
	}

	/**
	 * AJAX handler for updating the subscription delivery address.
	 */
	public function handle_update_subscription_address(): void {
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'devchefpress_subscription_nonce' ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'User not logged in' ], 401 );
			return;
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$address = isset( $_POST['address'] ) ? json_decode( wp_unslash( $_POST['address'] ), true ) : array();

		if ( $order_id <= 0 || ! is_array( $address ) ) {
			wp_send_json_error( [ 'message' => 'Invalid request data' ], 400 );
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( [ 'message' => 'Order not found' ], 404 );
			return;
		}

		if ( $order->get_customer_id() !== get_current_user_id() ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
			return;
		}

		$state = $order->get_meta( '_meal_plan_state' );
		$state_data = is_array( $state ) ? $state : json_decode( (string) $state, true );
		if ( ! is_array( $state_data ) ) {
			$state_data = array();
		}

		$sanitized_address = array(
			'name'     => sanitize_text_field( $address['name'] ?? '' ),
			'type'     => sanitize_text_field( $address['type'] ?? '' ),
			'building' => sanitize_text_field( $address['building'] ?? '' ),
			'floor'    => sanitize_text_field( $address['floor'] ?? '' ),
			'flat'     => sanitize_text_field( $address['flat'] ?? '' ),
			'details'  => sanitize_textarea_field( $address['details'] ?? '' ),
			'lat'      => isset( $address['lat'] ) ? floatval( $address['lat'] ) : '',
			'lng'      => isset( $address['lng'] ) ? floatval( $address['lng'] ) : '',
		);

		$state_data['address'] = $sanitized_address;
		$order->update_meta_data( '_meal_plan_state', $state_data );
		$order->set_billing_address_1( $sanitized_address['building'] );
		$order->set_billing_address_2( trim( $sanitized_address['floor'] . ' ' . $sanitized_address['flat'] ) );
		$order->set_shipping_address_1( $sanitized_address['building'] );
		$order->set_shipping_address_2( trim( $sanitized_address['floor'] . ' ' . $sanitized_address['flat'] ) );
		$order->save();

		// Update subscription table delivery details if available.
		$subscription = null;
		$subscription_id = $order->get_meta( '_subscription_id' );
		if ( $subscription_id ) {
			$subscription = UserSubscription::get_by_id( (int) $subscription_id );
		}
		if ( ! $subscription ) {
			$subscription = UserSubscription::get_by_order_id( $order_id );
		}

		if ( $subscription ) {
			$subscription->set_delivery_details( $sanitized_address );
			$subscription->save();
		}

		wp_send_json_success( array(
			'message' => 'Delivery address updated successfully',
			'address' => $sanitized_address,
		) );
	}


	/**
	 * AJAX handler for saving menu selection (weekly recipes).
	 * 
	 * Stores selected recipes per day + meal in a separate custom table.
	 * Does NOT modify _meal_plan_state.
	 */
	public function handle_save_menu_selection(): void {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'devchefpress_subscription_nonce' ) ) {
			wp_send_json_error( [ 'message' => 'Security verification failed' ], 403 );
		}

		// Verify user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'User not logged in' ], 401 );
		}

		$user_id = get_current_user_id();

		// Parse request data
		$state = isset( $_POST['state'] ) ? json_decode( wp_unslash( $_POST['state'] ), true ) : [];
		$menu = isset( $_POST['menu'] ) ? json_decode( wp_unslash( $_POST['menu'] ), true ) : [];
		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$edit_order_id = isset( $_POST['edit_order_id'] ) ? intval( $_POST['edit_order_id'] ) : 0;

		// Use edit_order_id if available (editing existing recipe selection)
		if ( $edit_order_id > 0 ) {
			$order_id = $edit_order_id;
		}

		// Validate order_id
		if ( $order_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Order ID is required' ], 400 );
		}

		// Load order
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( [ 'message' => 'Order not found' ], 404 );
		}

		// Verify user owns this order
		if ( $order->get_customer_id() !== $user_id ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		// Get subscription ID and meal plan state
		$subscription_id = (int) $order->get_meta( '_subscription_id' );
		$meal_plan_state = $order->get_meta( '_meal_plan_state' );

		if ( ! $subscription_id || empty( $meal_plan_state ) ) {
			wp_send_json_error( [ 'message' => 'Subscription data not found' ], 404 );
		}

		// Extract plan details
		$start_date = isset( $meal_plan_state['startDate'] ) ? $meal_plan_state['startDate'] : '';
		$plan_duration = isset( $meal_plan_state['planDuration'] ) ? $meal_plan_state['planDuration'] : '1 Week';

		if ( empty( $start_date ) ) {
			wp_send_json_error( [ 'message' => 'Start date not found in subscription data' ], 400 );
		}

		// Check if current date is before start date
		$current_date = current_time( 'Y-m-d' );
		if ( $current_date < $start_date ) {
			wp_send_json_error( [ 'message' => 'You cannot select next week recipes before your subscription start date. Your subscription starts on ' . date( 'F j, Y', strtotime( $start_date ) ) . '.' ], 400 );
		}

		// Map plan duration to weeks
		$duration_map = [
			'1 Week'   => 1,
			'1 Month'  => 4,
			'3 Months' => 12,
			'6 Months' => 24,
		];

		$total_weeks = $duration_map[ $plan_duration ] ?? 1;

		// Calculate current week number (Sunday-Saturday alignment)
		try {
			$start = new \DateTime( $start_date );
			$today = new \DateTime( $current_date );

			// Get the Sunday of the start week
			$start_week_start = clone $start;
			$start_week_start->modify( 'last sunday' );

			// If start date itself is Sunday, keep it
			if ( 0 === (int) $start->format( 'w' ) ) {
				$start_week_start = clone $start;
			}

			// Get current week's Sunday
			$current_week_start = clone $today;
			$current_week_start->modify( 'last sunday' );

			if ( 0 === (int) $today->format( 'w' ) ) {
				$current_week_start = clone $today;
			}

			// Calculate difference in weeks
			$diff_days = (int) $start_week_start->diff( $current_week_start )->days;
			$week_number = floor( $diff_days / 7 ) + 1;

			// Ensure week number is within valid range
			$week_number = max( 1, min( $week_number, $total_weeks ) );

			// Calculate week start date (aligned to Sunday)
			$week_start_date_str = $current_week_start->format('Y-m-d');
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => 'Error calculating week number: ' . $e->getMessage() ], 500 );
		}

		// Get slots from state
		$slots = isset( $state['slots'] ) ? $state['slots'] : [];

		if ( empty( $slots ) ) {
			wp_send_json_error( [ 'message' => 'No recipes selected' ], 400 );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'devchefpress_weekly_recipes';

		// Delete existing records for this week before inserting new ones
		$wpdb->delete(
			$table_name,
			[
				'order_id'    => $order_id,
				'week_number' => $week_number,
			],
			[ '%d', '%d' ]
		);

		// Insert new recipe selections
		$insert_count = 0;
		foreach ( $slots as $slot ) {
			$day = isset( $slot['day'] ) ? sanitize_text_field( $slot['day'] ) : '';
			$meal = isset( $slot['meal'] ) ? sanitize_text_field( $slot['meal'] ) : '';

			if ( empty( $day ) || empty( $meal ) ) {
				continue;
			}

			$recipe_selected = isset( $slot['recipeSelected'] ) ? $slot['recipeSelected'] : [];

			if ( empty( $recipe_selected ) ) {
				continue;
			}

			$recipe_id = (int) $recipe_selected['id'];
			$recipe_title = sanitize_text_field( $recipe_selected['title'] ?? 'Unknown Recipe' );

			$insert_result = $wpdb->insert(
				$table_name,
				[
					'order_id'        => $order_id,
					'subscription_id' => $subscription_id,
					'week_number'     => $week_number,
					'week_start_date' => $week_start_date_str,
					'day'             => $day,
					'meal'            => $meal,
					'recipe_id'       => $recipe_id,
					'recipe_title'    => $recipe_title,
					'created_at'      => current_time( 'mysql' ),
				],
				[ '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
			);

			if ( $insert_result ) {
				$insert_count++;
			}
		}

		if ( $insert_count === 0 ) {
			wp_send_json_error( [ 'message' => 'Failed to save any recipes' ], 500 );
		}

		// Return success response
		wp_send_json_success( [
			'message'         => 'Weekly recipes saved successfully',
			'week_number'     => $week_number,
			'week_start_date' => $week_start_date_str,
			'recipes_saved'   => $insert_count,
		] );
	}
}
