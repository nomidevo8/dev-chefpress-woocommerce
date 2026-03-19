<?php
declare( strict_types=1 );

namespace DevChefPress\Admin;

use DevChefPress\Hooks\Loader;

/**
 * Class MetaBoxes
 *
 * Registers and manages all meta box UI for the recipe builder.
 */
class MetaBoxes {

	public function __construct( Loader $loader ) {
		$loader->add_action( 'add_meta_boxes', $this, 'register_meta_boxes' );
	}

	/**
	 * Register all meta boxes.
	 */
	public function register_meta_boxes(): void {
		// The primary meta boxes are rendered inside the WooCommerce tab panel (tab-panel.php).
		// This method can be used to add additional standalone meta boxes if needed in future.
	}
}
