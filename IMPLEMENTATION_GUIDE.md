# Implementation Guide - Subscription Edit System

## Quick Start

### 1. Activation

Once the plugin is reactivated or installed:
- Database tables are automatically created
- Migrations are run via `Migrations::run()`
- Both tables created:
  - `wp_chefpress_user_subscriptions` (existing, unchanged)
  - `wp_chefpress_subscription_history` (new)

### 2. Test the Implementation

#### Test 2A: Unpaid Order Edit
1. Create a meal plan order but DON'T pay
2. Go to `/our-plans/?edit_order=ORDER_ID`
3. Change some meals/dates
4. Click Next → Payment
5. **Expected:** Same order updated with new items, can pay again

**Check in Database:**
```sql
SELECT * FROM wp_chefpress_subscription_history WHERE change_type = 'edit_unpaid' ORDER BY created_at DESC LIMIT 1;
```

#### Test 2B: Paid Order - Price Increase
1. Create and PAY for a meal plan order
2. Go to `/our-plans/?edit_order=ORDER_ID`
3. Add more meals (increase price to $50 more)
4. Click Next → Payment
5. **Expected:** New "adjustment" order for $50 shown

**Check in Database:**
```sql
-- Original order should be unchanged
SELECT * FROM wp_posts WHERE ID = {original_order_id};

-- Adjustment order should exist
SELECT post_id FROM wp_postmeta WHERE meta_key = '_parent_order_id' AND meta_value = {original_order_id};

-- History should record price_increase
SELECT * FROM wp_chefpress_subscription_history WHERE change_type = 'price_increase' ORDER BY created_at DESC LIMIT 1;
```

#### Test 2C: Paid Order - Price Decrease
1. Create and PAY for a meal plan order (1 Month plan = 30 days)
2. Go to `/our-plans/?edit_order=ORDER_ID`
3. Remove some meals (decrease price by $50)
4. Click Next
5. **Expected:** No payment flow, shows refund message: "$25 refund pending (15 remaining days)"

**Calculation Check:**
```
Remaining days: 15
Total days: 30
Refund ratio: 15/30 = 0.5
Refund amount: $50 * 0.5 = $25
```

**Check in Database:**
```sql
-- History records refund
SELECT * FROM wp_chefpress_subscription_history WHERE change_type = 'refund' ORDER BY created_at DESC LIMIT 1;

-- Subscription shows pending refund
SELECT total_refund_pending FROM wp_chefpress_user_subscriptions WHERE id = {subscription_id};
```

---

## Class Method Reference

### SubscriptionManager

#### `get_payment_status($order) → string`
Returns 'paid' or 'unpaid' based on order status

**Example:**
```php
$order = wc_get_order(123);
$status = SubscriptionManager::get_payment_status($order);
// 'paid' if status is processing/completed
// 'unpaid' if status is pending/failed
```

#### `get_adjustment_orders(int $order_id) → array`
Returns array of adjustment order IDs linked to original order

**Example:**
```php
$adj_orders = SubscriptionManager::get_adjustment_orders(123);
// [456, 457] if two adjustments exist
```

#### `calculate_smart_refund(float $difference, array $delivery_details, array $state) → array`
Calculates pro-rata refund based on remaining subscription time

**Parameters:**
- `$difference`: Absolute price difference (positive number)
- `$delivery_details`: From subscription->get_delivery_details()
- `$state`: Current state array with planDuration

**Returns:**
```php
[
    'refund_amount' => 25.00,         // Calculated refund
    'remaining_days' => 15,           // Days until subscription ends
    'total_days' => 30,               // Total subscription days
    'refund_ratio' => 0.5,            // Percentage of difference to refund
    'reason' => 'pro_rata_refund'     // Reason code
]
```

**Example:**
```php
$refund = SubscriptionManager::calculate_smart_refund(
    50.00,
    ['startDate' => '2026-04-16'],
    ['planDuration' => '1 Month']
);
// If today is 2026-05-01, remaining ~15 days
// Returns: refund_amount = 25.00
```

