<?php
declare( strict_types=1 );

namespace DevChefPress;

use DevChefPress\Admin\Admin;
use DevChefPress\Elementor\Elementor;
use DevChefPress\Frontend\Frontend;
use DevChefPress\Hooks\Loader;
use DevChefPress\Services\PluginSettings;

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
		$this->register_cpt();
		$this->load_textdomain();
		$this->register_rewrites();

		if ( is_admin() ) {
			new Admin( $this->loader );
		}

		new Frontend( $this->loader );
		new Elementor( $this->loader );
	}

	/**
	 * Register custom rewrites.
	 */
	private function register_rewrites(): void {
		$this->loader->add_action( 'init', $this, 'add_rewrite_rules' );
		$this->loader->add_filter( 'query_vars', $this, 'add_query_vars' );
		$this->loader->add_filter( 'template_include', $this, 'load_weekly_menu_template' );
	}

	/**
	 * Add rewrite rules for weekly menu and our plans.
	 */
	public function add_rewrite_rules(): void {
		add_rewrite_rule( '^weekly-menu/?$', 'index.php?weekly_menu=1', 'top' );
		add_rewrite_rule( '^our-plans/?$', 'index.php?our_plans=1', 'top' );
	}

	/**
	 * Add query vars.
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'weekly_menu';
		$vars[] = 'our_plans';
		return $vars;
	}

	/**
	 * Load the weekly menu or our plans template.
	 */
	public function load_weekly_menu_template( string $template ): string {
		if ( get_query_var( 'weekly_menu' ) ) {
			$custom_template = DEVCHEFPRESS_TEMPLATES_PATH . 'weekly-menu.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}
		if ( get_query_var( 'our_plans' ) ) {
			$custom_template = DEVCHEFPRESS_TEMPLATES_PATH . 'our-plans.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}
		return $template;
	}

	/**
	 * Register custom WooCommerce product type.
	 */
	private function register_product_type(): void {
		require_once DEVCHEFPRESS_PATH . 'app/WooCommerce/WC_Product_Recipe_product.php';

		$this->loader->add_action( 'init', $this, 'register_recipe_product_type' );
		$this->loader->add_filter( 'woocommerce_product_class', $this, 'map_recipe_product_class', 10, 2 );
	}

	/**
	 * Register recipe_product WooCommerce type.
	 */
	public function register_recipe_product_type(): void {
		// Taxonomy-backed terms make filtering easier and scalable.
		register_taxonomy( 'chefpress_recipe_tag', 'product', [
			'label'        => __( 'Recipe Tags', 'dev-chefpress' ),
			'hierarchical' => false,
			'show_ui'      => false,
			'rewrite'      => [ 'slug' => 'recipe-tag' ],
		] );

		register_taxonomy( 'chefpress_allergen_tag', 'product', [
			'label'        => __( 'Allergen Tags', 'dev-chefpress' ),
			'hierarchical' => false,
			'show_ui'      => false,
			'rewrite'      => [ 'slug' => 'allergen-tag' ],
		] );

		self::register_week_taxonomy();
		self::register_meal_type_taxonomy();
	}

	/**
	 * Register weekly assignment taxonomy.
	 */
	public static function register_week_taxonomy(): void {
		register_taxonomy( 'chefpress_week', 'product', [
			'label'        => __( 'Weekly Menu', 'dev-chefpress' ),
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'hierarchical' => false,
			'rewrite'      => [ 'slug' => 'chefpress-week', 'with_front' => false ],
		] );
	}

	/**
	 * Register meal type taxonomy.
	 */
	public static function register_meal_type_taxonomy(): void {
		register_taxonomy( 'chefpress_meal_type', 'product', [
			'label'        => __( 'Meal Types', 'dev-chefpress' ),
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'hierarchical' => false,
			'rewrite'      => [ 'slug' => 'chefpress-meal-type', 'with_front' => false ],
		] );
	}

	/**
	 * Create default week terms as part of activation.
	 */
	public static function create_default_week_terms(): void {
		self::register_week_taxonomy();

		$weeks = [
			__( 'Week 1', 'dev-chefpress' ),
			__( 'Week 2', 'dev-chefpress' ),
			__( 'Week 3', 'dev-chefpress' ),
			__( 'Week 4', 'dev-chefpress' ),
			__( 'Week 5', 'dev-chefpress' ),
			__( 'Week 6', 'dev-chefpress' ),
		];

		foreach ( $weeks as $week ) {
			if ( ! term_exists( $week, 'chefpress_week' ) ) {
				wp_insert_term( $week, 'chefpress_week', [ 'slug' => sanitize_title( $week ) ] );
			}
		}
	}

	/**
	 * Create default meal type terms as part of activation.
	 */
	public static function create_default_meal_type_terms(): void {
		self::register_meal_type_taxonomy();

		$meal_types = [
			__( 'Breakfast', 'dev-chefpress' ),
			__( 'Lunch', 'dev-chefpress' ),
			__( 'Dinner', 'dev-chefpress' ),
			__( 'Snacks', 'dev-chefpress' ),
		];

		foreach ( $meal_types as $meal_type ) {
			if ( ! term_exists( $meal_type, 'chefpress_meal_type' ) ) {
				wp_insert_term( $meal_type, 'chefpress_meal_type', [ 'slug' => sanitize_title( $meal_type ) ] );
			}
		}
	}


	/**
	 * Map custom product type to a real WC product class.
	 *
	 * @param string $classname Existing resolved class.
	 * @param string $product_type Product type slug.
	 */
	public function map_recipe_product_class( string $classname, string $product_type ): string {
		if ( 'recipe_product' === $product_type ) {
			return 'WC_Product_Recipe_product';
		}

		return $classname;
	}

	/**
	 * Register internal CPT (visible in admin menu). Settings live under this menu.
	 */
	private function register_cpt(): void {
		$this->loader->add_action( 'init', $this, 'register_chefpress_cpt' );
	}

	/**
	 * Reserved post type; enable show_ui later or attach to ChefPress menu.
	 */
	public function register_chefpress_cpt(): void {
		register_post_type(
			PluginSettings::CPT_SLUG,
			[
				'labels'              => [
					'name'          => __( 'ChefPress', 'dev-chefpress' ),
					'singular_name' => __( 'ChefPress Item', 'dev-chefpress' ),
					'menu_name'     => __( 'ChefPress', 'dev-chefpress' ),
					'add_new'       => __( 'Add New', 'dev-chefpress' ),
					'add_new_item'  => __( 'Add New Item', 'dev-chefpress' ),
					'edit_item'     => __( 'Edit Item', 'dev-chefpress' ),
				],
				'description'         => __( 'Reserved for future ChefPress functionality.', 'dev-chefpress' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-carrot',
				'menu_position'       => 56,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'capability_type'     => 'post',
				'capabilities' => [
					'create_posts' => 'do_not_allow',
				],
				'map_meta_cap'        => true,
				'supports'            => [ 'title' ],
				'has_archive'         => false,
			]
		);
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
