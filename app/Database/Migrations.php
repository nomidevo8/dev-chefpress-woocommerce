<?php
declare( strict_types=1 );

namespace DevChefPress\Database;

/**
 * Database Migrations
 * 
 * Handles creation and management of custom database tables.
 */
class Migrations {

	/**
	 * Run all migrations
	 */
	public static function run(): void {
		self::create_user_subscriptions_table();
		self::create_subscription_history_table();
	}

	/**
	 * Create user subscriptions table
	 */
	public static function create_user_subscriptions_table(): void {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'chefpress_user_subscriptions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			parent_order_id BIGINT(20) UNSIGNED NOT NULL,
			plan_name VARCHAR(255) NOT NULL,
			meals_data LONGTEXT NOT NULL COMMENT 'JSON serialized meals data',
			delivery_details LONGTEXT NOT NULL COMMENT 'JSON serialized delivery info',
			original_price DECIMAL(10,2) NOT NULL,
			current_price DECIMAL(10,2) NOT NULL,
			total_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
			total_refund_pending DECIMAL(10,2) NOT NULL DEFAULT 0,
			status VARCHAR(50) NOT NULL DEFAULT 'active' COMMENT 'active, paused, cancelled',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			KEY user_id (user_id),
			KEY parent_order_id (parent_order_id),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create subscription history table
	 */
	public static function create_subscription_history_table(): void {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'chefpress_subscription_history';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			subscription_id BIGINT(20) UNSIGNED NOT NULL,
			order_id BIGINT(20) UNSIGNED NOT NULL,
			change_type VARCHAR(50) NOT NULL COMMENT 'edit_unpaid, price_increase, refund, price_decrease, etc',
			old_price DECIMAL(10,2) NOT NULL DEFAULT 0,
			new_price DECIMAL(10,2) NOT NULL DEFAULT 0,
			difference DECIMAL(10,2) NOT NULL DEFAULT 0,
			refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
			adjustment_order_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'For price_increase, the adjustment order ID',
			remaining_days INT UNSIGNED DEFAULT NULL COMMENT 'For refund calculations',
			total_days INT UNSIGNED DEFAULT NULL COMMENT 'For refund calculations',
			refund_status VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, approved, completed',
			notes LONGTEXT COMMENT 'Additional notes about the change',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			KEY subscription_id (subscription_id),
			KEY order_id (order_id),
			KEY change_type (change_type),
			KEY refund_status (refund_status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
