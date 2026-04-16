<?php 
use DevChefPress\Helpers\WeekCalculator;
// use DevChefPress\Models\UserSubscription;

// $dummy_subscription = UserSubscription::get_by_id(1);
// echo "<pre>";
// print_r($dummy_subscription);
// echo "</pre>";
// die;
// $order = wc_get_order(1868);
// echo "<pre>";
// print_r($order);
// $subscription_id = $order ? $order->get_meta('_subscription_id') : 0;
// echo "<pre>";
// echo "Subscription ID: " . $subscription_id . "\n";
// die;
?>

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

    <!-- Sticky Bottom Navigation -->
    <div id="dev_chefpress_plan_nav_bar" class="dev_chefpress_plan_nav_bar">
        <button id="dev_chefpress_plan_back_btn" class="dev_chefpress_plan_nav_btn dev_chefpress_plan_nav_btn_back" onclick="handleBack()" aria-label="Back">
            <i data-lucide="arrow-left"></i>
            <span class="dev_chefpress_plan_nav_text">Back</span>
        </button>
        <div id="dev_our_plans_slots_container" class="dev_our_plans_slots_container" style="display: none;" aria-label="Selected meal slots">
            <div class="dev_our_plans_slots_inner"></div>
        </div>
        <button id="dev_chefpress_plan_next_btn" class="dev_chefpress_plan_nav_btn dev_chefpress_plan_nav_btn_next" onclick="nextStep()" aria-label="Next">
            <span class="dev_chefpress_plan_nav_text">Next</span>
            <i data-lucide="arrow-right"></i>
        </button>
    </div>

    <!-- Hidden Weekly Menu Component (for Step 9) -->

</main>

<div id="dev_chefpress_weekly_menu_container" style="display: none;">
    <?php


    // Get all week terms
    $week_terms = get_terms([
        'taxonomy' => 'chefpress_week',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    // If no terms, use defaults
    if (empty($week_terms) || is_wp_error($week_terms)) {
        $week_terms = [
            (object) ['name' => 'Week 1'],
            (object) ['name' => 'Week 2'],
            (object) ['name' => 'Week 3'],
            (object) ['name' => 'Week 4'],
            (object) ['name' => 'Week 5'],
            (object) ['name' => 'Week 6'],
        ];
    }

    // Use all terms dynamically
    $display_terms = array_values($week_terms);
    $term_count = count($display_terms);

    // Get carousel weeks to display: up to 3 past + current + future (looping)
    $carousel_weeks = WeekCalculator::get_carousel_weeks($term_count);

    // Calculate date ranges for all base weeks (for API calls)
    $date_ranges = WeekCalculator::calculate_week_ranges($term_count);

    // Determine the currently active week (1-based index)
    $active_week_index = WeekCalculator::get_active_week_index($term_count);
   
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
    $meal_type_terms = get_terms( [
        'taxonomy' => 'chefpress_meal_type',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ] );
    ?>
    
    <!-- Banner -->
    <div class="cp_weekly_menu_banner">
        <span class="cp_weekly_menu_banner_text">
            <?php
            $current_range = $date_ranges[$active_week_index] ?? $date_ranges[1];
            echo esc_html('Choose from ' . count($display_terms) . ' recipes for the week of ' . $current_range['range'] . ' ' . $current_range['month']);
            ?>
        </span>
    </div>


    <div class="cp_weekly_menu_app">
        <div class="cp_weekly_menu_container">
            <!-- Filter Toolbar -->
             <input type="hidden" name="active_week_index" id="active_week_index" value="<?php echo esc_attr($active_week_index); ?>">
             <input type="hidden" name="week_date_ranges" id="week_date_ranges" value='<?php echo esc_attr(json_encode($date_ranges)); ?>'>
            <?php \DevChefPress\Frontend\WeeklyMenu\MenuComponents::render_filter_toolbar($recipe_tags, $meal_type_terms); ?>

            <!-- Recipe Grid -->
             <input type="hidden" name="enabled_add_to_slot" id="enabled_add_to_slot" value="true">
            <?php \DevChefPress\Frontend\WeeklyMenu\MenuComponents::render_recipe_grid(); ?>
        </div>


    </div>

</div>
<!-- Sidebar Overlay -->
<div class="cp_weekly_menu_sidebar_overlay" id="cp_weekly_sidebar_overlay"></div>

<!-- Sidebar -->
<div class="cp_weekly_menu_sidebar" id="cp_weekly_filter_sidebar">
    <div class="cp_weekly_menu_sidebar_header">
        <h2>Filter by</h2>
        <button class="cp_weekly_menu_sidebar_close" id="cp_weekly_close_sidebar_btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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