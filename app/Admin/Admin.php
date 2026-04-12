<?php
declare( strict_types=1 );

namespace DevChefPress\Admin;

use DevChefPress\Hooks\Loader;

/**
 * Class Admin
 *
 * Bootstraps all admin-side functionality.
 */
class Admin {

	private Loader $loader;
	private Assets $assets;
	private MetaBoxes $meta_boxes;
	private SaveHandler $save_handler;
	private SettingsPage $settings_page;
	private OrderDetails $order_details;

	public function __construct( Loader $loader ) {
		$this->loader         = $loader;
		$this->assets         = new Assets( $loader );
		$this->meta_boxes     = new MetaBoxes( $loader );
		$this->save_handler   = new SaveHandler( $loader );
		$this->settings_page  = new SettingsPage( $loader );
		$this->order_details  = new OrderDetails( $loader );

		$this->register_hooks();
	}

	private function register_hooks(): void {
		// Add custom product type to WooCommerce product type dropdown.
		$this->loader->add_filter( 'product_type_selector', $this, 'add_recipe_product_type' );

		// Add WooCommerce product tabs.
		$this->loader->add_filter( 'woocommerce_product_data_tabs', $this, 'add_recipe_tab', 10, 1 );

		// Add tab panel content.
		$this->loader->add_action( 'woocommerce_product_data_panels', $this, 'render_recipe_tab_panel' );

		// Show/hide standard tabs for recipe product.
		$this->loader->add_filter( 'woocommerce_product_data_tabs', $this, 'maybe_hide_standard_tabs', 99, 1 );
	}

	/**
	 * Add "Recipe" to product type selector.
	 */
	public function add_recipe_product_type( array $types ): array {
		$types['recipe_product'] = __( 'Recipe Product', 'dev-chefpress' );
		return $types;
	}

	/**
	 * Add ChefPress Recipe Builder tab to WooCommerce product data tabs.
	 */
	public function add_recipe_tab( array $tabs ): array {
		$tabs['chefpress_recipe'] = [
			'label'    => __( 'Recipe Builder', 'dev-chefpress' ),
			'target'   => 'chefpress_recipe_data',
			'class'    => [ 'show_if_recipe_product' ],
			'priority' => 80,
		];
		return $tabs;
	}

	/**
	 * Render the recipe tab panel content.
	 */
	public function render_recipe_tab_panel(): void {
		global $post;
		$recipe = new \DevChefPress\Models\Recipe( $post->ID );
		require DEVCHEFPRESS_PATH . 'app/Admin/views/tab-panel.php';
	}

	/**
	 * Optionally hide standard WooCommerce tabs for recipe products.
	 */
	public function maybe_hide_standard_tabs( array $tabs ): array {
		// We keep standard tabs visible so users can still set price etc.
		return $tabs;
	}
}