#### `handle_unpaid_edit(int $order_id, array $state, array $new_pricing) → array|WP_Error`
Updates unpaid order with new state

**Example:**
```php
$result = SubscriptionManager::handle_unpaid_edit(
    123,
    $new_state_data,
    $new_pricing_data
);

// Returns: ['order_id' => 123, 'price_difference' => -10.50, ...]
// OR: WP_Error if failed
```

#### `create_adjustment_order(int $order_id, int $subscription_id, float $difference, array $new_pricing) → int|WP_Error`
Creates new adjustment order for price increase

**Example:**
```php
$adj_order_id = SubscriptionManager::create_adjustment_order(
    123,                    // Original order ID
    45,                     // Subscription ID
    50.00,                  // Price difference
    $new_pricing_data
);

// Returns: 456 (new adjustment order ID)
// Automatically links with _parent_order_id meta
```

#### `handle_price_decrease(int $subscription_id, int $order_id, float $difference, array $delivery_details, array $state) → array|WP_Error`
Records pending refund for price decrease

**Example:**
```php
$result = SubscriptionManager::handle_price_decrease(
    45,                          // Subscription ID
    123,                         // Order ID
    -50.00,                      // Price difference (negative)
    ['startDate' => '2026-04-16'],
    ['planDuration' => '1 Month']
);

// Returns: ['refund_amount' => 25.00, 'remaining_days' => 15, ...]
```

#### `insert_history(array $data) → int|WP_Error`
Inserts change record in history table

**Required fields in $data:**
```php
[
    'subscription_id' => 45,
    'order_id' => 123,
    'change_type' => 'price_increase',  // Required
    'old_price' => 100.00,              // Optional
    'new_price' => 150.00,              // Optional
    'difference' => 50.00,              // Optional
    'refund_amount' => 0,               // Optional
    'adjustment_order_id' => 456,       // Optional
    'remaining_days' => 15,             // Optional
    'total_days' => 30,                 // Optional
    'refund_status' => 'pending',       // Optional
    'notes' => 'User reduced meals'     // Optional
]
```

**Example:**
```php
$history_id = SubscriptionManager::insert_history([
    'subscription_id' => 45,
    'order_id' => 123,
    'change_type' => 'edit_unpaid',
    'old_price' => 100.00,
    'new_price' => 95.00,
    'difference' => -5.00,
    'notes' => 'Removed breakfast meal'
]);

// Returns: 789 (history record ID)
```

#### `get_history(int $subscription_id) → array`
Retrieves all changes for a subscription

**Example:**
```php
$history = SubscriptionManager::get_history(45);

// Returns:
[
    [
        'id' => 789,
        'subscription_id' => 45,
        'order_id' => 123,
        'change_type' => 'edit_unpaid',
        'old_price' => '100.00',
        'new_price' => '95.00',
        'difference' => '-5.00',
        'refund_amount' => '0.00',
        'adjustment_order_id' => null,
        'remaining_days' => null,
        'total_days' => null,
        'refund_status' => 'pending',
        'notes' => 'Removed breakfast meal',
        'created_at' => '2026-04-16 10:30:00',
        'updated_at' => '2026-04-16 10:30:00'
    ],
    // ... more records
]
```

#### `get_main_subscription_orders(int $user_id) → array`
Gets main meal plan orders (excludes adjustments)

**Example:**
```php
$orders = SubscriptionManager::get_main_subscription_orders(5);
// Returns: [123, 124, 125] (excludes adjustment orders)
```

---

### NotificationService

#### `notify_edit_unpaid(int $user_id, int $order_id, float $difference) → void`
Sends user notification for unpaid order edit

#### `notify_price_increase(int $user_id, int $adjustment_order_id, float $amount) → void`
Sends user notification with payment link for price increase

#### `notify_refund_pending(int $user_id, float $refund_amount, int $remaining_days) → void`
Sends user notification about pending refund

#### `notify_admin(int $order_id, string $type) → void`
Where $type is 'price_increase' or 'refund_required'

#### `get_user_notices(int $user_id, bool $clear = false) → array`
Retrieves user notifications (optionally clears them)

