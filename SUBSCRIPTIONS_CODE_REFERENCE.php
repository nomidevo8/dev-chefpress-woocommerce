<?php
/**
 * QUICK REFERENCE: Using ChefPress Admin Subscriptions
 * 
 * This file demonstrates how to use the admin subscriptions page in your code.
 */

// ============================================================================
// 1. GET SUBSCRIPTIONS LIST PAGE URL
// ============================================================================

use DevChefPress\Admin\SubscriptionsAdminPage;

$subscriptions_list_url = SubscriptionsAdminPage::subscriptions_url();
// Output: /wp-admin/admin.php?post_type=chefpress&page=chefpress-subscriptions
?>

<!-- Link to subscriptions page -->
<a href="<?php echo esc_url( $subscriptions_list_url ); ?>">
    View All Subscriptions
</a>

<?php
// ============================================================================
// 2. GET SUBSCRIPTION DETAILS PAGE URL
// ============================================================================

$subscription_id = 123;
$details_url = SubscriptionsAdminPage::subscription_details_url( $subscription_id );
// Output: /wp-admin/admin.php?page=chefpress-subscription-details&id=123
?>

<!-- Link to specific subscription details -->
<a href="<?php echo esc_url( $details_url ); ?>">
    View Subscription #<?php echo intval( $subscription_id ); ?>
</a>

<?php
// ============================================================================
// 3. GET SUBSCRIPTION DATA (in admin or elsewhere)
// ============================================================================

use DevChefPress\Models\UserSubscription;

// Get by subscription ID
$subscription = UserSubscription::get_by_id( 123 );
if ( $subscription ) {
    echo 'User: ' . $subscription->get_user_id();
    echo 'Plan: ' . $subscription->get_plan_name();
    echo 'Price: $' . number_format( $subscription->get_current_price(), 2 );
    echo 'Status: ' . $subscription->get_status();
}

// Get by order ID
$subscription = UserSubscription::get_by_order_id( 456 );

// Get all subscriptions for user
$user_id = 789;
$subscriptions = UserSubscription::get_by_user( $user_id );
foreach ( $subscriptions as $sub ) {
    echo $sub->get_plan_name() . ': $' . number_format( $sub->get_current_price(), 2 );
}

// Get only active subscriptions for user
$active_subs = UserSubscription::get_active_by_user( $user_id );

// ============================================================================
// 4. AVAILABLE SUBSCRIPTION METHODS
// ============================================================================

/**
 * Getter Methods Available on UserSubscription:
 * 
 * - get_id(): int
 * - get_user_id(): int
 * - get_parent_order_id(): int
 * - get_plan_name(): string
 * - get_meals_data(): array
 * - get_delivery_details(): array
 * - get_original_price(): float
 * - get_current_price(): float
 * - get_total_paid(): float
 * - get_total_refund_pending(): float
 * - get_status(): string
 * - get_created_at(): string (DATETIME format: 2025-01-15 10:30:45)
 * - get_updated_at(): string (DATETIME format: 2025-01-15 10:30:45)
 */

// ============================================================================
// 5. DISPLAY IN TEMPLATE (Example)
// ============================================================================
?>

<div class="subscription-card">
    <h3><?php echo esc_html( $subscription->get_plan_name() ); ?></h3>
    
    <p>
        <strong>Price:</strong>
        $<?php echo number_format( $subscription->get_current_price(), 2 ); ?>
    </p>
    
    <p>
        <strong>Status:</strong>
        <span class="status-<?php echo esc_attr( $subscription->get_status() ); ?>">
            <?php echo esc_html( ucfirst( $subscription->get_status() ) ); ?>
        </span>
    </p>
    
    <p>
        <strong>Created:</strong>
        <?php echo esc_html( wp_date( 'M j, Y', strtotime( $subscription->get_created_at() ) ) ); ?>
    </p>
    
    <a href="<?php echo esc_url( SubscriptionsAdminPage::subscription_details_url( $subscription->get_id() ) ); ?>" 
       class="button">
        View in Admin
    </a>
</div>

<?php
// ============================================================================
// 6. CALCULATE WITH HISTORY (Using SubscriptionManager)
// ============================================================================

use DevChefPress\Services\SubscriptionManager;

$subscription_id = 123;
$base_price = 100.00;

// Calculate current price from all historical changes
$current_price = SubscriptionManager::calculate_current_price_from_history( 
    $subscription_id,
    $base_price
);

echo 'Current Price (with history): $' . number_format( $current_price, 2 );

// Get subscription history
$history = SubscriptionManager::get_history( $subscription_id );
foreach ( $history as $record ) {
    echo sprintf(
        '%s: %s → %s (Diff: %s)',
        $record['change_type'],
        $record['old_price'],
        $record['new_price'],
        $record['difference']
    );
}

// ============================================================================
// 7. ADMIN CAPABILITY CHECK
// ============================================================================

if ( current_user_can( 'manage_woocommerce' ) ) {
    // User can access admin subscriptions page
    echo '<a href="' . esc_url( $subscriptions_list_url ) . '">Admin Dashboard</a>';
}

// ============================================================================
// 8. CUSTOM QUERY EXAMPLE (if needed outside of SubscriptionsAdminPage)
// ============================================================================

global $wpdb;

// Get subscriptions with specific filters
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}chefpress_user_subscriptions 
         WHERE user_id = %d AND status = %s 
         ORDER BY created_at DESC",
        $user_id,
        'active'
    )
);

foreach ( $results as $row ) {
    $sub = UserSubscription::get_by_id( $row->id );
    // Use subscription object...
}

// ============================================================================
// 9. SHORTCODE EXAMPLE (Display subscriptions on frontend)
// ============================================================================

/**
 * Example shortcode to display user's subscriptions
 * Usage: [user_subscriptions]
 */

add_shortcode( 'user_subscriptions', function() {
    if ( ! is_user_logged_in() ) {
        return '<p>Please log in to view your subscriptions.</p>';
    }
    
    $user_id = get_current_user_id();
    $subscriptions = UserSubscription::get_by_user( $user_id );
    
    if ( empty( $subscriptions ) ) {
        return '<p>You have no subscriptions yet.</p>';
    }
    
    ob_start();
    ?>
    <div class="subscriptions-list">
        <?php foreach ( $subscriptions as $sub ) : ?>
            <div class="subscription-item">
                <h4><?php echo esc_html( $sub->get_plan_name() ); ?></h4>
                <p>$<?php echo number_format( $sub->get_current_price(), 2 ); ?></p>
                <p>Status: <?php echo esc_html( $sub->get_status() ); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
});

// ============================================================================
// 10. VERIFY INSTALLATION
// ============================================================================

/**
 * Check in WordPress admin:
 * 
 * 1. Left sidebar: ChefPress > Subscriptions (should appear)
 * 2. Click "Subscriptions" to see the list
 * 3. Should show current subscriptions from database
 * 4. Try filling filters and clicking "Filter"
 * 5. Click "View Details" on any subscription
 * 6. Should show full subscription details
 * 
 * If any issues:
 * - Check error logs: /wp-content/debug.log
 * - Verify table exists: wp_chefpress_user_subscriptions
 * - Check user has manage_woocommerce capability
 */
