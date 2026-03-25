<?php
declare( strict_types=1 );

use DevChefPress\Helpers\WeekCalculator;

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

// Calculate date ranges using the WeekCalculator with configurable start date
$date_ranges = WeekCalculator::calculate_week_ranges( $term_count );

// Determine the currently active week (1-based index)
$active_week_index = WeekCalculator::get_active_week_index( $term_count );

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
                        <div class="cp_weekly_menu_date_item <?php echo ( $index + 1 ) === $active_week_index ? 'active' : 'future'; ?>" data-week="<?php echo esc_attr( (string) ( $index + 1 ) ); ?>">
                            <span class="cp_weekly_menu_date_range"><?php echo esc_html( $date_ranges[ $index + 1 ]['range'] ); ?></span>
                            <span class="cp_weekly_menu_date_month"><?php echo esc_html( $date_ranges[ $index + 1 ]['month'] ); ?></span>
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
    <div class="cp_weekly_menu_banner" >
        <span class="cp_weekly_menu_banner_text">
            <?php 
                $current_range = $date_ranges[ $active_week_index ] ?? $date_ranges[1];
                echo esc_html( 'Choose from ' . count( $display_terms ) . ' recipes for the week of ' . $current_range['range'] . ' ' . $current_range['month'] );
            ?>
        </span>
    </div>

    <div class="cp_weekly_menu_container">
        <?php \DevChefPress\Frontend\WeeklyMenu\MenuComponents::render_filter_toolbar( $recipe_tags ); ?>
        <?php \DevChefPress\Frontend\WeeklyMenu\MenuComponents::render_recipe_grid(); ?>
    </div>

    <!-- Sticky Footer -->
    <div class="cp_weekly_menu_sticky_footer">
        <a href="#" class="cp_weekly_menu_sticky_btn">
            <span class="cp_weekly_menu_sticky_btn_title">Try AOS Fresh Now</span>
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

    <?php \DevChefPress\Frontend\WeeklyMenu\MenuComponents::render_sidebar( $product_cats, $recipe_tags, $allergen_tags ); ?>

    <div class="cp_weekly_menu_sidebar_footer">
        <button class="cp_weekly_menu_sidebar_clear">Clear all</button>
        <button class="cp_weekly_menu_sidebar_apply">Apply filters</button>
    </div>
</div>