<?php
declare( strict_types=1 );

namespace DevChefPress\Admin;

use DevChefPress\Hooks\Loader;
use DevChefPress\Services\PluginSettings;

/**
 * Settings screen as a submenu under the ChefPress CPT.
 */
final class SettingsPage {

	/** Submenu slug (appears as ?page=… under the CPT menu). */
	private const SUBMENU_SLUG = 'chefpress-settings';

	public function __construct( Loader $loader ) {
		$loader->add_action( 'admin_menu', $this, 'register_menu', 20 );
		$loader->add_action( 'admin_init', $this, 'maybe_save' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue' );
	}

	/**
	 * Parent menu is the CPT list: edit.php?post_type=chefpress
	 */
	private static function parent_slug(): string {
		return 'edit.php?post_type=' . PluginSettings::CPT_SLUG;
	}

	public static function settings_url(): string {
		return admin_url( self::parent_slug() . '&page=' . self::SUBMENU_SLUG );
	}

	public function register_menu(): void {
		add_submenu_page(
			self::parent_slug(),
			__( 'ChefPress Settings', 'dev-chefpress' ),
			__( 'Settings', 'dev-chefpress' ),
			'manage_woocommerce',
			self::SUBMENU_SLUG,
			[ $this, 'render' ]
		);
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
				self::settings_url()
			)
		);
		exit;
	}

	public function enqueue( string $hook ): void {
		// Screen id for CPT submenu: {post_type}_page_{submenu_slug}
		$expected = PluginSettings::CPT_SLUG . '_page_' . self::SUBMENU_SLUG;
		if ( $hook !== $expected && false === strpos( $hook, self::SUBMENU_SLUG ) ) {
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
					'addRow'    => __( 'Add nutrition row', 'dev-chefpress' ),
					'remove'    => __( 'Remove', 'dev-chefpress' ),
					'key'       => __( 'Key (ID)', 'dev-chefpress' ),
					'label'     => __( 'Label', 'dev-chefpress' ),
					'unit'      => __( 'Display unit', 'dev-chefpress' ),
					'addPreset' => __( 'Add to list', 'dev-chefpress' ),
					'presetPh'  => __( 'Type and press Enter…', 'dev-chefpress' ),
				],
				'pricing' => PluginSettings::get_presets_for_js(),
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
