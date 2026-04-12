<?php
declare(strict_types=1);

namespace DevChefPress\Admin;

use DevChefPress\Hooks\Loader;

class OrderDetails {

    private Loader $loader;

    public function __construct(Loader $loader) {
        $this->loader = $loader;
        $this->register_hooks();
    }

    private function register_hooks(): void {
        $this->loader->add_action(
            'woocommerce_admin_order_data_after_order_details',
            $this,
            'render_meal_plan_button'
        );

        $this->loader->add_action(
            'admin_footer',
            $this,
            'render_modal_template'
        );

        $this->loader->add_action(
            'wp_ajax_devchefpress_get_meal_plan',
            $this,
            'ajax_get_meal_plan'
        );
    }

    /**
     * 1. BUTTON in order page
     */
    public function render_meal_plan_button($order): void {

        if (!$order instanceof \WC_Order) {
            $order = wc_get_order($order);
        }

        if (!$order) return;

        $state   = $order->get_meta('_meal_plan_state');
        $pricing = $order->get_meta('_meal_plan_pricing');

        if (empty($state) && empty($pricing)) {
            return;
        }

        $order_id = $order->get_id();

        echo '<div class="devchefpress-order-actions">';
        echo '<button type="button" style="margin-top:30px;"
            class="button button-primary dev-chefpress-view-meal-plan"
            data-order-id="' . esc_attr($order_id) . '">
            📋 View Meal Plan Details
        </button>';
        echo '</div>';
        
    }

    /**
     * 2. MODAL TEMPLATE (hidden)
     */
    public function render_modal_template(): void {
        global $pagenow;

        if ($pagenow !== 'admin.php') return;

        ?>
        <div id="devChefpressMealPlanModal" class="devchefpress-modal-overlay">
            <div class="devchefpress-modal-container">
                <button class="devchefpress-modal-close" onclick="document.getElementById('devChefpressMealPlanModal').style.display='none'">
                    ✖
                </button>

                <div id="devChefpressMealPlanContent" style=" background: white;" class="devchefpress-modal-content">
                    Loading...
                </div>
            </div>
        </div>

        <link rel="stylesheet" href="<?php echo esc_url(plugins_url('assets/css/meal-plan-modal.css', dirname(dirname(__FILE__)))); ?>">

        <script>
        (function($){

            function formatDate(dateStr) {
                if (!dateStr) return '-';
                try {
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                } catch(e) {
                    return dateStr;
                }
            }

            function getDayLabel(dayCode) {
                const days = {
                    'Mon': 'Monday',
                    'Tue': 'Tuesday',
                    'Wed': 'Wednesday',
                    'Thu': 'Thursday',
                    'Fri': 'Friday',
                    'Sat': 'Saturday',
                    'Sun': 'Sunday'
                };
                return days[dayCode] || dayCode;
            }

            function buildHTML(data) {
                if (!data) {
                    return '<div class="devchefpress-empty-state">No meal plan data available for this order</div>';
                }

                let html = '<div class="devchefpress-meal-plan-details">';

                // HEADER
                html += '<div class="devchefpress-modal-header">';
                html += '<h2>Meal Plan Order Details</h2>';
                html += '<p class="devchefpress-header-subtitle">Complete overview of customer\'s meal plan subscription</p>';
                html += '</div>';

                // SECTION 1: BASIC USER INFO
                if (data.goal || data.age || data.weight || data.height) {
                    html += '<div class="devchefpress-section">';
                    html += '<h3 class="devchefpress-section-title">👤 Personal Information</h3>';
                    html += '<div class="devchefpress-info-grid">';
                    
                    if (data.goal) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Goal</span>';
                        html += '<span class="devchefpress-value">' + data.goal.replace(/\+/g, ' ') + '</span>';
                        html += '</div>';
                    }

                    if (data.age) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Age</span>';
                        html += '<span class="devchefpress-value">' + data.age + ' years</span>';
                        html += '</div>';
                    }

                    if (data.gender) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Gender</span>';
                        html += '<span class="devchefpress-value">' + (data.gender.charAt(0).toUpperCase() + data.gender.slice(1)) + '</span>';
                        html += '</div>';
                    }

                    if (data.weight) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Weight</span>';
                        html += '<span class="devchefpress-value">' + data.weight + ' kg</span>';
                        html += '</div>';
                    }

                    if (data.height) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Height</span>';
                        html += '<span class="devchefpress-value">' + data.height + ' cm</span>';
                        html += '</div>';
                    }

