/**
 * Dev ChefPress for WooCommerce — Frontend JS
 * Mode filtering: Backend (scalable)
 */
(function ($) {
    'use strict';

    /**
      * Dev ChefPress for WooCommerce — Frontend JS For Weekly Menu Page
      */

    const cp_weekly_recipes = [];
    let cp_weekly_current_page = 1;
    let cp_weekly_total_pages = 1;
    let cp_weekly_selected_meal_type = ''; // Client-side meal type filter

    const $cp_weekly_grid = $('#cp_weekly_recipe_grid');
    const $cp_weekly_pagination = $('#cp_weekly_pagination');
    const $cp_weekly_banner = $('.cp_weekly_menu_banner_text');

    function updateBanner(recipeCount) {
        const activeWeekElement = $('.cp_weekly_menu_date_item.active');
        const weekIndex = parseInt(activeWeekElement.data('week') || $('#active_week_index').val() || 1, 10);
        const weekLabel = activeWeekElement.find('.cp_weekly_menu_date_range').text() || '';
        const weekMonth = activeWeekElement.find('.cp_weekly_menu_date_month').text() || '';
        const dateRangesEl = $('#week_date_ranges');
        const dateRanges = dateRangesEl.length && dateRangesEl.val() ? JSON.parse(dateRangesEl.val()) : null;
        const fullRange = `${weekLabel} ${weekMonth}`.trim()
            || (dateRanges && dateRanges[weekIndex] ? `${dateRanges[weekIndex].range} ${dateRanges[weekIndex].month}` : `Week ${weekIndex}`);
        
        if ($cp_weekly_banner.length) {
            $cp_weekly_banner.text(`Choose from ${recipeCount} recipes for the week of ${fullRange}`);
        }
    }

    function formatMealTypeLabel(mealType) {
        if (!mealType) {
            return '';
        }
        const normalized = mealType.toString().trim().toLowerCase();
        if (!normalized || normalized === 'all') {
            return '';
        }
        if (normalized === 'snacks') {
            return 'Snacks';
        }
        return normalized.charAt(0).toUpperCase() + normalized.slice(1);
    }

    function getAddToSlotButtonLabel(mealType) {
        const mealLabel = formatMealTypeLabel(mealType);
        return mealLabel ? `Add to ${mealLabel}` : 'Add to slot';
    }

    function updateAddToSlotButtonLabels(mealType) {
        const label = getAddToSlotButtonLabel(mealType);
        $('.cp_weekly_menu_card_add_slot').text(label);
    }

    const renderRecipes = (recipes) => {
        $cp_weekly_grid.removeClass('loading');
        $cp_weekly_grid.empty();
        recipes.forEach(cp_weekly_recipe => {
            const categoryText = Array.isArray(cp_weekly_recipe.categories) && cp_weekly_recipe.categories.length
                ? cp_weekly_recipe.categories.join(', ')
                : (cp_weekly_recipe.category_name || cp_weekly_recipe.category || '');

            const tagsArray = Array.isArray(cp_weekly_recipe.tags) ? cp_weekly_recipe.tags : [];
            let cp_weekly_tags_html = '';
            tagsArray.forEach(cp_weekly_tag => {
                if (typeof cp_weekly_tag === 'string') {
                    cp_weekly_tags_html += `
                            <span class="cp_weekly_menu_tag">
                                ${cp_weekly_tag}
                            </span>
                        `;
                } else if (cp_weekly_tag && typeof cp_weekly_tag === 'object') {
                    cp_weekly_tags_html += `
                            <span class="cp_weekly_menu_tag">
                                ${cp_weekly_tag.icon || ''}${cp_weekly_tag.label || ''}
                            </span>
                        `;
                }
            });

            const timeText = cp_weekly_recipe.cookingTime || cp_weekly_recipe.time || '';
            const caloriesText = cp_weekly_recipe.calories || '';
            const imageUrl = cp_weekly_recipe.image || cp_weekly_recipe.image_url || '';

            const cp_weekly_card_html = `
                        <div class="cp_weekly_menu_card" data-recipe-id="${cp_weekly_recipe.id}" data-product-url="${cp_weekly_recipe.url || ''}">
                            <div class="cp_weekly_menu_card_img_wrapper">
                                <img src="${imageUrl}" alt="${cp_weekly_recipe.title || ''}" class="cp_weekly_menu_card_img" referrerpolicy="no-referrer">
                                ${cp_weekly_recipe.isNew ? '<span class="cp_weekly_menu_badge_new">NEW</span>' : ''}
                            </div>
                            <div class="cp_weekly_menu_card_content">
                                <div class="cp_weekly_menu_card_category">
                                    ${cp_weekly_recipe.categoryIcon || ''} ${categoryText}
                                </div>
                                <h3 class="cp_weekly_menu_card_title cp_weekly_menu_card_title_clickable" data-recipe-id="${cp_weekly_recipe.id}" style="cursor: pointer;">${cp_weekly_recipe.title}</h3>
                                <p class="cp_weekly_menu_card_subtitle">${cp_weekly_recipe.subtitle}</p>
                                <div class="cp_weekly_menu_card_tags">
                                    ${cp_weekly_tags_html}
                                </div>
                                <div class="cp_weekly_menu_card_actions">
                                    <button type="button" class="cp_weekly_menu_card_add_slot" data-recipe-id="${cp_weekly_recipe.id}">${getAddToSlotButtonLabel(cp_weekly_selected_meal_type)}</button>
                                </div>
                                <div class="cp_weekly_menu_card_footer">
                                    <div class="cp_weekly_menu_footer_item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #f97316;"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg> ${timeText}
                                    </div>
                                    <div class="cp_weekly_menu_footer_item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #9ca3af;"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg> ${caloriesText} cals
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
            $cp_weekly_grid.append(cp_weekly_card_html);
        });
    };

    function renderSkeleton(count = 6) {
        $cp_weekly_grid.addClass('loading');
        const skeletonHTML = Array.from({ length: count }).map(() => `
            <div class="cp_weekly_menu_skeleton_card">
                <div class="cp_weekly_menu_skeleton_image"></div>
                <div class="cp_weekly_menu_skeleton_body">
                    <div class="cp_weekly_menu_skeleton_text cp_weekly_menu_skeleton_text_short"></div>
                    <div class="cp_weekly_menu_skeleton_text cp_weekly_menu_skeleton_text_long"></div>
                    <div class="cp_weekly_menu_skeleton_text cp_weekly_menu_skeleton_text_long"></div>
                    <div class="cp_weekly_menu_skeleton_footer">
                        <span class="cp_weekly_menu_skeleton_chip"></span>
                        <span class="cp_weekly_menu_skeleton_chip"></span>
                    </div>
                </div>
            </div>
        `).join('');
        $cp_weekly_grid.html(skeletonHTML);
    }

    function setLoading(loading) {
        if (loading) {
            renderSkeleton();
        } else {
            $cp_weekly_grid.removeClass('loading');
        }
    }

    // Initial render by fetching server data for selected week
    callFilterService();

    // Sort Dropdown Logic
    const $sortBtn = $('#cp_weekly_sort_btn');
    const $sortDropdown = $('#cp_weekly_sort_dropdown');

    $(document).on('click', '#cp_weekly_sort_btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $sortDropdown.toggleClass('show');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.cp_weekly_menu_dropdown').length) {
            $sortDropdown.removeClass('show');
            $mealTypeDropdown.removeClass('show');
        }
    });

    $('#cp_weekly_sort_dropdown a').on('click', function (e) {
        e.preventDefault();
        const sortType = $(this).data('sort');
        let sortedData = [...cp_weekly_recipes];

        $('#cp_weekly_sort_dropdown a').removeClass('active');
        $(this).addClass('active');

        switch (sortType) {
            case 'calories-asc':
                sortedData.sort((a, b) => parseInt(a.calories) - parseInt(b.calories));
                break;
            case 'carbs-asc':
                sortedData.sort((a, b) => a.carbs - b.carbs);
                break;
            case 'time-asc':
                sortedData.sort((a, b) => parseInt(a.time) - parseInt(b.time));
                break;
            case 'protein-desc':
                sortedData.sort((a, b) => b.protein - a.protein);
                break;
            case 'default':
            default:
                // Keep original order (id based)
                sortedData.sort((a, b) => a.id - b.id);
                break;
        }

        renderRecipes(sortedData);

        // Update button text
        const selectedText = $(this).text();
        $sortBtn.html(`${selectedText} <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>`);

        // Close dropdown
        $sortDropdown.removeClass('show');

        // Reset to first page when sorting changes
        cp_weekly_current_page = 1;

        // Send updated sort to filter service using Postman-style payload
        callFilterService();
    });

    function normalizeSortForApi(sort) {
        if (!sort || sort === 'default') {
            return '';
        }
        const mapping = {
            'calories-asc': 'calories:ASC',
            'calories-desc': 'calories:DESC',
            'carbs-asc': 'carbs:ASC',
            'carbs-desc': 'carbs:DESC',
            'time-asc': 'time:ASC',
            'time-desc': 'time:DESC',
            'protein-asc': 'protein:ASC',
            'protein-desc': 'protein:DESC',
        };
        return mapping[sort] || sort;
    }

    const cp_weekly_mealtype_arrow_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';

    function updateMealTypeButtonText(label) {
        $mealTypeBtn.html(`${label} ${cp_weekly_mealtype_arrow_svg}`);
    }

    function filterRecipesBySelectedMealType(recipes) {
        if (!cp_weekly_selected_meal_type) {
            return recipes;
        }
        return recipes.filter(recipe => {
            const recipeMealTypes = Array.isArray(recipe.mealType) ? recipe.mealType : [];
            return recipeMealTypes.some(mt => mt.toLowerCase() === cp_weekly_selected_meal_type.toLowerCase());
        });
    }

    function setMealTypeSelection(mealType) {
        cp_weekly_selected_meal_type = mealType || '';
        $('#cp_weekly_mealtype_dropdown a').removeClass('active');

        const normalizedType = mealType ? mealType.toLowerCase() : '';
        const $option = $(`#cp_weekly_mealtype_dropdown a[data-meal-type="${normalizedType}"]`);

        if ($option.length) {
            $option.addClass('active');
            updateMealTypeButtonText($option.text().trim());
            updateAddToSlotButtonLabels(normalizedType);
        } else {
            const $default = $('#cp_weekly_mealtype_dropdown a[data-meal-type=""]');
            $default.addClass('active');
            updateMealTypeButtonText('Meal Type');
            updateAddToSlotButtonLabels('');
        }
    }

    window.cpWeeklySetMealTypeFilter = function(mealType) {
        setMealTypeSelection(mealType);
    };

    // Meal Type Dropdown Logic (Client-side filtering)
    const $mealTypeBtn = $('#cp_weekly_mealtype_btn');
    const $mealTypeDropdown = $('#cp_weekly_mealtype_dropdown');

    $(document).on('click', '#cp_weekly_mealtype_btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $mealTypeDropdown.toggleClass('show');
    });

    $('#cp_weekly_mealtype_dropdown a').on('click', function (e) {
        e.preventDefault();
        const mealType = $(this).data('meal-type');
        // Update active state
        $('#cp_weekly_mealtype_dropdown a').removeClass('active');
        $(this).addClass('active');

        // Store the selected meal type and update button state
        setMealTypeSelection(mealType);

        // Filter recipes by meal type client-side
        const filteredRecipes = filterRecipesBySelectedMealType(cp_weekly_recipes);

        // Close dropdown
        $mealTypeDropdown.removeClass('show');

        // Reset to first page when filter changes
        cp_weekly_current_page = 1;

        // Render filtered recipes without making backend call
        renderRecipes(filteredRecipes);
    });

    function collectSidebarFilters() {
        const activeWeekElement = $('.cp_weekly_menu_date_item.active');
        // const week = activeWeekElement.length ? parseInt(activeWeekElement.data('week') || 1, 10) : 1;
        let week = 1;

        if (activeWeekElement.length) {
        const dataWeek = activeWeekElement.data('week');

        if (dataWeek !== undefined && dataWeek !== null && dataWeek !== '') {
            week = parseInt(dataWeek, 10);
        } else {
            week = parseInt($('#active_week_index').val(), 10) || 1;
        }
        } else {
        week = parseInt($('#active_week_index').val(), 10) || 1;
        }

        return {
            week: isNaN(week) ? 1 : week,
            category: $('.cp_weekly_menu_sidebar_btn[data-category].active').data('category') || '',
            tags: $('.cp_weekly_menu_sidebar_btn[data-recipe-tag].active, .cp_weekly_menu_filter_btn[data-recipe-tag].active').map(function () {
                return $(this).data('recipe-tag');
            }).get(),
            allergens: $('.cp_weekly_menu_sidebar_btn[data-allergen].active').map(function () {
                return $(this).data('allergen');
            }).get(),
            sort: normalizeSortForApi($('#cp_weekly_sort_dropdown a.active').data('sort') || 'default'),
            page: cp_weekly_current_page,
        };
    }

    function resetAllFilters() {
        $('.cp_weekly_menu_sidebar_btn').removeClass('active');
        $('.cp_weekly_menu_filter_btn[data-recipe-tag]').removeClass('active');
        $('#cp_weekly_sort_dropdown a').removeClass('active');
        $('#cp_weekly_sort_dropdown a[data-sort="default"]').addClass('active');
        $('#cp_weekly_mealtype_dropdown a').removeClass('active');
        $('#cp_weekly_mealtype_dropdown a[data-meal-type=""]').addClass('active');
        $('.cp_weekly_menu_date_item').removeClass('active');
        $('.cp_weekly_menu_date_item').first().addClass('active');
        $sortBtn.html('Sort by <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>');
        $mealTypeBtn.html('Meal Type <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>');
        cp_weekly_selected_meal_type = '';
        cp_weekly_current_page = 1;
    }

    function resetNonDateFilters() {
        $('.cp_weekly_menu_sidebar_btn').removeClass('active');
        $('.cp_weekly_menu_filter_btn[data-recipe-tag]').removeClass('active');
        $('#cp_weekly_sort_dropdown a').removeClass('active');
        $('#cp_weekly_sort_dropdown a[data-sort="default"]').addClass('active');
        $('#cp_weekly_mealtype_dropdown a').removeClass('active');
        $('#cp_weekly_mealtype_dropdown a[data-meal-type=""]').addClass('active');
        $sortBtn.html('Sort by <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>');
        $mealTypeBtn.html('Meal Type <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>');
        cp_weekly_selected_meal_type = '';
        cp_weekly_current_page = 1;
    }

    function renderPagination(currentPage, totalPages) {
        if (totalPages <= 1) {
            $cp_weekly_pagination.empty();
            return;
        }

        let paginationHTML = '';

        // Previous button
        if (currentPage > 1) {
            paginationHTML += `<a href="#" class="cp_weekly_pagination_btn cp_weekly_pagination_prev" data-page="${currentPage - 1}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg> Previous
            </a>`;
        }

        // Page numbers
        paginationHTML += '<div class="cp_weekly_pagination_numbers">';
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                const activeClass = i === currentPage ? 'active' : '';
                paginationHTML += `<a href="#" class="cp_weekly_pagination_num ${activeClass}" data-page="${i}">${i}</a>`;
            } else if (i === 2 && currentPage > 3) {
                paginationHTML += '<span class="cp_weekly_pagination_dots">...</span>';
            } else if (i === totalPages - 1 && currentPage < totalPages - 2) {
                paginationHTML += '<span class="cp_weekly_pagination_dots">...</span>';
            }
        }

        paginationHTML += '</div>';

        // Next button
        if (currentPage < totalPages) {
            paginationHTML += `<a href="#" class="cp_weekly_pagination_btn cp_weekly_pagination_next" data-page="${currentPage + 1}">
                Next <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>`;
        }

        $cp_weekly_pagination.html(paginationHTML);
    }

    function callFilterService() {
        const filters = collectSidebarFilters();

        const payload = {
            action: 'chefpress_filter_recipes',
            _chefpress_nonce: ChefPressConfig?.nonce || '',
            week: filters.week,
        };

        if (filters.category) {
            payload.category = filters.category;
        }

        if (filters.tags.length > 0) {
            payload['tags[]'] = filters.tags;
        }

        if (filters.allergens.length > 0) {
            payload['allergens[]'] = filters.allergens;
        }

        if (filters.sort && filters.sort !== 'default') {
            payload.sort = filters.sort;
        }
        payload.page = filters.page;

        setLoading(true);

        $.ajax({
            url: ChefPressConfig.ajax_url,
            type: 'POST',
            data: payload,
            dataType: 'json',
            success(response) {
                if (!response) {
                    setLoading(false);
                    $cp_weekly_grid.html('<p class="cp-no-results">No response from filter service.</p>');
                    return;
                }

                if (Array.isArray(response.recipes) && response.recipes.length) {
                    // Store recipes in the global array for client-side filtering
                    cp_weekly_recipes.length = 0; // Clear existing array
                    cp_weekly_recipes.push(...response.recipes);
                    
                    cp_weekly_current_page = response.current_page || 1;
                    cp_weekly_total_pages = response.total_pages || 1;
                    const recipeCount = response.recipes_count || response.recipes.length;
                    const recipesToRender = filterRecipesBySelectedMealType(response.recipes);
                    renderRecipes(recipesToRender);
                    renderPagination(cp_weekly_current_page, cp_weekly_total_pages);
                    updateBanner(recipeCount);
                    // Scroll to top of grid
                    $('html, body').animate({ scrollTop: $cp_weekly_grid.offset().top - 100 }, 300);
                    return;
                }

                if (response.html) {
                    setLoading(false);
                    $cp_weekly_grid.html(response.html);
                    $cp_weekly_pagination.empty();
                    return;
                }

                setLoading(false);
               $cp_weekly_grid.html(`
                    <div class="cp-no-results">
                        <p>No recipes found 🍽️</p>
                        <small>Try changing filters or check back later.</small>
                    </div>
                `);
                $cp_weekly_pagination.empty();
                updateBanner(0);
            },
            error() {
                setLoading(false);
                $cp_weekly_grid.html('<p class="cp-no-results">Filter request failed, please retry.</p>');
                $cp_weekly_pagination.empty();
            },
        });
    }

    // Pagination Click Handler
    $(document).on('click', '.cp_weekly_pagination_num, .cp_weekly_pagination_prev, .cp_weekly_pagination_next', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const page = parseInt($(this).data('page'), 10);
        if (page && page > 0) {
            cp_weekly_current_page = page;
            callFilterService();
        }
    });   


    // Sidebar Logic
    const $cp_weekly_sidebar = $('#cp_weekly_filter_sidebar');
    const $cp_weekly_overlay = $('#cp_weekly_sidebar_overlay');

    const cp_weekly_open_sidebar = (cp_weekly_e) => {
        if (cp_weekly_e) cp_weekly_e.preventDefault();
        $cp_weekly_sidebar.addClass('active');
        $cp_weekly_overlay.addClass('active');
        $('body').css('overflow', 'hidden');
    };

    const cp_weekly_close_sidebar = () => {
        $cp_weekly_sidebar.removeClass('active');
        $cp_weekly_overlay.removeClass('active');
        $('body').css('overflow', '');
    };

    $('#cp_weekly_open_sidebar_btn, #cp_weekly_view_all_link').on('click', cp_weekly_open_sidebar);
    $('#cp_weekly_close_sidebar_btn, #cp_weekly_sidebar_overlay').on('click', cp_weekly_close_sidebar);

    // Toggle Sidebar Buttons
    $('.cp_weekly_menu_sidebar_btn').on('click', function () {
        $(this).toggleClass('active');
    });

    // Show More Allergens
    $('#cp_weekly_show_more_allergens').on('click', function () {
        $('.cp_weekly_menu_sidebar_btn_hidden').each(function() {
            $(this).removeClass('cp_weekly_menu_sidebar_btn_hidden').hide().slideDown();
        });
        $(this).hide();
    });

    // Apply filters from sidebar (Postman payload format)
    $('.cp_weekly_menu_sidebar_apply').on('click', function (e) {
        e.preventDefault();
        $cp_weekly_sidebar.removeClass('active');
        $cp_weekly_overlay.removeClass('active');
        $('body').css('overflow', '');
        cp_weekly_current_page = 1;
        callFilterService();
    });

    // Clear sidebar filters
    $('.cp_weekly_menu_sidebar_clear').on('click', function (e) {
        e.preventDefault();
        resetAllFilters();
        $cp_weekly_sidebar.removeClass('active');
        $cp_weekly_overlay.removeClass('active');
        $('body').css('overflow', '');
        cp_weekly_current_page = 1;
        callFilterService();
    });

    // Date Carousel Logic
    const $cp_weekly_date_track = $('#cp_weekly_date_track');

    const cp_weekly_get_scroll_amount = () => {
        const $cp_weekly_item = $('.cp_weekly_menu_date_item').first();
        const cp_weekly_width = $cp_weekly_item.outerWidth();
        const cp_weekly_gap = parseFloat($cp_weekly_date_track.css('gap')) || 0;
        return cp_weekly_width + cp_weekly_gap;
    };

    $('#cp_weekly_date_prev').on('click', function (e) {
        e.preventDefault();
        const scrollAmount = cp_weekly_get_scroll_amount() * 2;
        $cp_weekly_date_track[0].scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    });

    $('#cp_weekly_date_next').on('click', function (e) {
        e.preventDefault();
        const scrollAmount = cp_weekly_get_scroll_amount() * 2;
        $cp_weekly_date_track[0].scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    });

    // Auto-scroll to active week on page load
    function scrollToActiveWeek() {
        const $activeWeek = $('.cp_weekly_menu_date_item.active');
        if ($activeWeek.length) {
            $activeWeek[0].scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center'
            });
        }
    }

    // Call on document ready
    $(document).ready(function() {
        setTimeout(scrollToActiveWeek, 100);
    });

    // Date Selection (week filters take priority and reset other filters)
    $('.cp_weekly_menu_date_item').on('click', function () {
        $('.cp_weekly_menu_date_item').removeClass('active');
        $(this).addClass('active');

        // When switching week, remove all other filters and sort to keep week-only view
        // But DON'T reset the date items - we just set them!
        resetNonDateFilters();

        callFilterService();
    });

    // Top recipe-tag toggles (multi-select, in addition to sidebar tags)
    $(document).on('click', '.cp_weekly_menu_filter_btn[data-recipe-tag]', function (e) {
        e.preventDefault();

        $(this).toggleClass('active');

        const tag = $(this).data('recipe-tag');
        if (tag) {
            $('.cp_weekly_menu_sidebar_btn[data-recipe-tag="' + tag + '"]').toggleClass('active', $(this).hasClass('active'));
        }
        cp_weekly_current_page = 1;

        callFilterService();
    });

    // Recipe Modal Logic
    const $cp_weekly_modal = $(`
        <div id="cp_weekly_recipe_modal" class="cp_weekly_recipe_modal">
            <div class="cp_weekly_recipe_modal_overlay"></div>
            <div class="cp_weekly_recipe_modal_dialog">
                <div class="cp_weekly_recipe_modal_header">
                    <a class="cp_weekly_recipe_modal_view_product" href="#" target="_blank" rel="noopener noreferrer" aria-label="View Full Product Page">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        View Product
                    </a>
                    <button class="cp_weekly_recipe_modal_close" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <div class="cp_weekly_recipe_modal_content">
                    <div class="cp_weekly_recipe_modal_loader">
                        <div class="cp_weekly_spinner"></div>
                    </div>
                </div>
            </div>
        </div>
    `);

    $('body').append($cp_weekly_modal);

    // Open modal on title click
    $(document).on('click', '.cp_weekly_menu_card_title_clickable', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const recipeId = $(this).data('recipe-id');
        const $card = $(this).closest('.cp_weekly_menu_card');
        const productUrl = $card.data('product-url') || '';
        
        if (recipeId) {
            cp_weekly_open_modal(recipeId, productUrl);
        }
    });

    // Add to slot button handler
    $(document).on('click', '.cp_weekly_menu_card_add_slot', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const recipeId = $(this).data('recipe-id');
        if (!recipeId) {
            return;
        }

        if (typeof window.assignRecipe === 'function') {
            window.assignRecipe(recipeId);
        } else {
            console.warn('assignRecipe is not available on this page.');
        }
    });

    // Open modal function
    function cp_weekly_open_modal(recipeId, productUrl = '') {
        $cp_weekly_modal.addClass('active');
        $('body').css('overflow', 'hidden');

        // Set the product URL on the view product button
        if (productUrl) {
            $('.cp_weekly_recipe_modal_view_product').attr('href', productUrl);
        }

        // Fetch recipe details
        $.ajax({
            url: ChefPressConfig.ajax_url,
            type: 'POST',
            data: {
                action: 'chefpress_get_recipe_details',
                _chefpress_nonce: ChefPressConfig?.nonce || '',
                recipe_id: recipeId,
            },
            dataType: 'html',
            success(html) {
                $('.cp_weekly_recipe_modal_content').html(html);
            },
            error(data) {
                $('.cp_weekly_recipe_modal_content').html('<p class="cp-modal-error">Failed to load recipe details. Please try again.</p>');
            },
        });
    }

    // Close modal
    function cp_weekly_close_modal() {
        $cp_weekly_modal.removeClass('active');
        $('body').css('overflow', '');
        $('.cp_weekly_recipe_modal_content').html(`
            <div class="cp_weekly_recipe_modal_loader">
                <div class="cp_weekly_spinner"></div>
            </div>
        `);
    }

    // Handle close button and overlay click
    $(document).on('click', '.cp_weekly_recipe_modal_close, .cp_weekly_recipe_modal_overlay', function (e) {
        if (e.target === this || $(this).hasClass('cp_weekly_recipe_modal_close')) {
            cp_weekly_close_modal();
        }
    });

    // Close modal on Escape key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $cp_weekly_modal.hasClass('active')) {
            cp_weekly_close_modal();
        }
    });

})(jQuery);