---

### SubscriptionDetailsHelper

#### `build_subscription_details_html(array $data, int $subscription_id, int $order_id) → string`
Generates complete HTML with all sections including history

**Section Generated:**
- Personal Information (goal, age, weight, height, etc.)
- Delivery Address
- Meal Plan Schedule (grouped by day)
- Pricing Summary
- Change History (all edits)
- Pending Refunds (if any)
- Adjustment Orders (if any)

---

## Frontend Integration

### JavaScript - my-subscriptions.js

#### Updated "View Details" Handler
```javascript
$(document).on('click', '.devchefpress-view-details', function(e) {
    // AJAX call to get subscription details
    // Now receives HTML from backend
    // Falls back to JavaScript builder if HTML not provided
});
```

#### Features:
- Uses backend HTML if available
- Falls back to JS builder for compatibility
- Initializes maps if coordinates present
- Handles close/cleanup

---

## Database Queries

### Find all price increases for a user
```sql
SELECT 
    h.*,
    o.post_date as order_date,
    u.user_email
FROM wp_chefpress_subscription_history h
JOIN wp_posts o ON h.order_id = o.ID
JOIN wp_postmeta pm ON o.ID = pm.post_id AND pm.meta_key = '_customer_user'
JOIN wp_users u ON pm.meta_value = u.ID
WHERE h.change_type = 'price_increase'
AND h.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY h.created_at DESC;
```

### Find pending refunds
```sql
SELECT 
    h.id,
    h.subscription_id,
    h.refund_amount,
    h.remaining_days,
    h.refund_status,
    s.user_id,
    u.user_email
FROM wp_chefpress_subscription_history h
JOIN wp_chefpress_user_subscriptions s ON h.subscription_id = s.id
JOIN wp_users u ON s.user_id = u.ID
WHERE h.change_type = 'refund'
AND h.refund_status = 'pending'
ORDER BY h.created_at DESC;
```

### Subscription edit timeline
```sql
SELECT * FROM wp_chefpress_subscription_history 
WHERE subscription_id = 45
ORDER BY created_at ASC;
```

---

## Troubleshooting Checklist

- [ ] Database tables created? Check phpmyadmin
- [ ] Subscription ID stored in order? Check `_subscription_id` meta
- [ ] Parent order ID in adjustment order? Check `_parent_order_id` meta
- [ ] History records exist? Check subscription_history table
- [ ] CSS loaded? Check browser DevTools network tab
- [ ] Notifications queued? Check user meta `devchefpress_notices`
- [ ] Permission errors? Verify nonce and user_id match
- [ ] Calculation errors? Check planDuration parsing

---

## Common Issues & Solutions

### Issue: Adjustment order not created on price increase

**Cause:** Payment status detection failing

**Solution:**
```php
// Debug: Check order status
$order = wc_get_order(123);
error_log('Order status: ' . $order->get_status());
error_log('Is paid: ' . (SubscriptionManager::get_payment_status($order) === 'paid' ? 'yes' : 'no'));
```

### Issue: Refund calculation seems wrong

**Cause:** Plan duration not in expected format

**Solution:** Ensure planDuration is one of:
- '1 Week'
- '1 Month'
- '3 Months'
- '6 Months'

### Issue: History not showing in modal

**Cause:** subscription_id not set in order meta

**Solution:**
```php
// Check if subscription_id exists
$order = wc_get_order(123);
$sub_id = $order->get_meta('_subscription_id');
error_log('Subscription ID: ' . ($sub_id ?: 'NOT SET'));
```

---

## Performance Notes

- History queries are indexed by `subscription_id`
- Refund calculations use simple math (no DB queries)
- Adjustment order creation creates 1 new order + 1 record
- History inserts are fast (single query)
- Modal loading uses AJAX (not blocking page)

---

## Security Audit

✓ Verified:
- User ownership check on all edits
- Nonce validation on AJAX
- Input sanitization throughout
- WC_Order type checking
- Database prepared statements
- No SQL injection vectors
- No output without escaping
