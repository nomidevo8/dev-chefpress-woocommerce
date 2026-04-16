# Subscription Edit + Billing + History System

## Overview

Complete implementation of meal plan subscription editing with intelligent billing logic, smart refunds, and comprehensive history tracking.

## Architecture

### Key Components

1. **Migrations.php** - Database table creation
   - `wp_chefpress_user_subscriptions` - Main subscriptions (unchanged)
   - `wp_chefpress_subscription_history` - Change tracking and history

2. **SubscriptionManager** - Core business logic
   - Payment status detection
   - Unpaid order edits
   - Adjustment order creation
   - Smart refund calculation
   - History tracking
   - Order/adjustment linking

3. **NotificationService** - User/admin notifications
   - User-facing notifications
   - Admin notifications
   - Notification storage and retrieval

4. **SubscriptionDetailsHelper** - HTML generation
   - Personal information section
   - Delivery address section
   - Meal plan schedule section
   - Pricing summary
   - Change history display
   - Refund information
   - Adjustment orders display

5. **Frontend.php** - AJAX handlers & integration
   - Modified `update_meal_plan_order()` with case handling
   - Enhanced `handle_get_subscription_details()` with history

## Database Schema

### subscription_history Table

```sql
CREATE TABLE wp_chefpress_subscription_history (
    id BIGINT(20) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    subscription_id BIGINT(20) UNSIGNED NOT NULL,
    order_id BIGINT(20) UNSIGNED NOT NULL,
    change_type VARCHAR(50) NOT NULL,  -- edit_unpaid, price_increase, refund, price_decrease
    old_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    new_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    difference DECIMAL(10,2) NOT NULL DEFAULT 0,
    refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    adjustment_order_id BIGINT(20) UNSIGNED DEFAULT NULL,
    remaining_days INT UNSIGNED DEFAULT NULL,
    total_days INT UNSIGNED DEFAULT NULL,
    refund_status VARCHAR(50) DEFAULT 'pending',  -- pending, approved, completed
    notes LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY subscription_id (subscription_id),
    KEY order_id (order_id),
    KEY change_type (change_type),
    KEY refund_status (refund_status)
);
```

## Logic Flow

### CASE A: Unpaid Order Edit

**When:** Order status is `pending` or `failed`

**Process:**
1. Load existing WooCommerce order
2. Calculate new pricing
3. Update line items in same order
4. Update order total
5. Update subscription record (meals, delivery details, price)
6. Record in history table
7. Keep order status as `pending`
8. User proceeds to regular checkout

**Result:** Single order with updated items and total

**Notification:** "Order updated. Additional payment required: $X" (if price increased)

### CASE B1: Paid Order with Price Increase

**When:** Order status is `processing` or `completed` AND new_price > old_price

**Process:**
1. Create NEW adjustment order (type='adjustment')
2. Add "Meal Plan Adjustment" line item with difference amount
3. Link adjustment to original order via `_parent_order_id` meta
4. Mark adjustment with `_adjustment_type='price_increase'`
5. Set adjustment order status to `pending`
6. Record in history table with adjustment_order_id
7. Redirect user to checkout for adjustment order only
8. Subscription NOT updated until payment succeeds

**Result:** Two orders - original (paid) + adjustment (pending payment)

**Notification:** "Additional payment required: $X. [Complete Payment Button]"

**Admin Notification:** "Price increase detected. Adjustment order requires payment."

### CASE B2: Paid Order with Price Decrease

**When:** Order status is `processing` or `completed` AND new_price < old_price

**Process:**
1. Calculate pro-rata refund based on remaining subscription days:
   ```
   total_days = parse_duration(planDuration)  // e.g., 30 for "1 Month"
   remaining_days = ceil((subscription_end - today) / 86400)
   refund_ratio = remaining_days / total_days
   refund_amount = abs(difference) * refund_ratio
   ```
2. Update subscription record:
   - Increment `total_refund_pending` by refund_amount
   - Decrement `current_price` by difference (which is negative)
3. Original WooCommerce order NOT modified
4. Record in history with refund details and calculation
5. Set refund_status to 'pending'
6. NO payment flow - user sees pending refund message

**Result:** Original order unchanged, refund tracked and pending

**Payment Gateway:** NOT called - manual refund via admin panel

