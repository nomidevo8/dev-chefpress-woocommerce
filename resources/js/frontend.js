/**
 * Dev ChefPress for WooCommerce — Frontend JS
 * Dual-mode filtering: Frontend (fast) and Backend (scalable)
 */
(function ($) {
    'use strict';

    /**
     * Dev ChefPress for WooCommerce — Frontend JS For Woocommerce Single Page = Recipe Product
     */
    
    if (window.lucide) {
        lucide.createIcons();
    }

    /**
     * Unified Filter Structure
     */
    var FilterState = {
        week:       '',
        category:   '',
        tags:       [],
        allergens:  [],
        sort:       '',
        page:       1
    };

    /**
     * Frontend Mode: Load all recipes once, filter in JS
     */
    var FrontendMode = {
        recipes: [],
        filteredRecipes: [],

        init: function() {
            // Load initial recipes for selected week
            this.loadRecipes();
            this.bindEvents();
        },

        loadRecipes: function() {
            var self = this;
            var filters = $.extend({}, FilterState);

            if (!filters.week) {
                return;
            }

            $.ajax({
                url: ChefPressConfig.ajax_url,
                type: 'POST',
                data: {
                    action: 'chefpress_filter_recipes',
                    nonce: ChefPressConfig.nonce,
                    filters: filters
                },
                success: function(response) {
                    self.recipes = response.recipes || [];
                    self.applyFilters();
                }
            });
        },

        applyFilters: function() {
            this.filteredRecipes = this.recipes.slice();

            // Filter by category, tags, allergens (in JS)
            if (FilterState.category) {
                this.filteredRecipes = this.filteredRecipes.filter(function(r) {
                    return r.categories.indexOf(FilterState.category) !== -1;
                });
            }

            if (FilterState.tags.length > 0) {
                this.filteredRecipes = this.filteredRecipes.filter(function(r) {
                    return FilterState.tags.some(function(tag) {
                        return r.tags.indexOf(tag) !== -1;
                    });
                });
            }

            if (FilterState.allergens.length > 0) {
                this.filteredRecipes = this.filteredRecipes.filter(function(r) {
                    return !FilterState.allergens.some(function(allergen) {
                        return r.allergens.indexOf(allergen) !== -1;
                    });
                });
            }

            // Apply sorting
            if (FilterState.sort) {
                this.applySorting();
            }

            // Apply pagination
            this.applyPagination();
            this.render();
        },

        applySorting: function() {
            var sortParts = FilterState.sort.split(':');
            var sortKey = sortParts[0];
            var sortDir = (sortParts[1] || 'ASC').toUpperCase();

            var self = this;
            this.filteredRecipes.sort(function(a, b) {
                var aVal = parseFloat(a[sortKey]) || 0;
                var bVal = parseFloat(b[sortKey]) || 0;
                return sortDir === 'ASC' ? aVal - bVal : bVal - aVal;
            });
        },

        applyPagination: function() {
            // Could implement pagination in JS if needed
            // For now, just use page 1
        },

        render: function() {
            // Render filtered recipes to DOM
            var html = '<div class="cp-recipes-grid">';

            if (this.filteredRecipes.length === 0) {
                html = '<p class="cp-no-results">' + ChefPressConfig.strings.noResults + '</p>';
            } else {
                this.filteredRecipes.forEach(function(recipe) {
                    html += self.renderRecipeCard(recipe);
                });
            }

            html += '</div>';
            $('.cp-recipes-container').html(html);
        },

        renderRecipeCard: function(recipe) {
            // Render a single recipe card from data object
            return '<article class="cp-recipe-card">' +
                '  <div class="cp-recipe-card__image">' +
                '    <img src="' + recipe.image + '" alt="' + recipe.title + '" />' +
                '  </div>' +
                '  <div class="cp-recipe-card__body">' +
                '    <h3 class="cp-recipe-card__title">' + recipe.title + '</h3>' +
                (recipe.subtitle ? '    <p class="cp-recipe-card__subtitle">' + recipe.subtitle + '</p>' : '') +
                (recipe.cookingTime ? '    <p class="cp-recipe-card__meta">⏱ ' + recipe.cookingTime + '</p>' : '') +
                '    <div class="cp-recipe-card__nutrition">' +
                (recipe.calories ? '      <span class="cp-nutrition-badge">' + recipe.calories + ' kcal</span>' : '') +
                (recipe.protein ? '      <span class="cp-nutrition-badge">' + recipe.protein + 'g protein</span>' : '') +
                '    </div>' +
                '    <a href="' + recipe.url + '" class="cp-btn cp-btn--primary cp-btn--sm">' + ChefPressConfig.strings.viewRecipe + '</a>' +
                '  </div>' +
                '</article>';
        },

        bindEvents: function() {
            var self = this;

            // Filter change handlers (debounced)
            $(document).on('change', '.cp-filter-category, .cp-filter-tags, .cp-filter-allergens, .cp-filter-sort', function() {
                self.updateFilter($(this));
                setTimeout(function() { self.applyFilters(); }, 300);
            });
        },

        updateFilter: function($el) {
            var filterType = $el.data('filter-type');
            var value = $el.val();

            switch (filterType) {
                case 'category':
                    FilterState.category = value;
                    break;
                case 'tags':
                    FilterState.tags = value ? [value] : [];
                    break;
                case 'allergens':
                    FilterState.allergens = value ? [value] : [];
                    break;
                case 'sort':
                    FilterState.sort = value;
                    break;
            }
        }
    };

    /**
     * Backend Mode: AJAX on every filter change
     */
    var BackendMode = {
        debounceTimer: null,

        init: function() {
            this.bindEvents();
            this.loadRecipes();
        },

        bindEvents: function() {
            var self = this;

            $(document).on('change', '.cp-filter-category, .cp-filter-tags, .cp-filter-allergens, .cp-filter-sort', function() {
                self.updateFilter($(this));
                self.debounceLoad();
            });

            $(document).on('click', '.cp-pagination a', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                FilterState.page = page;
                self.loadRecipes();
            });
        },

        updateFilter: function($el) {
            var filterType = $el.data('filter-type');
            var value = $el.val();

            switch (filterType) {
                case 'category':
                    FilterState.category = value;
                    break;
                case 'tags':
                    FilterState.tags = value ? [value] : [];
                    break;
                case 'allergens':
                    FilterState.allergens = value ? [value] : [];
                    break;
                case 'sort':
                    FilterState.sort = value;
                    break;
                case 'week':
                    FilterState.week = value;
                    FilterState.page = 1;
                    break;
            }
        },

        debounceLoad: function() {
            var self = this;
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(function() {
                self.loadRecipes();
            }, 300);
        },

        loadRecipes: function() {
            var self = this;
            var filters = $.extend({}, FilterState);

            if (!filters.week) {
                return;
            }

            // Show loading state
            $('.cp-recipes-container').addClass('is-loading');

            $.ajax({
                url: ChefPressConfig.ajax_url,
                type: 'POST',
                data: {
                    action: 'chefpress_filter_recipes',
                    nonce: ChefPressConfig.nonce,
                    filters: filters
                },
                success: function(response) {
                    $('.cp-recipes-container').html(response.html).removeClass('is-loading');
                    self.updatePagination(response);
                },
                error: function() {
                    $('.cp-recipes-container').removeClass('is-loading');
                }
            });
        },

        updatePagination: function(response) {
            var html = '';
            if (response.total_pages > 1) {
                html = '<div class="cp-pagination">';
                for (var i = 1; i <= response.total_pages; i++) {
                    var activeClass = i === response.current_page ? ' is-active' : '';
                    html += '<a href="#" class="cp-pagination__link' + activeClass + '" data-page="' + i + '">' + i + '</a>';
                }
                html += '</div>';
            }
            $('.cp-pagination-container').html(html);
        }
    };

    /**
     * Auto Mode: Choose between frontend and backend based on data size
     */
    var AutoMode = {
        init: function() {
            var self = this;
            var filters = $.extend({}, FilterState, { page: 1 });

            $.ajax({
                url: ChefPressConfig.ajax_url,
                type: 'POST',
                data: {
                    action: 'chefpress_filter_recipes',
                    nonce: ChefPressConfig.nonce,
                    filters: filters
                },
                success: function(response) {
                    if (response.recipes_count <= 50) {
                        FrontendMode.recipes = response.recipes || [];
                        FrontendMode.init();
                    } else {
                        BackendMode.init();
                    }
                }
            });
        }
    };

    /**
     * Initialize filtering based on mode
     */
    $(document).ready(function() {
        if (!ChefPressConfig || !ChefPressConfig.filter_mode) {
            return;
        }

        // Set initial week from page context or select
        var $weekSelect = $('.cp-filter-week');
        if ($weekSelect.length) {
            FilterState.week = $weekSelect.val();
        }

        switch (ChefPressConfig.filter_mode) {
            case 'frontend':
                FrontendMode.init();
                break;
            case 'backend':
                BackendMode.init();
                break;
            case 'auto':
                AutoMode.init();
                break;
        }
    });

})(jQuery);

