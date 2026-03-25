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
            <button class="cp_weekly_menu_filter_btn">
                Sort by <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
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
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#00a0d2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M18 8c0-3.3-2-6-6-6s-6 2.7-6 6c0 1.1.3 2.1.8 3L4 14l3 3 3-3c.9.5 1.9.8 3 .8 3.3 0 6-2 6-6Z" />
                    </svg> Fish
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                    </svg> Poultry
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v10" />
                        <path d="M18 17c0 1-2 2-6 2s-6-1-6-2" />
                        <path d="M12 12c-3.3 0-6 2-6 4.5s2.7 4.5 6 4.5 6-2 6-4.5-2.7-4.5-6-4.5Z" />
                    </svg> Meat
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <circle cx="12" cy="12" r="3" fill="#22c55e" />
                    </svg> Veg/Vegan
                </button>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 2rem;">

        <!-- Recipe Features -->
        <div class="cp_weekly_menu_sidebar_section">
            <h3 class="cp_weekly_menu_sidebar_section_title">
                Recipe Features <span class="cp_weekly_menu_sidebar_badge_new">NEW</span>
            </h3>
            <div class="cp_weekly_menu_sidebar_grid">
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v2" />
                        <path d="M12 18v4" />
                        <path d="M4.93 4.93l1.41 1.41" />
                        <path d="M17.66 17.66l1.41 1.41" />
                        <path d="M2 12h2" />
                        <path d="M20 12h2" />
                        <path d="M6.34 17.66l-1.41 1.41" />
                        <path d="M19.07 4.93l-1.41 1.41" />
                    </svg> Air Fryer
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#db2777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z" />
                    </svg> Chef's Choice
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg> Weekly Classic
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg> Family Friendly
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M8 14s1.5 2 4 2 4-2 4-2" />
                        <line x1="9" y1="9" x2="9.01" y2="9" />
                        <line x1="15" y1="9" x2="15.01" y2="9" />
                    </svg> Tips For Kids
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                        <path d="M2 12h20" />
                        <path d="M12 2a14.5 14.5 0 0 1 0 20 14.5 14.5 0 0 1 0-20" />
                    </svg> Global Eats
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#ca8a04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m16 2-2 2 4 4-4 4 2 2 6-6-6-6Z" />
                        <path d="m8 20 2-2-4-4 4-4-2-2-6 6 6 6Z" />
                        <path d="m15 5-9 14" />
                    </svg> Low Carb
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#a855f7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z" />
                    </svg> Calorie Smart
                </button>
                <button class="cp_weekly_menu_sidebar_btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" />
                    </svg> Express
                </button>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 2rem;">

        <!-- Allergens -->
        <div class="cp_weekly_menu_sidebar_section">
            <h3 class="cp_weekly_menu_sidebar_section_title">Allergens</h3>
            <div class="cp_weekly_menu_sidebar_grid">
                <button class="cp_weekly_menu_sidebar_btn">No Peanuts</button>
                <button class="cp_weekly_menu_sidebar_btn">No Gluten</button>
                <button class="cp_weekly_menu_sidebar_btn">No Tree Nuts</button>
                <button class="cp_weekly_menu_sidebar_btn">No Wheat</button>
                <button class="cp_weekly_menu_sidebar_btn">No Milk</button>
                <button class="cp_weekly_menu_sidebar_btn">No Crustaceans</button>
            </div>
            <button class="cp_weekly_menu_sidebar_show_more">
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