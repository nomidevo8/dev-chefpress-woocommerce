<main id="dev_chefpress_plan_main">
    <div id="dev_chefpress_plan_wizard-container" class="dev_chefpress_plan_glass-card">
        <!-- Progress Header -->
        <div id="dev_chefpress_plan_progress-container">
            <div class="dev_chefpress_plan_logo-area">
            </div>

            <div class="dev_chefpress_plan_progress-right">
                <div class="dev_chefpress_plan_progress-label dev_chefpress_plan_sm-block">
                    <p class="dev_chefpress_plan_label-title">Current Step</p>
                    <p id="dev_chefpress_plan_step-label">Goal Selection</p>
                </div>
                <div class="dev_chefpress_plan_progress-ring-wrap">
                    <svg viewBox="0 0 48 48">
                        <circle id="dev_chefpress_plan_progress-circle" cx="24" cy="24" r="20" stroke="#10b981"
                            stroke-width="4" fill="transparent" stroke-dasharray="125.6" stroke-dashoffset="125.6"
                            style="transition: stroke-dashoffset 0.5s ease;" />
                    </svg>
                    <span id="dev_chefpress_plan_step-number">1/15</span>
                </div>
            </div>
        </div>

        <!-- Step Content -->
        <div id="dev_chefpress_plan_step-content" class="dev_chefpress_plan_step-transition"></div>
    </div>

    <!-- Hidden Weekly Menu Component (for Step 9) -->
    <div id="dev_chefpress_weekly_menu_container" style="display: none;">
        <?php
        // Get dynamic terms for the weekly menu
        $product_cats = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        $recipe_tags = get_terms([
            'taxonomy' => 'chefpress_recipe_tag',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        $allergen_tags = get_terms([
            'taxonomy' => 'chefpress_allergen_tag',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        ?>
        
        <div class="cp_weekly_menu_app">
            <div class="cp_weekly_menu_container">
                <!-- Filter Toolbar -->
                <?php \DevChefPress\Frontend\WeeklyMenu\MenuComponents::render_filter_toolbar($recipe_tags); ?>
                
                <!-- Recipe Grid -->
                <?php \DevChefPress\Frontend\WeeklyMenu\MenuComponents::render_recipe_grid(); ?>
            </div>

            <!-- Sidebar Overlay -->
            <div class="cp_weekly_menu_sidebar_overlay" id="cp_weekly_sidebar_overlay"></div>

            <!-- Sidebar -->
            <div class="cp_weekly_menu_sidebar" id="cp_weekly_filter_sidebar">
                <div class="cp_weekly_menu_sidebar_header">
                    <h2>Filter by</h2>
                    <button class="cp_weekly_menu_sidebar_close" id="cp_weekly_close_sidebar_btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <?php \DevChefPress\Frontend\WeeklyMenu\MenuComponents::render_sidebar($product_cats, $recipe_tags, $allergen_tags); ?>

                <div class="cp_weekly_menu_sidebar_footer">
                    <button class="cp_weekly_menu_sidebar_clear">Clear all</button>
                    <button class="cp_weekly_menu_sidebar_apply">Apply filters</button>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="dev_chefpress_plan_flex dev_chefpress_plan_justify-between dev_chefpress_plan_mt-8">
            <button onclick="prevStep()" class="dev_chefpress_plan_btn-outline">Back</button>
            <button onclick="nextStep()" class="dev_chefpress_plan_btn-primary">Review Order</button>
        </div>
    </div>
</main>