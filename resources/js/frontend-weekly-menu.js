/**
 * Dev ChefPress for WooCommerce — Frontend JS
 * Dual-mode filtering: Frontend (fast) and Backend (scalable)
 */
(function ($) {
    'use strict';

    /**
      * Dev ChefPress for WooCommerce — Frontend JS For Weekly Menu Page
      */

    const cp_weekly_recipes = [
        {
            id: 1,
            image: "https://picsum.photos/seed/fish1/600/450",
            category: "FISH",
            categoryIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8c0-3.3-2-6-6-6s-6 2.7-6 6c0 1.1.3 2.1.8 3L4 14l3 3 3-3c.9.5 1.9.8 3 .8 3.3 0 6-2 6-6Z"/><path d="M12 2v2"/><path d="M12 14v2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/><path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/></svg>',
            title: "Express: Seabream and Mediterranean Veg",
            subtitle: "with Dill Couscous and Herb Dressing",
            isNew: true,
            tags: [
                { label: "Express", icon: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>' },
                { label: "Calorie smart", icon: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>' }
            ],
            time: "20",
            calories: "495"
        },
        {
            id: 2,
            image: "https://picsum.photos/seed/soup1/600/450",
            category: "FISH",
            categoryIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8c0-3.3-2-6-6-6s-6 2.7-6 6c0 1.1.3 2.1.8 3L4 14l3 3 3-3c.9.5 1.9.8 3 .8 3.3 0 6-2 6-6Z"/><path d="M12 2v2"/><path d="M12 14v2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/><path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/></svg>',
            title: "Spicy Prawn Tom Kha",
            subtitle: "Thai Coconut Soup",
            isNew: false,
            tags: [
                { label: "Calorie smart", icon: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>' },
                { label: "Low carb", icon: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 2-2 2 4 4-4 4 2 2 6-6-6-6Z"/><path d="m8 20 2-2-4-4 4-4-2-2-6 6 6 6Z"/><path d="m15 5-9 14"/></svg>' }
            ],
            time: "30",
            calories: "434"
        },
        {
            id: 3,
            image: "https://picsum.photos/seed/chicken1/600/450",
            category: "POULTRY",
            categoryIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            title: "Lemon Garlic Chicken",
            subtitle: "with Greek Potato Salad",
            isNew: false,
            tags: [
                { label: "Calorie smart", icon: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>' },
                { label: "Tips for kids", icon: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' }
            ],
            time: "45",
            calories: "602"
        },
        {
            id: 4,
            image: "https://picsum.photos/seed/chicken2/600/450",
            category: "POULTRY",
            categoryIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            title: "Crispy Parmesan Chicken",
            subtitle: "with Vegetables and Caper Mayo",
            isNew: false,
            tags: [
                { label: "Calorie smart", icon: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>' },
                { label: "Low carb", icon: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 2-2 2 4 4-4 4 2 2 6-6-6-6Z"/><path d="m8 20 2-2-4-4 4-4-2-2-6 6 6 6Z"/><path d="m15 5-9 14"/></svg>' }
            ],
            time: "35",
            calories: "618"
        }
    ];

    const $cp_weekly_grid = $('#cp_weekly_recipe_grid');

    const renderRecipes = (recipes) => {
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

    // Initial render
    renderRecipes(cp_weekly_recipes);

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
            tags: $('.cp_weekly_menu_sidebar_btn[data-recipe-tag].active').map(function () {
                return $(this).data('recipe-tag');
            }).get(),
            allergens: $('.cp_weekly_menu_sidebar_btn[data-allergen].active').map(function () {
                return $(this).data('allergen');
            }).get(),
            sort: normalizeSortForApi($('#cp_weekly_sort_dropdown a.active').data('sort') || 'default'),
        };
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

        $.ajax({
            url: ChefPressConfig.ajax_url,
            type: 'POST',
            data: payload,
            dataType: 'json',
            success(response) {
                if (!response) {
                    $cp_weekly_grid.html('<p class="cp-no-results">No response from filter service.</p>');
                    return;
                }

                if (ChefPressConfig.filter_mode === 'backend' && response.html) {
                    $cp_weekly_grid.html(response.html);
                    return;
                }

                if (Array.isArray(response.recipes) && response.recipes.length) {
                    renderRecipes(response.recipes);
                    return;
                }

                if (response.html) {
                    $cp_weekly_grid.html(response.html);
                    return;
                }

                $cp_weekly_grid.html('<p class="cp-no-results">No recipes found in filter response.</p>');
            },
            error() {
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
    $('#cp_weekly_sidebar_clear').on('click', function (e) {
        e.preventDefault();
        $('.cp_weekly_menu_sidebar_btn').removeClass('active');
        $('#cp_weekly_sort_dropdown a').removeClass('active');
        $('#cp_weekly_sort_dropdown a[data-sort="default"]').addClass('active');
        $sortBtn.html('Sort by <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>');
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

    // Date Selection
    $('.cp_weekly_menu_date_item').on('click', function () {
        $('.cp_weekly_menu_date_item').removeClass('active');
        $(this).addClass('active');
    });

})(jQuery);

