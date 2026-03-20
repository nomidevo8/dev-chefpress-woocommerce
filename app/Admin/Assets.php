<?php
declare( strict_types=1 );

namespace DevChefPress\Admin;

use DevChefPress\Hooks\Loader;

/**
 * Class Assets
 *
 * Loads admin-side CSS and JavaScript assets.
 */
class Assets {

	public function __construct( Loader $loader ) {
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_assets' );
	}

	/**
	 * Enqueue admin scripts and styles on product edit pages.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		global $post;
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		wp_enqueue_editor();

		// SortableJS from CDN.
		wp_enqueue_script(
			'sortablejs',
			'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
			[],
			'1.15.2',
			true
		);

		// Admin CSS.
		wp_enqueue_style(
			'dev-chefpress-admin',
			DEVCHEFPRESS_ASSETS_URL . 'css/admin.css',
			[ 'wp-color-picker' ],
			DEVCHEFPRESS_VERSION
		);

		// Admin JS.
		wp_enqueue_script(
			'dev-chefpress-admin',
			DEVCHEFPRESS_ASSETS_URL . 'js/admin.js',
			[ 'jquery', 'sortablejs', 'wp-color-picker', 'media-upload', 'editor' ],
			DEVCHEFPRESS_VERSION,
			true
		);

		// WordPress media library.
		wp_enqueue_media();

		// Localize script with data.
		wp_localize_script( 'dev-chefpress-admin', 'ChefPressAdmin', [
			'nonce'   => wp_create_nonce( 'dev_chefpress_nonce' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'stepEditorSettings' => [
				'tinymce'       => [
					'wpautop'       => true,
					'resize'        => true,
					'branding'      => false,
					'height'        => 200,
					'toolbar1'      => 'bold,italic,bullist,numlist,link,unlink',
					'toolbar2'      => '',
					'block_formats' => 'Paragraph=p;Heading 3=h3;Heading 4=h4',
				],
				'quicktags'     => true,
				'mediaButtons'  => false,
			],
			'strings' => [
				'addStep'         => __( 'Add Step', 'dev-chefpress' ),
				'addGroup'        => __( 'Add Ingredient Group', 'dev-chefpress' ),
				'addIngredient'   => __( 'Add Ingredient', 'dev-chefpress' ),
				'removeConfirm'   => __( 'Are you sure you want to remove this item?', 'dev-chefpress' ),
				'selectImage'     => __( 'Select Image', 'dev-chefpress' ),
				'useImage'        => __( 'Use this image', 'dev-chefpress' ),
				'noTitle'         => __( '(No title)', 'dev-chefpress' ),
				'stepLabel'       => __( 'Step', 'dev-chefpress' ),
				'groupLabel'      => __( 'Group', 'dev-chefpress' ),
				'duplicated'      => __( 'Duplicated!', 'dev-chefpress' ),
			],
		] );
	}
}
