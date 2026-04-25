<?php
/**
 * Plugin Name:       Dev ChefPress for WooCommerce
 * Description:       A professional SaaS-style Recipe Builder system for WooCommerce products. Transform any product into a structured, beautiful recipe page.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            DevTeam
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dev-chefpress
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 */

declare( strict_types=1 );

namespace DevChefPress;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	// For development, use timestamp to prevent caching.
	define( 'DEVCHEFPRESS_VERSION', time() );
} else {
	// For production, use plugin version or filemtime for cache busting.
	define( 'DEVCHEFPRESS_VERSION', '1.0.0.01' );
}
define( 'DEVCHEFPRESS_FILE', __FILE__ );
define( 'DEVCHEFPRESS_PATH', plugin_dir_path( __FILE__ ) );
define( 'DEVCHEFPRESS_URL', plugin_dir_url( __FILE__ ) );
define( 'DEVCHEFPRESS_ASSETS_URL', DEVCHEFPRESS_URL . 'assets/' );
define( 'DEVCHEFPRESS_RESOURCES_URL', DEVCHEFPRESS_URL . 'resources/' );
define( 'DEVCHEFPRESS_TEMPLATES_PATH', DEVCHEFPRESS_PATH . 'templates/' );

// Autoloader.
spl_autoload_register( function ( string $class ): void {
	$prefix   = 'DevChefPress\\';
	$base_dir = DEVCHEFPRESS_PATH . 'app/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * Declare WooCommerce HPOS compatibility.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

/**
 * Initialize the plugin after all plugins are loaded.
 */
add_action( 'plugins_loaded', function (): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function (): void {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'Dev ChefPress requires WooCommerce to be installed and active.', 'dev-chefpress' )
				. '</p></div>';
		} );
		return;
	}

	Plugin::instance();
} );

/**
 * Plugin activation hook.
 */
register_activation_hook( __FILE__, function (): void {
	// Ensure the weekly taxonomy is registered before creating terms.
	\DevChefPress\Plugin::create_default_week_terms();
	\DevChefPress\Plugin::create_default_meal_type_terms();
	
	// Create custom subscription database tables
	\DevChefPress\Models\UserSubscription::create_table();
	
	// Run all database migrations (including history table)
	\DevChefPress\Database\Migrations::run();
	
	flush_rewrite_rules();
} );

/**
 * Plugin deactivation hook.
 */
register_deactivation_hook( __FILE__, function (): void {
	flush_rewrite_rules();
} );

// require_once plugin_dir_path(__FILE__) . 'generate-test-recipes.php';