(function($) {
    'use strict';

    console.info('devchefpress my-subscriptions.js loaded');

    // Format date helper
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

    // Get day label helper
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

    // Build subscription details HTML
    function buildSubscriptionDetailsHTML(data) {
        if (!data) {
            return '<div class="devchefpress-empty-state">No subscription data available</div>';
        }

        let html = '<div class="devchefpress-subscription-details">';

        // HEADER
        html += '<div class="devchefpress-modal-header">';
        html += '<div class="devchefpress-header-content">';
        html += '<h2>Subscription Details</h2>';
        html += '<p class="devchefpress-header-subtitle">Complete overview of your meal plan subscription</p>';
        html += '</div>';
        html += '</div>';

        // SECTION 1: BASIC USER INFO
        if (data.goal || data.age || data.weight || data.height) {
            html += '<div class="devchefpress-section">';
            html += '<h3 class="devchefpress-section-title">👤 Personal Information</h3>';
            html += '<div class="devchefpress-info-grid-modal">';

            if (data.goal) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Goal</span>';
                html += '<span class="devchefpress-info-value">' + data.goal.replace(/\+/g, ' ') + '</span>';
                html += '</div>';
            }

            if (data.age) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Age</span>';
                html += '<span class="devchefpress-info-value">' + data.age + ' years</span>';
                html += '</div>';
            }

            if (data.gender) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Gender</span>';
                html += '<span class="devchefpress-info-value">' + (data.gender.charAt(0).toUpperCase() + data.gender.slice(1)) + '</span>';
                html += '</div>';
            }

            if (data.weight) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Weight</span>';
                html += '<span class="devchefpress-info-value">' + data.weight + ' kg</span>';
                html += '</div>';
            }

            if (data.height) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Height</span>';
                html += '<span class="devchefpress-info-value">' + data.height + ' cm</span>';
                html += '</div>';
            }

            if (data.bodyFat && data.bodyFat > 0) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Body Fat</span>';
                html += '<span class="devchefpress-info-value">' + data.bodyFat + '%</span>';
                html += '</div>';
            }

            if (data.activityLevel) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Activity Level</span>';
                html += '<span class="devchefpress-info-value">' + data.activityLevel.replace(/\+/g, ' ') + '</span>';
                html += '</div>';
            }

            if (data.dietType) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Diet Type</span>';
                html += '<span class="devchefpress-info-value">' + data.dietType.replace(/\+/g, ' ') + '</span>';
                html += '</div>';
            }

            if (data.planDuration) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Plan Duration</span>';
                html += '<span class="devchefpress-info-value">' + data.planDuration.replace(/\+/g, ' ') + '</span>';
                html += '</div>';
            }

            if (data.startDate) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Start Date</span>';
                html += '<span class="devchefpress-info-value">' + formatDate(data.startDate) + '</span>';
                html += '</div>';
            }

            if (data.deliverySlot) {
                html += '<div class="devchefpress-info-item-modal">';
                html += '<span class="devchefpress-info-label">Delivery Slot</span>';
                html += '<span class="devchefpress-info-value">' + (data.deliverySlot.charAt(0).toUpperCase() + data.deliverySlot.slice(1)) + '</span>';
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
                html += '<span class="devchefpress-value" style="font-weight: 600; color: #10b981;">' + data.address.name + '</span>';
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

            // PACKAGE DISCOUNT
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
                    html += '<span style="display: block; font-size: 12px; color: #6b7280; margin-top: 4px;">' + packageInfo.join(' • ') + '</span>';
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

    // View Details Click
    $(document).on('click', '.devchefpress-view-details', function(e) {
        e.preventDefault();
        const orderId = $(this).data('order-id');
        
        console.log('View Details clicked for order:', orderId);

        // Show modal
        $('#devchefpressSubscriptionModal').css('display', 'flex');
        $('#devchefpressSubscriptionContent').html('<div class="devchefpress-loading">Loading subscription details...</div>');

        console.log('Sending AJAX request for order:', orderId);
        $.ajax({
            url: devchefpress_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'devchefpress_get_subscription_details',
                nonce: devchefpress_ajax.nonce,
                order_id: orderId
            },
            success: function(res) {
                console.log('AJAX success response:', res);
                if (res.success && res.data) {
                    window.devChefpressCurrentSubscription = res.data;
                    
                    // Use HTML from backend if available, otherwise fall back to JavaScript builder
                    let html = res.data.html || buildSubscriptionDetailsHTML(res.data);
                    $('#devchefpressSubscriptionContent').html(html);

                    // Initialize map if coordinates exist
                    setTimeout(function() {
                        const mapContainer = document.getElementById('devchefpress-delivery-map');
                        if (mapContainer && mapContainer.dataset.lat && mapContainer.dataset.lng) {
                            try {
                                if (typeof L === 'undefined') {
                                    console.error('Leaflet library not loaded');
                                    return;
                                }

                                // Destroy existing map if it exists
                                if (window.devChefpressDeliveryMap) {
                                    window.devChefpressDeliveryMap.remove();
                                    window.devChefpressDeliveryMap = null;
                                }

                                const lat = parseFloat(mapContainer.dataset.lat);
                                const lng = parseFloat(mapContainer.dataset.lng);

                                const map = L.map('devchefpress-delivery-map', {
                                    scrollWheelZoom: true,
                                    zoomControl: true
                                }).setView([lat, lng], 15);

                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                                    maxZoom: 19,
                                    maxNativeZoom: 18
                                }).addTo(map);

                                const marker = L.marker([lat, lng]).addTo(map);
                                marker.bindPopup('<div style="font-weight: 600; color: #10b981; margin: 5px 0;">📍 Delivery Location</div>').openPopup();

                                map.invalidateSize();
                                
                                // Store map reference globally to clean up later
                                window.devChefpressDeliveryMap = map;
                            } catch(e) {
                                console.error('Map initialization error:', e);
                            }
                        }
                    }, 300);
                } else {
                    console.error('AJAX error response:', res);
                    $('#devchefpressSubscriptionContent').html('<div class="devchefpress-error-state">No subscription data available</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error, xhr);
                $('#devchefpressSubscriptionContent').html('<div class="devchefpress-error-state">Error loading subscription details. Please try again.</div>');
            }
        });
    });

    // Edit Subscription Click
    $(document).on('click', '.devchefpress-edit-subscription', function(e) {
        e.preventDefault();
        const orderId = $(this).data('order-id');

        if (orderId && devchefpress_ajax.edit_our_plans_url) {
            window.location.href = devchefpress_ajax.edit_our_plans_url + '?edit_order=' + orderId;
        }
    });


    // Tab switching for meal plan schedule
    $(document).on('click', '.devchefpress-tab-item', function() {
        const day = $(this).data('day');
        $('.devchefpress-tab-item').removeClass('active');
        $(this).addClass('active');
        $('.devchefpress-tab-content').removeClass('active');
        $('.devchefpress-tab-content[data-day="' + day + '"]').addClass('active');
    });

    // Close modal from close button
    $(document).on('click', '.devchefpress-modal-close', function() {
        // Clean up map before closing
        if (window.devChefpressDeliveryMap) {
            window.devChefpressDeliveryMap.remove();
            window.devChefpressDeliveryMap = null;
        }
        $('#devchefpressSubscriptionModal').css('display', 'none');
    });

    // Close modal on outside click
    $(document).on('click', '#devchefpressSubscriptionModal', function(e) {
        if (e.target.id === 'devchefpressSubscriptionModal') {
            // Clean up map before closing
            if (window.devChefpressDeliveryMap) {
                window.devChefpressDeliveryMap.remove();
                window.devChefpressDeliveryMap = null;
            }
            $(this).css('display', 'none');
        }
    });

})(jQuery);