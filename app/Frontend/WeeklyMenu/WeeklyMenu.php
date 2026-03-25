<?php
declare( strict_types=1 );

// Get all week terms
$week_terms = get_terms( [
    'taxonomy' => 'chefpress_week',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
] );

// If no terms, use defaults
if ( empty( $week_terms ) || is_wp_error( $week_terms ) ) {
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
$display_terms = array_values( $week_terms );
$term_count = count( $display_terms );

// Calculate date ranges starting from current week (one per term)
$current_date = new DateTime();
$current_date->setISODate( (int) $current_date->format('o'), (int) $current_date->format('W') );
$monday = clone $current_date;
$monday->modify('monday this week');

$date_ranges = [];
for ( $i = 0; $i < $term_count; $i++ ) {
    $start = clone $monday;
    $start->modify('+' . ($i * 7) . ' days');
    $end = clone $start;
    $end->modify('+6 days');
    
    $start_day = $start->format('j');
    $end_day = $end->format('j');
    $month = $start->format('M');
    $end_month = $end->format('M');
    
    if ( $month === $end_month ) {
        $range = $start_day . ' – ' . $end_day;
        $month_display = $month;
    } else {
        $range = $start_day . ' – ' . $end_day;
        $month_display = $month . ' – ' . $end_month;
    }
    
    $date_ranges[] = [
        'range' => $range,
        'month' => $month_display,
    ];
}

// Fetch dynamic terms for sidebar
$product_cats = get_terms( [
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
] );

$recipe_tags = get_terms( [
    'taxonomy' => 'chefpress_recipe_tag',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
] );

$allergen_tags = get_terms( [
    'taxonomy' => 'chefpress_allergen_tag',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
] );
?>

<div class="cp_weekly_menu_app">
    <!-- Date Navigation -->
    <div class="cp_weekly_menu_container">
        <nav class="cp_weekly_menu_date_nav">
            <button class="cp_weekly_menu_nav_btn" id="cp_weekly_date_prev">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6" />
                </svg>
            </button>

            <div class="cp_weekly_menu_date_viewport">
                <div class="cp_weekly_menu_date_track" id="cp_weekly_date_track">
                    <?php foreach ( $display_terms as $index => $term ): ?>
                        <div class="cp_weekly_menu_date_item <?php echo $index === 0 ? 'active' : 'future'; ?>">
                            <span class="cp_weekly_menu_date_range"><?php echo esc_html( $date_ranges[$index]['range'] ); ?></span>
                            <span class="cp_weekly_menu_date_month"><?php echo esc_html( $date_ranges[$index]['month'] ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button class="cp_weekly_menu_nav_btn" id="cp_weekly_date_next">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
        </nav>
    </div>

    <!-- Banner -->
    <div class="cp_weekly_menu_banner" style="display: none !important;">
        <span class="cp_weekly_menu_banner_text">
            Choose from 39 recipes for the week of <?php echo esc_html( $date_ranges[0]['range'] . ' ' . $date_ranges[0]['month'] ); ?>
        </span>
        <button class="cp_weekly_menu_banner_btn">
            Add-ons available!
        </button>
    </div>

    <div class="cp_weekly_menu_container">
        <!-- Filters -->
        <div class="cp_weekly_menu_filters_row">
            <button class="cp_weekly_menu_filter_btn" id="cp_weekly_open_sidebar_btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                </svg> Filter
            </button>
            <div class="cp_weekly_menu_dropdown">
                <button type="button" class="cp_weekly_menu_filter_btn" id="cp_weekly_sort_btn">
                    Sort by <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="cp_weekly_menu_dropdown_content" id="cp_weekly_sort_dropdown">
                    <a href="#" data-sort="default">Default</a>
                    <a href="#" data-sort="calories-asc">Calories: Low to High</a>
                    <a href="#" data-sort="carbs-asc">Carbs: Low to High</a>
                    <a href="#" data-sort="time-asc">Cooking Time: Low to High</a>
                    <a href="#" data-sort="protein-desc">Protein: High to Low</a>
                </div>
            </div>
            <button class="cp_weekly_menu_filter_btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    style="color: #f97316;">
                    <path
                        d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" />
                </svg> Express
            </button>
            <button class="cp_weekly_menu_filter_btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    style="color: #ef4444;">
                    <path
                        d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z" />
                </svg> Calorie Smart
            </button>
            <button class="cp_weekly_menu_filter_btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    style="color: #ca8a04;">
                    <path d="m16 2-2 2 4 4-4 4 2 2 6-6-6-6Z" />
                    <path d="m8 20 2-2-4-4 4-4-2-2-6 6 6 6Z" />
                    <path d="m15 5-9 14" />
                </svg> Low Carb
            </button>
            <button class="cp_weekly_menu_filter_btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    style="color: #3b82f6;">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg> Family Friendly
            </button>
            <button class="cp_weekly_menu_filter_btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    style="color: #22c55e;">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
                    <path
                        d="M2 21c0-3 1.85-5.36 5.08-6C10.9 14.23 12 14 15 12c-2 2.31-2.89 3.59-3 5.42-.01 1.44.88 2.4 3 3.58" />
                </svg> Veg/Vegan
            </button>
            <a href="#" class="cp_weekly_menu_view_all" id="cp_weekly_view_all_link">View all &gt;</a>
        </div>

        <!-- Recipe Grid -->
        <div id="cp_weekly_recipe_grid" class="cp_weekly_menu_grid">
            <!-- Recipes will be injected here by JS -->
        </div>
    </div>

    <!-- Sticky Footer -->
    <div class="cp_weekly_menu_sticky_footer">
        <a href="#" class="cp_weekly_menu_sticky_btn">
            <span class="cp_weekly_menu_sticky_btn_title">Try Hello Chef Now</span>
            <span class="cp_weekly_menu_sticky_btn_subtitle">Order these recipes to your door</span>
        </a>
    </div>
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

    <div class="cp_weekly_menu_sidebar_content">
        <!-- Main Protein -->
        <div class="cp_weekly_menu_sidebar_section">
            <h3 class="cp_weekly_menu_sidebar_section_title">Main Protein</h3>
            <div class="cp_weekly_menu_sidebar_grid">
                <?php foreach ( $product_cats as $cat ): ?>
                    <button class="cp_weekly_menu_sidebar_btn" data-category="<?php echo esc_attr( $cat->slug ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                        </svg> <?php echo esc_html( $cat->name ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 2rem;">

        <!-- Recipe Features -->
        <div class="cp_weekly_menu_sidebar_section">
            <h3 class="cp_weekly_menu_sidebar_section_title">
                Recipe Features <span class="cp_weekly_menu_sidebar_badge_new">NEW</span>
            </h3>
            <div class="cp_weekly_menu_sidebar_grid">
                <?php foreach ( $recipe_tags as $tag ): ?>
                    <button class="cp_weekly_menu_sidebar_btn" data-recipe-tag="<?php echo esc_attr( $tag->slug ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg> <?php echo esc_html( $tag->name ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 2rem;">

        <!-- Allergens -->
        <div class="cp_weekly_menu_sidebar_section">
            <h3 class="cp_weekly_menu_sidebar_section_title">Allergens</h3>
            <div class="cp_weekly_menu_sidebar_grid">
                <?php $count = 0; foreach ( $allergen_tags as $allergen ): $count++; ?>
                    <button class="cp_weekly_menu_sidebar_btn <?php echo $count > 6 ? 'cp_weekly_menu_sidebar_btn_hidden' : ''; ?>" data-allergen="<?php echo esc_attr( $allergen->slug ); ?>">
                        No <?php echo esc_html( $allergen->name ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <button class="cp_weekly_menu_sidebar_show_more" id="cp_weekly_show_more_allergens">
                Show more allergens <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
            <p class="cp_weekly_menu_sidebar_disclaimer">
                Due to production methods, we cannot guarantee our products are completely free from any allergen
                such as <strong>Peanuts, Tree Nuts, Sesame Seeds, Milk, Egg, Fish, Crustaceans, Molluscs, Soya,
                    Wheat, Gluten, Lupin, Mustard, Sulphur dioxide and Celery.</strong>
            </p>
        </div>
    </div>

    <div class="cp_weekly_menu_sidebar_footer">
        <button class="cp_weekly_menu_sidebar_clear">Clear all</button>
        <button class="cp_weekly_menu_sidebar_apply">Apply filters</button>
    </div>
</div>