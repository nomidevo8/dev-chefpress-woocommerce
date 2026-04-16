# Quick Reference - Subscription Edit API

## 🔗 Core Classes

### SubscriptionManager
```php
use DevChefPress\Services\SubscriptionManager;

// Get payment status
$status = SubscriptionManager::get_payment_status($order);  // 'paid' or 'unpaid'

// Calculate refund
$refund = SubscriptionManager::calculate_smart_refund($diff, $details, $state);

// Handle unpaid edit
$result = SubscriptionManager::handle_unpaid_edit($order_id, $state, $pricing);

// Create adjustment order
$adj_id = SubscriptionManager::create_adjustment_order($order_id, $sub_id, $diff, $pricing);

// Handle price decrease
$result = SubscriptionManager::handle_price_decrease($sub_id, $order_id, $diff, $details, $state);

// Insert history
$hist_id = SubscriptionManager::insert_history(['subscription_id' => 45, ...]);

// Get history
$history = SubscriptionManager::get_history($subscription_id);

// Get adjustment orders
$orders = SubscriptionManager::get_adjustment_orders($order_id);

// Get main subscription orders (filter adjustments)
$orders = SubscriptionManager::get_main_subscription_orders($user_id);
```

### NotificationService
```php
use DevChefPress\Services\NotificationService;

// Notify about unpaid edit
NotificationService::notify_edit_unpaid($user_id, $order_id, $difference);

// Notify about price increase
NotificationService::notify_price_increase($user_id, $adj_order_id, $amount);

// Notify about pending refund
NotificationService::notify_refund_pending($user_id, $refund_amount, $days);

// Notify admin
NotificationService::notify_admin($order_id, 'price_increase');  // or 'refund_required'

// Get user notifications
$notices = NotificationService::get_user_notices($user_id, $clear = false);
```

### Database/Migrations
```php
use DevChefPress\Database\Migrations;

// Run migrations (called on plugin activation)
Migrations::run();
```

---

## 📋 Database Schema Quick Ref

### subscription_history Table

| Column | Type | Meaning |
|--------|------|---------|
| `id` | BIGINT | Auto-increment ID |
| `subscription_id` | BIGINT | FK to user_subscriptions |
| `order_id` | BIGINT | Original WC order ID |
| `change_type` | VARCHAR | edit_unpaid / price_increase / refund |
| `old_price` | DECIMAL | Previous price |
| `new_price` | DECIMAL | New price |
| `difference` | DECIMAL | new - old (can be negative) |
| `refund_amount` | DECIMAL | Amount to refund (if applicable) |
| `adjustment_order_id` | BIGINT | FK to adjustment order (if applicable) |
| `remaining_days` | INT | Days left in subscription (for refund) |
| `total_days` | INT | Total subscription days (for audit) |
| `refund_status` | VARCHAR | pending / approved / completed |
| `notes` | LONGTEXT | Additional info |
| `created_at` | DATETIME | When change was made |
| `updated_at` | DATETIME | Last update |

---

## 🔄 Decision Tree

```
Order Editing
    ↓
Is order paid? (processing/completed)
    ├─ NO (pending/failed)
    │   └─ CASE A: handle_unpaid_edit()
    │       └─ Update same order
    │       └─ Keep single order_id
    │       └─ No new payment needed
    │
    └─ YES (processing/completed)
        ├─ New price > old price?
        │   ├─ YES
        │   │   └─ CASE B1: create_adjustment_order()
        │   │       └─ Create new order for difference
        │   │       └─ Link via _parent_order_id
        │   │       └─ Redirect to checkout
        │   │
        │   └─ NO (new price < old price)
        │       └─ CASE B2: handle_price_decrease()
        │           └─ Calculate pro-rata refund
        │           └─ Record as pending
        │           └─ Update subscription
        │
        └─ ELSE (same price)
            └─ Just update subscription record
```

---

## 💰 Refund Calculation

```
Total subscription days = parse_duration(planDuration)
  '1 Week'  → 7 days
  '1 Month' → 30 days
  '3 Months' → 90 days
  '6 Months' → 180 days

Remaining days = ceil((subscription_end_date - today) / 86400)

Refund ratio = remaining_days / total_days

Refund amount = abs(price_difference) * refund_ratio
```

**Example:**
```
Plan: 1 Month (30 days)
Price decrease: -$60
Today: Day 15 of 30
Remaining: 15 days

Ratio: 15/30 = 0.5
Refund: $60 * 0.5 = $30
```

---

## 🎯 Common Patterns

### Check if order is paid
```php
$order = wc_get_order($order_id);
$is_paid = SubscriptionManager::get_payment_status($order) === 'paid';
```

### Get all pending refunds for user
```php
$sub_ids = UserSubscription::get_active_by_user($user_id);
foreach ($sub_ids as $sub) {
    $refund = $sub->get_total_refund_pending();
    if ($refund > 0) {
        // User has pending refund
    }
}
```

