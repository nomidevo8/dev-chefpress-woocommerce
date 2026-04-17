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

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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
        const address = data.address || {};
        html += '<div class="devchefpress-section">';
        html += '<h3 class="devchefpress-section-title">📍 Delivery Address</h3>';
        html += '<form id="devchefpressAddressForm" class="devchefpress-address-form" data-order-id="' + (data.order_id || '') + '">';
        html += '<div class="devchefpress-info-grid-modal">';

        html += '<div class="devchefpress-info-item-modal">';
        html += '<span class="devchefpress-info-label">Location Name</span>';
        html += '<input class="devchefpress-info-input" id="devchefpress-address-name" name="name" type="text" value="' + escapeHtml(address.name) + '" />';
        html += '</div>';

        html += '<div class="devchefpress-info-item-modal">';
        html += '<span class="devchefpress-info-label">Address Type</span>';
        html += '<input class="devchefpress-info-input" id="devchefpress-address-type" name="type" type="text" value="' + escapeHtml(address.type) + '" />';
        html += '</div>';

        html += '<div class="devchefpress-info-item-modal">';
        html += '<span class="devchefpress-info-label">Building / House</span>';
        html += '<input class="devchefpress-info-input" id="devchefpress-address-building" name="building" type="text" value="' + escapeHtml(address.building) + '" />';
        html += '</div>';

        html += '<div class="devchefpress-info-item-modal">';
        html += '<span class="devchefpress-info-label">Floor</span>';
        html += '<input class="devchefpress-info-input" id="devchefpress-address-floor" name="floor" type="text" value="' + escapeHtml(address.floor) + '" />';
        html += '</div>';

        html += '<div class="devchefpress-info-item-modal">';
        html += '<span class="devchefpress-info-label">Flat / Unit</span>';
        html += '<input class="devchefpress-info-input" id="devchefpress-address-flat" name="flat" type="text" value="' + escapeHtml(address.flat) + '" />';
        html += '</div>';

        html += '<div class="devchefpress-info-item-modal devchefpress-full-width">';
        html += '<span class="devchefpress-info-label">Special Instructions</span>';
        html += '<textarea class="devchefpress-info-textarea" id="devchefpress-address-details" name="details">' + escapeHtml(address.details) + '</textarea>';
        html += '</div>';

        // html += '<div class="devchefpress-info-item-modal">';
        // html += '<span class="devchefpress-info-label">Latitude</span>';
        // html += '<span class="devchefpress-info-value">' + escapeHtml(address.lat !== undefined ? address.lat : '') + '</span>';
        // html += '</div>';

        // html += '<div class="devchefpress-info-item-modal">';
        // html += '<span class="devchefpress-info-label">Longitude</span>';
        // html += '<span class="devchefpress-info-value">' + escapeHtml(address.lng !== undefined ? address.lng : '') + '</span>';
        // html += '</div>';

        html += '</div>';
        html += '<div class="devchefpress-address-form-actions">';
        html += ' <button type="button" id="devchefpress-save-address-btn" class="devchefpress-btn devchefpress-btn-primary">Save Address</button>';
        html += '<span id="devchefpress-address-save-feedback" class="devchefpress-save-address-feedback"></span>';
        html += '</form>';
        html += '</div>';

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
        

        // Show modal
        $('#devchefpressSubscriptionModal').css('display', 'flex');
        $('#devchefpressSubscriptionContent').html('<div class="devchefpress-loading">Loading subscription details...</div>');

        $.ajax({
            url: devchefpress_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'devchefpress_get_subscription_details',
                nonce: devchefpress_ajax.nonce,
                order_id: orderId
            },
            success: function(res) {
                if (res.success && res.data) {
                    window.devChefpressCurrentSubscription = res.data;
                    let html = buildSubscriptionDetailsHTML(res.data);
                    $('#devchefpressSubscriptionContent').html(html);
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

    // Save Address Click
    $(document).on('click', '#devchefpress-save-address-btn', function(e) {
        e.preventDefault();
        const $form = $('#devchefpressAddressForm');
        const orderId = $form.data('order-id');
        const $feedback = $('#devchefpress-address-save-feedback');

        if ( ! orderId ) {
            $feedback.text('Unable to determine subscription order.');
            return;
        }

        const address = {
            name: $('#devchefpress-address-name').val() || '',
            type: $('#devchefpress-address-type').val() || '',
            building: $('#devchefpress-address-building').val() || '',
            floor: $('#devchefpress-address-floor').val() || '',
            flat: $('#devchefpress-address-flat').val() || '',
            details: $('#devchefpress-address-details').val() || '',
            lat: window.devChefpressCurrentSubscription?.address?.lat || '',
            lng: window.devChefpressCurrentSubscription?.address?.lng || ''
        };

        $feedback.text('Saving address...');
        $(this).prop('disabled', true);

        $.ajax({
            url: devchefpress_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'devchefpress_update_subscription_address',
                nonce: devchefpress_ajax.nonce,
                order_id: orderId,
                address: JSON.stringify(address)
            },
            success: function(res) {
                if ( res.success && res.data ) {
                    $feedback.text('Address updated successfully.');
                    window.devChefpressCurrentSubscription.address = res.data.address || address;
                    const html = buildSubscriptionDetailsHTML(window.devChefpressCurrentSubscription);
                    $('#devchefpressSubscriptionContent').html(html);
                } else {
                    $feedback.text(res.data?.message || 'Unable to save address.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save address error:', status, error, xhr);
                $feedback.text('Error saving address. Please try again.');
            },
            complete: function() {
                $('#devchefpress-save-address-btn').prop('disabled', false);
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
        $('#devchefpressSubscriptionModal').css('display', 'none');
    });

    // Close modal on outside click
    $(document).on('click', '#devchefpressSubscriptionModal', function(e) {
        if (e.target.id === 'devchefpressSubscriptionModal') {
            $(this).css('display', 'none');
        }
    });

})(jQuery);