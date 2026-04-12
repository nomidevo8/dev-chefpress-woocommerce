<?php

declare( strict_types=1 );

namespace DevChefPress\Elementor;

use DevChefPress\Hooks\Loader;

/**
 * Class Elementor
 *
 * Registers custom Elementor widgets and assets.
 */
class Elementor {

	private Loader $loader;

	public function __construct( Loader $loader ) {
		$this->loader = $loader;
		$this->register_hooks();
	}

	private function register_hooks(): void {
		$this->loader->add_action( 'elementor/widgets/register', $this, 'register_widgets' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'register_assets' );
		$this->loader->add_action( 'elementor/editor/after_enqueue_scripts', $this, 'register_assets' );
		$this->loader->add_action( 'elementor/preview/enqueue_styles', $this, 'register_assets' );
	}

	public function register_assets(): void {
		wp_register_style(
			'chefpress-elementor-profile-account',
			DEVCHEFPRESS_RESOURCES_URL . 'css/elementor-profile-account.css',
			[],
			DEVCHEFPRESS_VERSION
		);

		wp_register_script(
			'chefpress-elementor-profile-account',
			DEVCHEFPRESS_RESOURCES_URL . 'js/elementor-profile-account.js',
			[],
			DEVCHEFPRESS_VERSION,
			true
		);
	}

	public function register_widgets( $widgets_manager ): void {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		if ( ! class_exists( '\DevChefPress\Elementor\Widgets\ProfileAccount' ) ) {
			require_once DEVCHEFPRESS_PATH . 'app/Elementor/Widgets/ProfileAccount.php';
		}

		$widgets_manager->register( new Widgets\ProfileAccount() );
	}
}
