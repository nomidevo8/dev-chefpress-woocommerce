/**
 * Dev ChefPress for WooCommerce — Frontend JS
 * Dual-mode filtering: Frontend (fast) and Backend (scalable)
 */
(function ($) {
    'use strict';

    /**
      * Dev ChefPress for WooCommerce — Frontend JS For Weekly Menu Page
      */

    const cp_weekly_recipes = [];

    const $cp_weekly_grid = $('#cp_weekly_recipe_grid');

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
                        <div class="cp_weekly_menu_card">
                            <div class="cp_weekly_menu_card_img_wrapper">
                                <img src="${imageUrl}" alt="${cp_weekly_recipe.title || ''}" class="cp_weekly_menu_card_img" referrerpolicy="no-referrer">
                                ${cp_weekly_recipe.isNew ? '<span class="cp_weekly_menu_badge_new">NEW</span>' : ''}
                            </div>
                            <div class="cp_weekly_menu_card_content">
                                <div class="cp_weekly_menu_card_category">
                                    ${cp_weekly_recipe.categoryIcon || ''} ${categoryText}
                                </div>
                                <h3 class="cp_weekly_menu_card_title">${cp_weekly_recipe.title}</h3>
                                <p class="cp_weekly_menu_card_subtitle">${cp_weekly_recipe.subtitle}</p>
                                <div class="cp_weekly_menu_card_tags">
                                    ${cp_weekly_tags_html}
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
        }
    });

    $('#cp_weekly_sort_dropdown a').on('click', function (e) {
        e.preventDefault();
        console.log('Sort option selected:', $(this).data('sort'));
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

    function collectSidebarFilters() {
        const activeWeekElement = $('.cp_weekly_menu_date_item.active');
        const week = activeWeekElement.length ? parseInt(activeWeekElement.data('week') || 1, 10) : 1;

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
        };
    }

    function resetAllFilters() {
        $('.cp_weekly_menu_sidebar_btn').removeClass('active');
        $('.cp_weekly_menu_filter_btn[data-recipe-tag]').removeClass('active');
        $('#cp_weekly_sort_dropdown a').removeClass('active');
        $('#cp_weekly_sort_dropdown a[data-sort="default"]').addClass('active');
        $sortBtn.html('Sort by <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>');
    }

    function callFilterService() {
        const filters = collectSidebarFilters();

        const payload = {
            action: 'chefpress_filter_recipes',
            _chefpress_nonce: ChefPressConfig?.nonce || '',
            week: filters.week,
            filter_mode: ChefPressConfig?.filter_mode || 'auto',
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

                if (ChefPressConfig.filter_mode === 'backend' && response.html) {
                    setLoading(false);
                    $cp_weekly_grid.html(response.html);
                    return;
                }

                if (Array.isArray(response.recipes) && response.recipes.length) {
                    renderRecipes(response.recipes);
                    return;
                }

                if (response.html) {
                    setLoading(false);
                    $cp_weekly_grid.html(response.html);
                    return;
                }

                setLoading(false);
                $cp_weekly_grid.html('<p class="cp-no-results">No recipes found in filter response.</p>');
            },
            error() {
                setLoading(false);
                $cp_weekly_grid.html('<p class="cp-no-results">Filter request failed, please retry.</p>');
            },
        });
    }

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
        callFilterService();
    });

    // Clear sidebar filters
    $('#cp_weekly_menu_sidebar_clear').on('click', function (e) {
        e.preventDefault();
        resetAllFilters();
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

    $('#cp_weekly_date_prev').on('click', function () {
        $cp_weekly_date_track.animate({
            scrollLeft: $cp_weekly_date_track.scrollLeft() - (cp_weekly_get_scroll_amount() * 2)
        }, 300);
    });

    $('#cp_weekly_date_next').on('click', function () {
        $cp_weekly_date_track.animate({
            scrollLeft: $cp_weekly_date_track.scrollLeft() + (cp_weekly_get_scroll_amount() * 2)
        }, 300);
    });

    // Date Selection (week filters take priority and reset other filters)
    $('.cp_weekly_menu_date_item').on('click', function () {
        $('.cp_weekly_menu_date_item').removeClass('active');
        $(this).addClass('active');

        // When switching week, remove all other filters and sort to keep week-only view
        resetAllFilters();

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

        callFilterService();
    });

})(jQuery);

