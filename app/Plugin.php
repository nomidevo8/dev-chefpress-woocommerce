<?php
declare( strict_types=1 );

namespace DevChefPress;

use DevChefPress\Admin\Admin;
use DevChefPress\Frontend\Frontend;
use DevChefPress\Hooks\Loader;

/**
 * Class Plugin
 *
 * Main plugin singleton. Bootstraps all modules.
 */
final class Plugin {

	/** @var Plugin|null */
	private static ?Plugin $instance = null;

	/** @var Loader */
	private Loader $loader;

	/**
	 * Private constructor — use Plugin::instance().
	 */
	private function __construct() {
		$this->loader = new Loader();
		$this->init();
		$this->loader->run();
	}

	/**
	 * Get or create the singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize all modules.
	 */
	private function init(): void {
		$this->register_product_type();
		$this->load_textdomain();

		if ( is_admin() ) {
			new Admin( $this->loader );
		}

		new Frontend( $this->loader );
	}

	/**
	 * Register custom WooCommerce product type.
	 */
	private function register_product_type(): void {
		$this->loader->add_action( 'init', $this, 'register_recipe_product_type' );
	}

	/**
	 * Register recipe_product WooCommerce type.
	 */
	public function register_recipe_product_type(): void {
		// Register taxonomy for recipe tags.
		register_taxonomy( 'chefpress_recipe_tag', 'product', [
			'label'        => __( 'Recipe Tags', 'dev-chefpress' ),
			'hierarchical' => false,
			'show_ui'      => false, // Managed via meta box.
			'rewrite'      => [ 'slug' => 'recipe-tag' ],
		] );
	}

	/**
	 * Load plugin text domain.
	 */
	private function load_textdomain(): void {
		$this->loader->add_action( 'init', $this, 'load_plugin_textdomain' );
	}

	/**
	 * Load translations.
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'dev-chefpress',
			false,
			dirname( plugin_basename( DEVCHEFPRESS_FILE ) ) . '/languages'
		);
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}
}
