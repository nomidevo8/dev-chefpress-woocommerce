<?php
declare( strict_types=1 );

namespace DevChefPress\Admin;

use DevChefPress\Hooks\Loader;
use DevChefPress\Services\PluginSettings;

/**
 * Top-level ChefPress admin menu and Settings screen.
 */
final class SettingsPage {

	private const MENU_SLUG = 'dev-chefpress';

	public function __construct( Loader $loader ) {
		$loader->add_action( 'admin_menu', $this, 'register_menu', 9 );
		$loader->add_action( 'admin_init', $this, 'maybe_save' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue' );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'ChefPress', 'dev-chefpress' ),
			__( 'ChefPress', 'dev-chefpress' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			[ $this, 'render' ],
			'dashicons-carrot',
			56
		);

		// Future submenus: add_submenu_page( self::MENU_SLUG, ... );
	}

	public function maybe_save(): void {
		if ( ! isset( $_POST['chefpress_settings_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'chefpress_settings_save', 'chefpress_settings_nonce' );

		$payload = isset( $_POST['chefpress_settings'] ) && is_array( $_POST['chefpress_settings'] )
			? wp_unslash( $_POST['chefpress_settings'] )
			: [];

		PluginSettings::save_from_post( $payload );

		wp_safe_redirect(
			add_query_arg(
				'chefpress_saved',
				'1',
				admin_url( 'admin.php?page=' . self::MENU_SLUG )
			)
		);
		exit;
	}

	public function enqueue( string $hook ): void {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'dev-chefpress-admin',
			DEVCHEFPRESS_ASSETS_URL . 'css/admin.css',
			[],
			DEVCHEFPRESS_VERSION
		);

		wp_enqueue_script(
			'dev-chefpress-settings',
			DEVCHEFPRESS_ASSETS_URL . 'js/settings-admin.js',
			[],
			DEVCHEFPRESS_VERSION,
			true
		);

		wp_localize_script(
			'dev-chefpress-settings',
			'ChefPressSettings',
			[
				'strings' => [
					'addRow'       => __( 'Add nutrition row', 'dev-chefpress' ),
					'remove'       => __( 'Remove', 'dev-chefpress' ),
					'key'          => __( 'Key (ID)', 'dev-chefpress' ),
					'label'        => __( 'Label', 'dev-chefpress' ),
					'unit'         => __( 'Display unit', 'dev-chefpress' ),
					'addPreset'    => __( 'Add to list', 'dev-chefpress' ),
					'presetPh'     => __( 'Type and press Enter…', 'dev-chefpress' ),
				],
			]
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dev-chefpress' ) );
		}

		$settings = PluginSettings::all();
		$saved    = isset( $_GET['chefpress_saved'] ) && '1' === $_GET['chefpress_saved'];

		require DEVCHEFPRESS_PATH . 'app/Admin/views/settings-page.php';
	}
}