                    if (data.bodyFat && data.bodyFat > 0) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Body Fat</span>';
                        html += '<span class="devchefpress-value">' + data.bodyFat + '%</span>';
                        html += '</div>';
                    }

                    if (data.activityLevel) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Activity Level</span>';
                        html += '<span class="devchefpress-value">' + data.activityLevel.replace(/\+/g, ' ') + '</span>';
                        html += '</div>';
                    }

                    if (data.dietType) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Diet Type</span>';
                        html += '<span class="devchefpress-value">' + data.dietType.replace(/\+/g, ' ') + '</span>';
                        html += '</div>';
                    }

                    if (data.planDuration) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Plan Duration</span>';
                        html += '<span class="devchefpress-value">' + data.planDuration.replace(/\+/g, ' ') + '</span>';
                        html += '</div>';
                    }

                    if (data.startDate) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Start Date</span>';
                        html += '<span class="devchefpress-value">' + formatDate(data.startDate) + '</span>';
                        html += '</div>';
                    }

                    if (data.deliverySlot) {
                        html += '<div class="devchefpress-info-item">';
                        html += '<span class="devchefpress-label">Delivery Slot</span>';
                        html += '<span class="devchefpress-value">' + (data.deliverySlot.charAt(0).toUpperCase() + data.deliverySlot.slice(1)) + '</span>';
                        html += '</div>';
                    }

                    html += '</div>';
                    html += '</div>';
                }

                // SECTION 2: ADDRESS
                if (data.address) {
                    html += '<div class="devchefpress-section">';
                    html += '<h3 class="devchefpress-section-title">📍 Delivery Address</h3>';
                    html += '<div class="devchefpress-address-card">';

                    if (data.address.type) {
                        html += '<div class="devchefpress-address-item">';
                        html += '<span class="devchefpress-label">Address Type</span>';
                        html += '<span class="devchefpress-value">' + data.address.type + '</span>';
                        html += '</div>';
                    }

                    if (data.address.building) {
                        html += '<div class="devchefpress-address-item">';
                        html += '<span class="devchefpress-label">Building/House</span>';
                        html += '<span class="devchefpress-value">' + data.address.building + '</span>';
                        html += '</div>';
                    }

                    if (data.address.floor) {
                        html += '<div class="devchefpress-address-item">';
                        html += '<span class="devchefpress-label">Floor</span>';
                        html += '<span class="devchefpress-value">' + data.address.floor + '</span>';
                        html += '</div>';
                    }

                    if (data.address.flat) {
                        html += '<div class="devchefpress-address-item">';
                        html += '<span class="devchefpress-label">Flat / Unit</span>';
                        html += '<span class="devchefpress-value">' + data.address.flat + '</span>';
                        html += '</div>';
                    }

                    if (data.address.details) {
                        html += '<div class="devchefpress-address-item">';
                        html += '<span class="devchefpress-label">Special Instructions</span>';
                        html += '<span class="devchefpress-value">' + data.address.details + '</span>';
                        html += '</div>';
                    }

                    if (data.address.lat && data.address.lng) {
                        html += '<div class="devchefpress-address-item devchefpress-coords">';
                        html += '<span class="devchefpress-label">Coordinates</span>';
                        html += '<span class="devchefpress-value devchefpress-muted">' + data.address.lat.toFixed(4) + ', ' + data.address.lng.toFixed(4) + '</span>';
                        html += '</div>';
                    }

                    html += '</div>';
                    html += '</div>';
                }

                // SECTION 3: MEAL PLAN
                if (data.slots && data.slots.length > 0) {
                    html += '<div class="devchefpress-section">';
                    html += '<h3 class="devchefpress-section-title">🍽️ Meal Plan Schedule</h3>';
                    
                    // Group meals by day
                    let mealsByDay = {};
                    data.slots.forEach(slot => {
                        if (!mealsByDay[slot.day]) {
                            mealsByDay[slot.day] = [];
                        }
                        mealsByDay[slot.day].push(slot);
                    });

                    // Display meals grouped by day
                    Object.keys(mealsByDay).forEach(dayCode => {
                        const dayMeals = mealsByDay[dayCode];
                        html += '<div class="devchefpress-day-block">';
                        html += '<h4 class="devchefpress-day-title">' + getDayLabel(dayCode) + '</h4>';
                        
                        dayMeals.forEach(slot => {
                            const recipeName = slot.recipeSelected ? slot.recipeSelected.title.replace(/\+/g, ' ') : 'Not selected';
                            html += '<div class="devchefpress-meal-item">';
                            html += '<span class="devchefpress-meal-type">' + slot.meal + '</span>';
                            html += '<span class="devchefpress-recipe-name">' + recipeName + '</span>';
                            html += '</div>';
                        });

                        html += '</div>';
                    });

                    html += '</div>';
                }

                // SECTION 4: PRICING
                if (data.pricing || data.subtotal || data.promoCode !== undefined) {
                    html += '<div class="devchefpress-section">';
                    html += '<h3 class="devchefpress-section-title">💰 Pricing Summary</h3>';
                    html += '<div class="devchefpress-pricing-card">';

                    // Subtotal
                    if (data.pricing && data.pricing.subtotal !== undefined) {
                        html += '<div class="devchefpress-pricing-row">';
                        html += '<span class="devchefpress-pricing-label">Subtotal</span>';
                        html += '<span class="devchefpress-pricing-value">$' + parseFloat(data.pricing.subtotal).toFixed(2) + '</span>';
                        html += '</div>';
                    } else if (data.subtotal !== undefined) {
                        html += '<div class="devchefpress-pricing-row">';
                        html += '<span class="devchefpress-pricing-label">Subtotal</span>';
                        html += '<span class="devchefpress-pricing-value">$' + parseFloat(data.subtotal).toFixed(2) + '</span>';
                        html += '</div>';
                    }

                    // PACKAGE DISCOUNT (Display as prominent section)
                    let packageInfo = [];
                    if (data.planDuration) {
                        packageInfo.push(data.planDuration.replace(/\+/g, ' '));
                    }
                    if (data.dietType) {
                        packageInfo.push(data.dietType.replace(/\+/g, ' '));
                    }
                    
                    if ((data.pricing && data.pricing.planDiscount !== undefined && data.pricing.planDiscount > 0) || packageInfo.length > 0) {
                        const planDiscount = (data.pricing && data.pricing.planDiscount !== undefined) ? data.pricing.planDiscount : 0;
                        html += '<div class="devchefpress-pricing-row devchefpress-package-discount-row">';
                        html += '<div style="flex: 1;">';
                        html += '<span class="devchefpress-pricing-label">📦 Package Discount</span>';
                        if (packageInfo.length > 0) {
                            html += '<span style="display: block; font-size: 12px; color: var(--cp_product_color-text-muted); margin-top: 4px;">' + packageInfo.join(' • ') + '</span>';
                        }
                        html += '</div>';
                        if (planDiscount > 0) {
                            html += '<span class="devchefpress-pricing-value devchefpress-package-badge">-' + parseFloat(planDiscount).toFixed(0) + '%</span>';
                        }
                        html += '</div>';
                    }

                    // Promo Code
                    if (data.promoCode && data.promoCode.trim() !== '') {
                        html += '<div class="devchefpress-pricing-row">';
                        html += '<span class="devchefpress-pricing-label">🎟️ Promo Code</span>';
                        html += '<span class="devchefpress-pricing-value devchefpress-promo-code">' + data.promoCode + '</span>';
                        html += '</div>';
                    }

                    // Promo Discount
                    if ((data.promoDiscount !== undefined && data.promoDiscount > 0) || (data.pricing && data.pricing.promoDiscount !== undefined && data.pricing.promoDiscount > 0)) {
                        const promoDiscount = data.promoDiscount !== undefined ? data.promoDiscount : (data.pricing ? data.pricing.promoDiscount : 0);
                        html += '<div class="devchefpress-pricing-row">';
                        html += '<span class="devchefpress-pricing-label">Promo Discount</span>';
                        html += '<span class="devchefpress-pricing-value devchefpress-discount">-' + parseFloat(promoDiscount).toFixed(0) + '%</span>';
                        html += '</div>';
                    }

                    // Coupon Discount
                    if (data.couponTotal !== undefined && data.couponTotal > 0) {
                        html += '<div class="devchefpress-pricing-row">';
                        html += '<span class="devchefpress-pricing-label">Coupon Discount</span>';
                        html += '<span class="devchefpress-pricing-value devchefpress-discount">-$' + parseFloat(data.couponTotal).toFixed(2) + '</span>';
                        html += '</div>';
                    }

                    // Total
                    if (data.pricing && data.pricing.total !== undefined) {
                        html += '<div class="devchefpress-pricing-row devchefpress-pricing-total">';
                        html += '<span class="devchefpress-pricing-label">Total</span>';
                        html += '<span class="devchefpress-pricing-value">$' + parseFloat(data.pricing.total).toFixed(2) + '</span>';
                        html += '</div>';
                    } else if (data.total !== undefined) {
                        html += '<div class="devchefpress-pricing-row devchefpress-pricing-total">';
                        html += '<span class="devchefpress-pricing-label">Total</span>';
                        html += '<span class="devchefpress-pricing-value">$' + parseFloat(data.total).toFixed(2) + '</span>';
                        html += '</div>';
                    }

                    html += '</div>';
                    html += '</div>';
                }

                html += '</div>';
                return html;
            }

            $(document).on('click', '.dev-chefpress-view-meal-plan', function(){

                const orderId = $(this).data('order-id');

                $('#devChefpressMealPlanModal').css('display','flex');
                $('#devChefpressMealPlanContent').html('<div class="devchefpress-loading">Loading meal plan details...</div>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'devchefpress_get_meal_plan',
                        nonce: '<?php echo wp_create_nonce('devchefpress-nonce'); ?>',
                        order_id: orderId
                    },
                    success: function(res){

                        if(res.success){
                            $('#devChefpressMealPlanContent').html(
                                buildHTML(res.data)
                            );
                        } else {
                            $('#devChefpressMealPlanContent').html(
                                '<div class="devchefpress-error-state">No meal plan data available for this order</div>'
                            );
                        }
                    },
                    error: function(){
                        $('#devChefpressMealPlanContent').html(
                            '<div class="devchefpress-error-state">Error loading meal plan. Please try again.</div>'
                        );
                    }
                });
            });

            // Close modal on outside click
            $(document).on('click', '#devChefpressMealPlanModal', function(e){
                if(e.target.id === 'devChefpressMealPlanModal'){
                    $(this).css('display','none');
                }
            });

        })(jQuery);
        </script>
        <?php
    }

    /**
     * 3. AJAX HANDLER - Get meal plan data
     */
    public function ajax_get_meal_plan(): void {
        // check_ajax_referer('devchefpress-nonce', 'nonce');

        // if (!current_user_can('manage_woocommerce_orders')) {
        //     wp_send_json_error(['message' => 'Unauthorized']);
        // }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if (!$order_id) {
            wp_send_json_error(['message' => 'Invalid order ID']);
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(['message' => 'Order not found']);
        }

        $state   = $order->get_meta('_meal_plan_state');
        $pricing = $order->get_meta('_meal_plan_pricing');

        if (empty($state)) {
            wp_send_json_error(['message' => 'No meal plan found']);
        }

        // Prepare response data
        $response_data = [];

        // Add state data
        if (!empty($state)) {
            $response_data = is_array($state) ? $state : json_decode((string)$state, true);
        }

        // Add pricing data from separate meta if exists
        if (!empty($pricing)) {
            $pricing_data = is_array($pricing) ? $pricing : json_decode((string)$pricing, true);
            $response_data['pricing'] = $pricing_data;
        }

        // If no separate pricing meta, extract from WooCommerce order
        if (empty($response_data['pricing']) || empty($response_data['pricing'])) {
            $response_data['subtotal'] = (float) $order->get_subtotal();
            $response_data['total'] = (float) $order->get_total();
            
            // Calculate discounts from order coupons
            $coupon_discount = 0;
            foreach ($order->get_coupons() as $coupon) {
                $coupon_discount += (float) $coupon->get_discount();
            }
            
            if ($coupon_discount > 0) {
                $response_data['couponTotal'] = $coupon_discount;
            }
        }

        wp_send_json_success($response_data);
    }
}