<?php
/**
 * My Subscriptions Template
 *
 * Displays user's meal plan subscriptions in a modern card-based layout
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get subscriptions from the custom subscription table by current user
$subscriptions = array();
if ( class_exists( '\DevChefPress\Models\UserSubscription' ) ) {
    $subscriptions = \DevChefPress\Models\UserSubscription::get_by_user( $user_id );
    if ( ! is_array( $subscriptions ) ) {
        $subscriptions = array();
    }
}

get_header();
?>

<div class="devchefpress-my-subscriptions">
    <div class="devchefpress-container">
        <div class="devchefpress-header">
            <h1 class="devchefpress-title">My Subscriptions</h1>
            <p class="devchefpress-subtitle">Manage your meal plan subscriptions</p>
        </div>

        <?php if (empty($subscriptions)): ?>
            <!-- Empty State -->
            <div class="devchefpress-empty-state">
                <div class="devchefpress-empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3>You don't have any subscriptions yet</h3>
                <p>Start your healthy journey by creating your first meal plan subscription.</p>
                <a href="<?php echo esc_url(home_url('/our-plans')); ?>" class="devchefpress-btn devchefpress-btn-primary">
                    Create Subscription
                </a>
            </div>
        <?php else: ?>
            <!-- Subscriptions Table -->
            <div class="devchefpress-subscriptions-container">
                <div class="devchefpress-subscriptions-table-wrapper">
                    <table class="devchefpress-subscriptions-table">
                        <thead>
                            <tr>
                                <th>Plan Name</th>
                                <th>Price</th>
                                <th>Start Date</th>
                                <th class="devchefpress-actions-header">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $subscription):
                                $order_id = $subscription->get_parent_order_id();
                                $order = $order_id ? wc_get_order( $order_id ) : null;

                                $state_data = null;
                                $pricing_data = null;

                                if ( $order ) {
                                    $meal_plan_state = $order->get_meta('_meal_plan_state');
                                    $meal_plan_pricing = $order->get_meta('_meal_plan_pricing');

                                    $state_data = is_array($meal_plan_state) ? $meal_plan_state : json_decode((string) $meal_plan_state, true);
                                    $pricing_data = is_array($meal_plan_pricing) ? $meal_plan_pricing : json_decode((string) $meal_plan_pricing, true);
                                }

                                $plan_name = $subscription->get_plan_name() ?: 'Meal Plan';
                                $status = ucfirst( $subscription->get_status() ?? 'active' );
                                $start_date = $subscription->get_delivery_details()['startDate'] ?? $state_data['startDate'] ?? '';
                                $formatted_start = $start_date ? date('M j, Y', strtotime($start_date)) : '-';

                                $total_price = $pricing_data['total'] ?? $subscription->get_current_price();
                                $formatted_price = '$' . number_format( (float) $total_price, 2 );
                                $disabled_attr = $order_id ? '' : 'disabled';
                            ?>
                                <tr class="devchefpress-subscription-row" data-order-id="<?php echo esc_attr( $order_id ); ?>">
                                    <td class="devchefpress-plan-cell">
                                        <div class="devchefpress-plan-info">
                                            <strong><?php echo esc_html( $plan_name ); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html( $formatted_price ); ?></td>
                                    <td><?php echo esc_html( $formatted_start ); ?></td>
                                    <td class="devchefpress-actions-cell">
                                        <div class="devchefpress-action-icons">
                                            <button class="devchefpress-action-icon devchefpress-view-details" data-order-id="<?php echo esc_attr( $order_id ); ?>" title="View Details" <?php echo $disabled_attr; ?>>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                            <button class="devchefpress-action-icon devchefpress-edit-subscription" data-order-id="<?php echo esc_attr( $order_id ); ?>" title="Edit Subscription" <?php echo $disabled_attr; ?>>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                            <button class="devchefpress-action-icon devchefpress-book-week" data-order-id="<?php echo esc_attr( $order_id ); ?>" title="Book Current Week" <?php echo $disabled_attr; ?>>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for subscription details -->
<div id="devchefpressSubscriptionModal" class="devchefpress-modal-overlay">
    <div class="devchefpress-modal-container">
        <button class="devchefpress-modal-close" onclick="document.getElementById('devchefpressSubscriptionModal').style.display='none'">
            ✖
        </button>
        <div id="devchefpressSubscriptionContent" class="devchefpress-modal-content">
            Loading...
        </div>
    </div>
</div>

<?php
// Enqueue styles and scripts
$plugin_dir = plugin_dir_url(dirname(__FILE__));

wp_enqueue_style('devchefpress-my-subscriptions', $plugin_dir . 'resources/css/my-subscriptions.css');

// Enqueue Leaflet CSS and JS for maps
wp_enqueue_style('leaflet-css', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css');
wp_enqueue_script('leaflet-js', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', array(), '1.9.4', true);

// Enqueue jsPDF for PDF export
wp_enqueue_script('jspdf-js', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array(), '2.5.1', true);

// Enqueue main subscription script
wp_enqueue_script('devchefpress-my-subscriptions', $plugin_dir . 'resources/js/my-subscriptions.js', array('jquery', 'leaflet-js', 'jspdf-js'), '1.0.0', true);

// Localize script with both AJAX config and edit subscription URL
wp_localize_script('devchefpress-my-subscriptions', 'devchefpress_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('devchefpress_subscription_nonce'),
    'edit_our_plans_url' => esc_url(home_url('/our-plans'))
));

get_footer();