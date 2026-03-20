<?php
declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Product_Recipe_product' ) ) {
	/**
	 * WooCommerce product class for recipe products.
	 *
	 * WooCommerce derives this classname from product type slug "recipe_product".
	 */
	class WC_Product_Recipe_product extends WC_Product_Simple {
		public function get_type(): string {
			return 'recipe_product';
		}
	}
}