**Notification:** "Refund pending: $X (based on remaining X days). Processing within 5-7 business days."

**Admin Notification:** "Refund approval required. Amount: $X"

## Key Rules

✅ **MUST DO:**
- Create adjustment orders ONLY for price increases on paid orders
- Use new orders for extra charges, never modify paid orders directly
- Track ALL changes in history table
- Verify user ownership before any modification
- Calculate refunds pro-rata based on time remaining
- Update custom subscription table consistently with orders

❌ **NEVER DO:**
- Modify already-paid orders directly
- Create adjustment orders for unpaid order edits
- Auto-refund to payment gateway
- Show adjustment orders in subscription list
- Lose change history

## Files Modified/Created

### Created:
- `app/Database/Migrations.php` - Table creation
- `app/Services/SubscriptionManager.php` - Core logic (450+ lines)
- `app/Services/NotificationService.php` - Notifications
- `app/Frontend/SubscriptionDetailsHelper.php` - HTML generation
- `resources/css/subscription-details.css` - Styling

### Modified:
- `app/Frontend/Frontend.php` - Updated AJAX handlers
- `templates/my-subscriptions.php` - Filter main subscriptions only
- `resources/js/my-subscriptions.js` - Use backend HTML

## Implementation Checklist

- [x] Create database migrations (Migrations.php)
- [x] Create SubscriptionManager service
- [x] Create NotificationService
- [x] Create SubscriptionDetailsHelper
- [x] Update Frontend.php imports and AJAX handlers
- [x] Update my-subscriptions.php filtering
- [x] Update my-subscriptions.js
- [x] Create subscription-details.css
- [x] Enqueue CSS in Frontend.php

## Usage Examples

### Get Payment Status
```php
$order = wc_get_order($order_id);
$status = SubscriptionManager::get_payment_status($order);
// Returns: 'paid' or 'unpaid'
```

### Calculate Smart Refund
```php
$refund = SubscriptionManager::calculate_smart_refund(
    150.00,  // price_difference (absolute)
    ['startDate' => '2026-04-16'],  // delivery_details
    ['planDuration' => '1 Month']   // state
);
// Returns: ['refund_amount' => 75.00, 'remaining_days' => 15, ...]
```

### Handle Unpaid Edit
```php
$result = SubscriptionManager::handle_unpaid_edit(
    $order_id,
    $new_state,
    $new_pricing
);
// Updates order, subscription, and history
```

### Create Adjustment Order
```php
$adj_order_id = SubscriptionManager::create_adjustment_order(
    $order_id,
    $subscription_id,
    $difference,
    $new_pricing
);
// Creates and links adjustment order
```

### Send Notifications
```php
NotificationService::notify_refund_pending(
    $user_id,
    $refund_amount,
    $remaining_days
);

NotificationService::notify_admin($order_id, 'refund_required');
```

### Get Subscription History
```php
$history = SubscriptionManager::get_history($subscription_id);
// Returns array of history records
```

## Security Considerations

✓ **Implemented:**
- User ownership verification
- Nonce checks on AJAX
- Input sanitization
- WP_Error for errors
- Database prepared statements

## Testing Recommendations

1. **Test Unpaid Edit:** Create order, edit before payment, verify single order updated
2. **Test Price Increase:** Pay order, edit with higher price, verify adjustment order created
3. **Test Price Decrease:** Pay order, edit with lower price, verify refund calculated correctly
4. **Test Authorization:** Try to edit someone else's order, verify rejection
5. **Test History:** Make multiple edits, verify all changes tracked
6. **Test Notifications:** Verify user/admin notifications sent for each case

## Troubleshooting

### Database tables not created?
Ensure `Migrations::run()` is called on plugin activation:
```php
register_activation_hook(__FILE__, function() {
    \DevChefPress\Database\Migrations::run();
});
```

### Adjustment orders not linking?
Verify `_parent_order_id` meta is set correctly in `create_adjustment_order()`

### Refund calculation incorrect?
Check `parse_duration_to_days()` - verify plan duration format matches

### History not showing?
Verify `subscription_id` is correctly stored in order meta `_subscription_id`

## Future Enhancements

- Automatic refund processing to payment gateway
- Admin panel for refund approval workflow
- Email templates customization
- Refund history detail view
- Price change suggestions based on meals
- Group edits (edit multiple subscriptions at once)
