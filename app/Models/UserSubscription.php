<?php
declare( strict_types=1 );

namespace DevChefPress\Models;

/**
 * UserSubscription Model
 * 
 * Handles all interactions with custom subscription database table.
 * This is separate from WooCommerce orders and manages subscription logic independently.
 */
class UserSubscription {

	private int $id;
	private int $user_id;
	private int $parent_order_id;
	private string $plan_name;
	private array $meals_data;
	private array $delivery_details;
	private float $original_price;
	private float $current_price;
	private float $total_paid;
	private float $total_refund_pending;
	private string $status;
	private string $created_at;
	private string $updated_at;

	/**
	 * Database table name
	 */
	public static function  get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'chefpress_user_subscriptions';
	}

	/**
	 * Create database table on plugin activation
	 */
	public static function create_table(): void {
		global $wpdb;
		$table_name      = self::get_table_name();
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
	 * Create a subscription record.
	 */
	public static function create_subscription_record( array $data ): int|\WP_Error {
		global $wpdb;

		$user_id = isset( $data['user_id'] ) ? intval( $data['user_id'] ) : 0;
		if ( $user_id <= 0 ) {
			return new \WP_Error( 'invalid_user_id', 'A valid user ID is required.' );
		}

		$plan_name = sanitize_text_field( $data['plan_name'] ?? '' );
		if ( empty( $plan_name ) ) {
			return new \WP_Error( 'invalid_plan_name', 'A valid plan name is required.' );
		}

		$meals_data = wp_json_encode( $data['meals_data'] ?? array() );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error( 'json_encode_failed', 'Unable to encode meals data.' );
		}

		$delivery_details = wp_json_encode( $data['delivery_details'] ?? array() );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error( 'json_encode_failed', 'Unable to encode delivery details.' );
		}

		$inserted = $wpdb->insert(
			self::get_table_name(),
			array(
				'user_id'              => $user_id,
				'parent_order_id'      => 0,
				'plan_name'            => $plan_name,
				'meals_data'           => $meals_data,
				'delivery_details'     => $delivery_details,
				'original_price'       => floatval( $data['original_price'] ?? 0 ),
				'current_price'        => floatval( $data['current_price'] ?? 0 ),
				'total_paid'           => floatval( $data['total_paid'] ?? 0 ),
				'total_refund_pending' => floatval( $data['total_refund_pending'] ?? 0 ),
				'status'               => sanitize_text_field( $data['status'] ?? 'active' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s' )
		);

		if ( false === $inserted ) {
			return new \WP_Error( 'db_insert_error', 'Unable to store meal plan subscription.' );
		}

		return intval( $wpdb->insert_id );
	}

	/**
	 * Update subscription parent_order_id and prevent duplicate linking.
	 */
	public static function link_subscription_to_order( int $subscription_id, int $order_id ): bool|\WP_Error {
		global $wpdb;

		$subscription_id = intval( $subscription_id );
		$order_id        = intval( $order_id );

		if ( $subscription_id <= 0 || $order_id <= 0 ) {
			return new \WP_Error( 'invalid_ids', 'Valid subscription and order IDs are required.' );
		}

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM " . self::get_table_name() . " WHERE id = %d AND parent_order_id = %d",
				$subscription_id,
				$order_id
			)
		);

		if ( $existing ) {
			return true;
		}

		$updated = $wpdb->update(
			self::get_table_name(),
			array( 'parent_order_id' => $order_id ),
			array( 'id' => $subscription_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new \WP_Error( 'db_update_error', 'Unable to link subscription to order.' );
		}

		return true;
	}

	/**
	 * Complete a pending refund without modifying WooCommerce order state.
	 *
	 * @param int $subscription_id
	 * @return float|\WP_Error Previous pending refund amount or WP_Error on failure.
	 */
	public static function complete_pending_refund( int $subscription_id ): float|\WP_Error {
		global $wpdb;

		$subscription = self::get_by_id( $subscription_id );
		if ( ! $subscription ) {
			return new \WP_Error( 'subscription_not_found', 'Subscription not found.' );
		}

		$pending_amount = $subscription->get_total_refund_pending();
		if ( $pending_amount <= 0 ) {
			return 0.0;
		}

		$updated = $wpdb->update(
			self::get_table_name(),
			array( 'total_refund_pending' => 0 ),
			array( 'id' => $subscription_id ),
			array( '%f' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new \WP_Error( 'db_update_error', 'Unable to mark refund as completed.' );
		}

		return $pending_amount;
	}

	/**
	 * Checks whether an order is a meal plan order.
	 */
	public static function is_meal_plan_order( int $order_id ): bool {
		return get_post_meta( intval( $order_id ), '_order_type', true ) === 'meal_plan';
	}

	/**
	 * Save subscription to database
	 */
	public function save(): bool {
		global $wpdb;
		$table_name = self::get_table_name();

		$data = array(
			'user_id'              => $this->user_id,
			'parent_order_id'      => $this->parent_order_id,
			'plan_name'            => $this->plan_name,
			'meals_data'           => wp_json_encode( $this->meals_data ),
			'delivery_details'     => wp_json_encode( $this->delivery_details ),
			'original_price'       => $this->original_price,
			'current_price'        => $this->current_price,
			'total_paid'           => $this->total_paid,
			'total_refund_pending' => $this->total_refund_pending,
			'status'               => $this->status,
		);

		$format = array( '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s' );

		if ( $this->id ) {
			// Update existing
			return (bool) $wpdb->update( $table_name, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
		} else {
			// Insert new
			$result = $wpdb->insert( $table_name, $data, $format );
			if ( $result ) {
				$this->id = $wpdb->insert_id;
			}
			return (bool) $result;
		}
	}

	/**
	 * Get subscription by ID
	 */
	public static function get_by_id( int $id ): ?self {
		global $wpdb;
		$table_name = self::get_table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id )
		);

		if ( ! $row ) {
			return null;
		}

		return self::from_db_row( $row );
	}

	/**
	 * Get subscription by parent order ID
	 */
	public static function get_by_order_id( int $order_id ): ?self {
		global $wpdb;
		$table_name = self::get_table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE parent_order_id = %d", $order_id )
		);

		if ( ! $row ) {
			return null;
		}

		return self::from_db_row( $row );
	}

	/**
	 * Get all subscriptions for a user
	 */
	public static function get_by_user( int $user_id ): array {
		global $wpdb;
		$table_name = self::get_table_name();

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d ORDER BY updated_at DESC", $user_id )
		);

		return array_map( fn( $row ) => self::from_db_row( $row ), $rows );
	}

	/**
	 * Get active subscriptions for a user
	 */
	public static function get_active_by_user( int $user_id ): array {
		global $wpdb;
		$table_name = self::get_table_name();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE user_id = %d AND status = 'active' ORDER BY updated_at DESC",
				$user_id
			)
		);

		return array_map( fn( $row ) => self::from_db_row( $row ), $rows );
	}

	/**
	 * Create from database row
	 */
	private static function from_db_row( object $row ): self {
		$subscription = new self();
		$subscription->id                   = (int) $row->id;
		$subscription->user_id              = (int) $row->user_id;
		$subscription->parent_order_id      = (int) $row->parent_order_id;
		$subscription->plan_name            = $row->plan_name;
		$subscription->meals_data           = json_decode( $row->meals_data, true ) ?? array();
		$subscription->delivery_details     = json_decode( $row->delivery_details, true ) ?? array();
		$subscription->original_price       = (float) $row->original_price;
		$subscription->current_price        = (float) $row->current_price;
		$subscription->total_paid           = (float) $row->total_paid;
		$subscription->total_refund_pending = (float) $row->total_refund_pending;
		$subscription->status               = $row->status;
		$subscription->created_at           = $row->created_at;
		$subscription->updated_at           = $row->updated_at;

		return $subscription;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->status             = 'active';
		$this->total_paid         = 0;
		$this->total_refund_pending = 0;
		$this->meals_data         = array();
		$this->delivery_details   = array();
	}

	// GETTERS
	public function get_id(): int {
		return $this->id ?? 0;
	}

	public function get_user_id(): int {
		return $this->user_id;
	}

	public function get_parent_order_id(): int {
		return $this->parent_order_id;
	}

	public function get_plan_name(): string {
		return $this->plan_name ?? '';
	}

	public function get_meals_data(): array {
		return $this->meals_data;
	}

	public function get_delivery_details(): array {
		return $this->delivery_details;
	}

	public function get_original_price(): float {
		return $this->original_price ?? 0;
	}

	public function get_current_price(): float {
		// Get base price from order if available, otherwise use stored original_price
		$base_price = $this->get_base_price_from_order();
		
		// Calculate current price by applying all history changes
		return \DevChefPress\Services\SubscriptionManager::calculate_current_price_from_history(
			$this->id,
			$base_price
		);
	}

	/**
	 * Get base price from parent order, fallback to stored original_price
	 */
	private function get_base_price_from_order(): float {
		if ( $this->parent_order_id > 0 ) {
			$order = wc_get_order( $this->parent_order_id );
			if ( $order ) {
				return (float) $order->get_total();
			}
		}
		
		return $this->original_price;
	}

	public function get_total_paid(): float {
		return $this->total_paid;
	}

	public function get_total_refund_pending(): float {
		return $this->total_refund_pending;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_created_at(): string {
		return $this->created_at ?? '';
	}

	public function get_updated_at(): string {
		return $this->updated_at ?? '';
	}

	// SETTERS
	public function set_user_id( int $user_id ): self {
		$this->user_id = $user_id;
		return $this;
	}

	public function set_parent_order_id( int $order_id ): self {
		$this->parent_order_id = $order_id;
		return $this;
	}

	public function set_plan_name( string $name ): self {
		$this->plan_name = $name;
		return $this;
	}

	public function set_meals_data( array $data ): self {
		$this->meals_data = $data;
		return $this;
	}

	public function set_delivery_details( array $details ): self {
		$this->delivery_details = $details;
		return $this;
	}

	public function set_original_price( float $price ): self {
		$this->original_price = $price;
		return $this;
	}

	public function set_current_price( float $price ): self {
		$this->current_price = $price;
		return $this;
	}

	public function set_total_paid( float $paid ): self {
		$this->total_paid = $paid;
		return $this;
	}

	public function add_total_paid( float $amount ): self {
		$this->total_paid += $amount;
		return $this;
	}

	public function set_total_refund_pending( float $refund ): self {
		$this->total_refund_pending = $refund;
		return $this;
	}

	public function add_refund_pending( float $amount ): self {
		$this->total_refund_pending += $amount;
		return $this;
	}

	public function set_status( string $status ): self {
		if ( in_array( $status, array( 'active', 'paused', 'cancelled' ), true ) ) {
			$this->status = $status;
		}
		return $this;
	}

	/**
	 * Verify user owns this subscription
	 */
	public function verify_ownership( int $user_id ): bool {
		return $this->user_id === $user_id;
	}
}
