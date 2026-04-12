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

        $this->loader->add_action(
            'wp_ajax_devchefpress_export_meal_plan',
            $this,
            'ajax_export_meal_plan'
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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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
                html += '<div class="devchefpress-header-content">';
                html += '<h2>Meal Plan Order Details</h2>';
                html += '<p class="devchefpress-header-subtitle">Complete overview of customer\'s meal plan subscription</p>';
                html += '</div>';
                html += '<div class="devchefpress-header-actions">';
                html += '<button class="devchefpress-pdf-btn" onclick="downloadMealPlanPDF(' + data.order_id + ')" title="Download PDF">';
                html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 15h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 19h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += ' PDF</button>';
                html += '</div>';
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

                    if (data.address.name) {
                        html += '<div class="devchefpress-address-item">';
                        html += '<span class="devchefpress-label">Location Name</span>';
                        html += '<span class="devchefpress-value" style="font-weight: 600; color: var(--cp_product_color-brand);">' + data.address.name + '</span>';
                        html += '</div>';
                    }

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
                        html += '<button class="devchefpress-gmaps-btn" onclick="window.open(\'https://www.google.com/maps?q=' + data.address.lat + ',' + data.address.lng + '\', \'_blank\')" title="Open in Google Maps">';
                        html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/></svg>';
                        html += ' View in Maps</button>';
                        html += '</div>';
                        html += '<div id="devchefpress-delivery-map" class="devchefpress-map-container" data-lat="' + data.address.lat + '" data-lng="' + data.address.lng + '"></div>';
                    }

                    html += '</div>';
                    html += '</div>';
                }

                // SECTION 3: MEAL PLAN
                if (data.slots && data.slots.length > 0) {
                    html += '<div class="devchefpress-section">';
                    html += '<h3 class="devchefpress-section-title">🍽️ Meal Plan Schedule</h3>';
                    html += '<div class="devchefpress-meal-plan-tabs">';
                    
                    // Group meals by day
                    let mealsByDay = {};
                    data.slots.forEach(slot => {
                        if (!mealsByDay[slot.day]) {
                            mealsByDay[slot.day] = [];
                        }
                        mealsByDay[slot.day].push(slot);
                    });

                    const days = Object.keys(mealsByDay);
                    if (days.length > 0) {
                        // Sidebar
                        html += '<div class="devchefpress-tabs-sidebar">';
                        days.forEach((dayCode, index) => {
                            const isActive = index === 0 ? ' active' : '';
                            html += '<div class="devchefpress-tab-item' + isActive + '" data-day="' + dayCode + '">' + getDayLabel(dayCode) + '</div>';
                        });
                        html += '</div>';
                        
                        // Content
                        html += '<div class="devchefpress-tabs-content">';
                        days.forEach((dayCode, index) => {
                            const isActive = index === 0 ? ' active' : '';
                            const dayMeals = mealsByDay[dayCode];
                            html += '<div class="devchefpress-tab-content' + isActive + '" data-day="' + dayCode + '">';
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
                    
                    html += '</div>';
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
                            window.devChefpressCurrentMealPlan = res.data;
                            $('#devChefpressMealPlanContent').html(
                                buildHTML(res.data)
                            );
                            
                            // Initialize map if coordinates exist with longer delay for proper rendering
                            setTimeout(function() {
                                const mapContainer = document.getElementById('devchefpress-delivery-map');
                                console.log('Map container found:', mapContainer ? 'Yes' : 'No');
                                
                                if (mapContainer && mapContainer.dataset.lat && mapContainer.dataset.lng) {
                                    try {
                                        // Check if Leaflet is loaded
                                        if (typeof L === 'undefined') {
                                            console.error('Leaflet library not loaded');
                                            return;
                                        }
                                        
                                        const lat = parseFloat(mapContainer.dataset.lat);
                                        const lng = parseFloat(mapContainer.dataset.lng);
                                        console.log('Initializing map with coordinates:', lat, lng);
                                        
                                        // Ensure container is visible and properly sized
                                        mapContainer.style.width = '100%';
                                        mapContainer.style.height = '350px';
                                        mapContainer.style.display = 'block';
                                        mapContainer.style.position = 'relative';
                                        mapContainer.style.zIndex = '1';
                                        
                                        console.log('Map container styles applied');
                                        
                                        // Initialize Leaflet map
                                        const map = L.map('devchefpress-delivery-map', {
                                            scrollWheelZoom: true,
                                            zoomControl: true
                                        }).setView([lat, lng], 15);
                                        
                                        console.log('Leaflet map initialized');
                                        
                                        // Add tile layer
                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                                            maxZoom: 19,
                                            maxNativeZoom: 18
                                        }).addTo(map);
                                        
                                        console.log('Tile layer added');
                                        
                                        // Add marker at delivery location
                                        const marker = L.marker([lat, lng], {
                                            icon: L.icon({
                                                iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
                                                iconSize: [25, 41],
                                                iconAnchor: [12, 41],
                                                popupAnchor: [1, -34],
                                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                                                shadowSize: [41, 41]
                                            })
                                        }).addTo(map);
                                        
                                        console.log('Marker added');
                                        
                                        marker.bindPopup('<div style="font-weight: 600; color: var(--cp_product_color-brand); margin: 5px 0;">📍 Delivery Location</div>').openPopup();
                                        
                                        console.log('Map initialization complete');
                                        
                                        // Fit map bounds
                                        map.invalidateSize();
                                    } catch(e) {
                                        console.error('Map initialization error:', e);
                                    }
                                }
                            }, 300);
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

            // Export meal plan function
            window.exportMealPlan = function(orderId) {
                if (!orderId || isNaN(orderId)) {
                    console.error('Export failed: missing order ID');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = ajaxurl;
                form.target = '_blank';
                
                const fields = {
                    action: 'devchefpress_export_meal_plan',
                    nonce: '<?php echo wp_create_nonce('devchefpress_meal_plan_nonce'); ?>',
                    order_id: orderId
                };
                
                for (const key in fields) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }

            window.downloadMealPlanPDF = function(orderId) {
                if (!orderId || isNaN(orderId)) {
                    console.error('PDF export failed: missing order ID');
                    return;
                }

                const mealPlan = window.devChefpressCurrentMealPlan;
                if (!mealPlan || parseInt(mealPlan.order_id, 10) !== parseInt(orderId, 10)) {
                    console.error('PDF export failed: meal plan data not available');
                    return;
                }

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ unit: 'pt', format: 'a4' });
                const margin = 40;
                let y = margin;

                doc.setFontSize(18);
                doc.text('Meal Plan Order Details', margin, y);
                y += 30;

                const addLine = (label, value) => {
                    doc.setFontSize(11);
                    doc.setFont(undefined, 'bold');
                    doc.text(label + ':', margin, y);
                    doc.setFont(undefined, 'normal');
                    doc.text(String(value), margin + 120, y, { maxWidth: 420 });
                    y += 18;
                };

                addLine('Order ID', orderId);
                y += 10;

                if (mealPlan.goal) addLine('Goal', mealPlan.goal.replace(/\+/g, ' '));
                if (mealPlan.age) addLine('Age', mealPlan.age + ' years');
                if (mealPlan.gender) addLine('Gender', mealPlan.gender);
                if (mealPlan.weight) addLine('Weight', mealPlan.weight + ' kg');
                if (mealPlan.height) addLine('Height', mealPlan.height + ' cm');
                if (mealPlan.activityLevel) addLine('Activity Level', mealPlan.activityLevel.replace(/\+/g, ' '));
                if (mealPlan.dietType) addLine('Diet Type', mealPlan.dietType.replace(/\+/g, ' '));
                if (mealPlan.planDuration) addLine('Plan Duration', mealPlan.planDuration.replace(/\+/g, ' '));
                if (mealPlan.startDate) addLine('Start Date', new Date(mealPlan.startDate).toLocaleDateString('en-US'));
                if (mealPlan.deliverySlot) addLine('Delivery Slot', mealPlan.deliverySlot);
                y += 20;

                if (mealPlan.address) {
                    doc.setFontSize(14);
                    doc.setFont(undefined, 'bold');
                    doc.text('Delivery Address', margin, y);
                    y += 20;
                    doc.setFontSize(11);
                    doc.setFont(undefined, 'normal');
                    if (mealPlan.address.name) addLine('Location Name', mealPlan.address.name);
                    if (mealPlan.address.type) addLine('Type', mealPlan.address.type);
                    if (mealPlan.address.building) addLine('Building/House', mealPlan.address.building);
                    if (mealPlan.address.floor) addLine('Floor', mealPlan.address.floor);
                    if (mealPlan.address.flat) addLine('Flat/Unit', mealPlan.address.flat);
                    if (mealPlan.address.details) addLine('Instructions', mealPlan.address.details);
                    if (mealPlan.address.lat && mealPlan.address.lng) addLine('Coordinates', mealPlan.address.lat.toFixed(4) + ', ' + mealPlan.address.lng.toFixed(4));
                    y += 20;
                }

                if (mealPlan.slots && mealPlan.slots.length > 0) {
                    doc.setFontSize(14);
                    doc.setFont(undefined, 'bold');
                    doc.text('Meal Plan Schedule', margin, y);
                    y += 20;
                    doc.setFontSize(11);
                    doc.setFont(undefined, 'normal');

                    const mealsByDay = {};
                    mealPlan.slots.forEach(slot => {
                        if (!mealsByDay[slot.day]) mealsByDay[slot.day] = [];
                        mealsByDay[slot.day].push(slot);
                    });

                    Object.keys(mealsByDay).forEach(dayCode => {
                        const dayLabel = getDayLabel(dayCode);
                        doc.setFont(undefined, 'bold');
                        doc.text(dayLabel, margin, y);
                        y += 16;
                        doc.setFont(undefined, 'normal');
                        mealsByDay[dayCode].forEach(slot => {
                            const recipeName = slot.recipeSelected ? slot.recipeSelected.title.replace(/\+/g, ' ') : 'Not selected';
                            doc.text(slot.meal + ': ' + recipeName, margin + 10, y, { maxWidth: 500 });
                            y += 14;
                            if (y > 740) {
                                doc.addPage();
                                y = margin;
                            }
                        });
                        y += 10;
                    });
                }

                if (mealPlan.pricing || mealPlan.subtotal || mealPlan.promoCode !== undefined) {
                    doc.setFontSize(14);
                    doc.setFont(undefined, 'bold');
                    doc.text('Pricing Summary', margin, y);
                    y += 20;
                    doc.setFontSize(11);
                    doc.setFont(undefined, 'normal');

                    if (mealPlan.pricing && mealPlan.pricing.subtotal !== undefined) addLine('Subtotal', '$' + parseFloat(mealPlan.pricing.subtotal).toFixed(2));
                    else if (mealPlan.subtotal !== undefined) addLine('Subtotal', '$' + parseFloat(mealPlan.subtotal).toFixed(2));

                    if (mealPlan.promoCode) addLine('Promo Code', mealPlan.promoCode);
                    const promoDiscount = mealPlan.promoDiscount !== undefined ? mealPlan.promoDiscount : (mealPlan.pricing ? mealPlan.pricing.promoDiscount : 0);
                    if (promoDiscount) addLine('Promo Discount', '-' + parseFloat(promoDiscount).toFixed(2));
                    if (mealPlan.couponTotal) addLine('Coupon Discount', '-$' + parseFloat(mealPlan.couponTotal).toFixed(2));
                    if (mealPlan.pricing && mealPlan.pricing.total !== undefined) addLine('Total', '$' + parseFloat(mealPlan.pricing.total).toFixed(2));
                    else if (mealPlan.total !== undefined) addLine('Total', '$' + parseFloat(mealPlan.total).toFixed(2));
                }

                doc.save('meal-plan-order-' + orderId + '.pdf');
            }

            // Tab switching for meal plan schedule
            $(document).on('click', '.devchefpress-tab-item', function(){
                const day = $(this).data('day');
                $('.devchefpress-tab-item').removeClass('active');
                $(this).addClass('active');
                $('.devchefpress-tab-content').removeClass('active');
                $('.devchefpress-tab-content[data-day="' + day + '"]').addClass('active');
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

        // Add order details for PDF generation
        $response_data['order_number'] = $order->get_order_number();
        $response_data['customer_name'] = $order->get_formatted_billing_full_name();

        // Keep the order ID available for export and UI actions
        $response_data['order_id'] = $order_id;

        wp_send_json_success($response_data);
    }

    /**
     * Export meal plan data as JSON
     */
    public function ajax_export_meal_plan(): void {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'devchefpress_meal_plan_nonce') ||
            !current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        if (!$order_id) {
            wp_send_json_error(['message' => 'Invalid order ID']);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found']);
        }

        // Get meal plan data
        $state = $order->get_meta('_meal_plan_state');
        $pricing = $order->get_meta('_meal_plan_pricing');

        if (empty($state)) {
            wp_send_json_error(['message' => 'No meal plan data found']);
        }

        // Prepare export data
        $export_data = [
            'order_id' => $order_id,
            'order_number' => $order->get_order_number(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'export_date' => current_time('Y-m-d H:i:s'),
            'meal_plan' => $state,
            'pricing' => $pricing ?: [
                'subtotal' => (float) $order->get_subtotal(),
                'total' => (float) $order->get_total(),
                'discounts' => $order->get_total_discount()
            ]
        ];

        // Set headers for JSON download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="meal-plan-order-' . $order_id . '.json"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output JSON
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }
}