### Track edit for audit
```php
$hist_id = SubscriptionManager::insert_history([
    'subscription_id' => $sub_id,
    'order_id' => $order_id,
    'change_type' => 'price_increase',
    'old_price' => $old,
    'new_price' => $new,
    'difference' => $new - $old,
    'adjustment_order_id' => $adj_order_id,
    'notes' => "User edited from {$old} to {$new}"
]);
```

### Send notifications
```php
// For unpaid edit
NotificationService::notify_edit_unpaid($user_id, $order_id, -5.50);
// Message: "Order updated. New price: $95."

// For price increase
NotificationService::notify_price_increase($user_id, $adj_id, 50.00);
// Message: "Additional payment of $50 required. [Pay Now]"

// For price decrease
NotificationService::notify_refund_pending($user_id, 25.00, 15);
// Message: "Refund of $25 pending (15 remaining days). 5-7 business days."

// For admin
NotificationService::notify_admin($order_id, 'refund_required');
// Email sent to admin about refund
```

---

## 🧪 SQL Debugging

### See recent edits
```sql
SELECT 
  h.created_at, 
  h.change_type, 
  h.old_price, 
  h.new_price, 
  h.difference,
  h.refund_amount
FROM wp_chefpress_subscription_history h
WHERE h.subscription_id = 45
ORDER BY h.created_at DESC;
```

### Find adjustment orders
```sql
SELECT 
  o.ID as order_id,
  pm.meta_value as parent_order_id
FROM wp_posts o
JOIN wp_postmeta pm ON o.ID = pm.post_id 
  AND pm.meta_key = '_parent_order_id'
WHERE pm.meta_value = 123;  -- original order
```

### Check pending refunds
```sql
SELECT 
  s.id,
  s.user_id,
  s.total_refund_pending,
  COUNT(h.id) as change_count
FROM wp_chefpress_user_subscriptions s
LEFT JOIN wp_chefpress_subscription_history h 
  ON s.id = h.subscription_id 
  AND h.change_type = 'refund'
WHERE s.total_refund_pending > 0
GROUP BY s.id;
```

---

## ✅ Validation Checklist

Before modifying an order:

```php
// 1. Is order valid?
$order = wc_get_order($order_id);
if (!$order) { /* Error */ }

// 2. Does user own it?
if ($order->get_customer_id() !== get_current_user_id()) { /* Error */ }

// 3. Is subscription linked?
$sub_id = $order->get_meta('_subscription_id');
if (!$sub_id) { /* Error */ }

// 4. Does subscription exist?
$sub = UserSubscription::get_by_id($sub_id);
if (!$sub) { /* Error */ }

// 5. Check payment status
$status = SubscriptionManager::get_payment_status($order);
// $status will be 'paid' or 'unpaid'

// 6. Calculate prices
$new_pricing = calculate_meal_plan_pricing($state);
$old_price = (float) $order->get_total();
$new_price = (float) $new_pricing['total'];
$difference = $new_price - $old_price;

// 7. Proceed with appropriate handler
if ('unpaid' === $status) {
    SubscriptionManager::handle_unpaid_edit($order_id, $state, $new_pricing);
} elseif ($difference > 0) {
    SubscriptionManager::create_adjustment_order($order_id, $sub_id, $difference, $new_pricing);
} elseif ($difference < 0) {
    SubscriptionManager::handle_price_decrease($sub_id, $order_id, $difference, ...);
}
```

---

## 🔐 Security Checklist

- [ ] User ownership verified: `$order->get_customer_id() === get_current_user_id()`
- [ ] Nonce validated: `wp_verify_nonce($_POST['nonce'], 'action_name')`
- [ ] Input sanitized: All `$_POST` through WP sanitize functions
- [ ] Output escaped: All user data through `esc_html()`, `esc_attr()`, etc.
- [ ] DB queries prepared: Using `$wpdb->prepare()` with placeholders
- [ ] Errors returned: Using `WP_Error` for failures
- [ ] Permission checks: Capability checks where applicable
- [ ] SSL verified: All sensitive operations over HTTPS

---

## 🚀 Performance Tips

1. **Index queries**: Already done on subscription_id, order_id, change_type
2. **Cache history**: Results are already ordered by created_at DESC
3. **Batch operations**: Insert history once per edit (not multiple records)
4. **AJAX loading**: Modal loads async (doesn't block page load)
5. **No N+1**: Each method makes bounded queries

---

## 📞 Error Codes

All errors return `WP_Error`:

| Code | Meaning | Solutions |
|------|---------|-----------|
| `order_not_found` | Order doesn't exist | Verify order_id is correct |
| `unauthorized` | User doesn't own order | Check user_id matches |
| `subscription_not_found` | No subscription linked | Ensure _subscription_id meta set |
| `invalid_user` | User ID invalid | Check user exists |
| `woocommerce_not_found` | WC not active | Check WooCommerce installed |
| `db_insert_error` | DB write failed | Check permissions, disk space |
| `missing_field` | History data incomplete | Verify all required fields in insert_history() |

---

**Last Updated:** April 2026  
**Version:** 1.0.0  
**Status:** 🟢 Production Ready
