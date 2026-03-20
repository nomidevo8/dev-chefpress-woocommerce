<?php
/**
 * Uninstall Dev ChefPress for WooCommerce
 *
 * Removes all plugin data on uninstall if the option is enabled.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Only remove data if the user has opted in.
if ( ! get_option( 'devchefpress_delete_data_on_uninstall', false ) ) {
    return;
}

global $wpdb;

// Remove all ChefPress post meta.
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_chefpress_%'"
);

// Remove plugin options.
delete_option( 'devchefpress_delete_data_on_uninstall' );
delete_option( 'chefpress_plugin_settings' );

// Drop cache.
wp_cache_flush();
