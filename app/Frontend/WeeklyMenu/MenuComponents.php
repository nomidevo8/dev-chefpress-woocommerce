<?php
declare( strict_types=1 );

namespace DevChefPress\Frontend\WeeklyMenu;

class MenuComponents {

	public static function render_filter_toolbar( array $recipe_tags, array $meal_type_terms = [] ): void {
		?>
		<div class="cp_weekly_menu_filters_row">
			<button class="cp_weekly_menu_filter_btn" id="cp_weekly_open_sidebar_btn">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
				Filter
			</button>
			<div class="cp_weekly_menu_dropdown">
				<button type="button" class="cp_weekly_menu_filter_btn" id="cp_weekly_sort_btn">
					Sort by <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
				</button>
				<div class="cp_weekly_menu_dropdown_content" id="cp_weekly_sort_dropdown">
					<a href="#" data-sort="default" class="active">Default</a>
					<a href="#" data-sort="calories:ASC">Calories: Low to High</a>
					<a href="#" data-sort="carbs:ASC">Carbs: Low to High</a>
					<a href="#" data-sort="time:ASC">Cooking Time: Low to High</a>
					<a href="#" data-sort="protein:DESC">Protein: High to Low</a>
				</div>
			</div>
			
			<?php if ( ! empty( $meal_type_terms ) ) : ?>
				<div class="cp_weekly_menu_dropdown">
					<button type="button" class="cp_weekly_menu_filter_btn" id="cp_weekly_mealtype_btn">
						Meal Type <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
					</button>
					<div class="cp_weekly_menu_dropdown_content" id="cp_weekly_mealtype_dropdown">
						<a href="#" data-meal-type="" class="cp_weekly_mealtype_option active">All</a>
						<?php foreach ( $meal_type_terms as $meal_type ) : ?>
							<a href="#" data-meal-type="<?php echo esc_attr( $meal_type->slug ); ?>" class="cp_weekly_mealtype_option">
								<?php echo esc_html( $meal_type->name ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			
			<?php $top_recipe_tags = array_slice( $recipe_tags, 0, 5 ); ?>
			<?php foreach ( $top_recipe_tags as $tag ) : ?>
				<button class="cp_weekly_menu_filter_btn" data-recipe-tag="<?php echo esc_attr( $tag->slug ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #f97316;"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
					<?php echo esc_html( $tag->name ); ?>
				</button>
			<?php endforeach; ?>
			<a href="#" class="cp_weekly_menu_view_all" id="cp_weekly_view_all_link">View all &gt;</a>
		</div>
		<?php
	}

	public static function render_recipe_grid(): void {
		?>
		<div id="cp_weekly_recipe_grid" class="cp_weekly_menu_grid"></div>
		<div id="cp_weekly_pagination" class="cp_weekly_menu_pagination"></div>
		<?php
	}

	public static function render_sidebar( array $product_cats, array $recipe_tags, array $allergen_tags ): void {
		?>
		<div class="cp_weekly_menu_sidebar_content">
			<div class="cp_weekly_menu_sidebar_section">
				<h3 class="cp_weekly_menu_sidebar_section_title">Main Protein</h3>
				<div class="cp_weekly_menu_sidebar_grid">
					<?php foreach ( $product_cats as $cat ) : ?>
						<button class="cp_weekly_menu_sidebar_btn" data-category="<?php echo esc_attr( $cat->slug ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
							<?php echo esc_html( $cat->name ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<hr style="border:0;border-top:1px solid #eee;margin-bottom:2rem;" />

			<div class="cp_weekly_menu_sidebar_section">
				<h3 class="cp_weekly_menu_sidebar_section_title">Recipe Features <span class="cp_weekly_menu_sidebar_badge_new">NEW</span></h3>
				<div class="cp_weekly_menu_sidebar_grid">
					<?php foreach ( $recipe_tags as $tag ) : ?>
						<button class="cp_weekly_menu_sidebar_btn" data-recipe-tag="<?php echo esc_attr( $tag->slug ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
							<?php echo esc_html( $tag->name ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<hr style="border:0;border-top:1px solid #eee;margin-bottom:2rem;" />

			<div class="cp_weekly_menu_sidebar_section">
				<h3 class="cp_weekly_menu_sidebar_section_title">Allergens</h3>
				<div class="cp_weekly_menu_sidebar_grid">
					<?php $count = 0; foreach ( $allergen_tags as $allergen ) : $count++; ?>
						<button class="cp_weekly_menu_sidebar_btn <?php echo $count > 6 ? 'cp_weekly_menu_sidebar_btn_hidden' : ''; ?>" data-allergen="<?php echo esc_attr( $allergen->slug ); ?>">
							No <?php echo esc_html( $allergen->name ); ?>
						</button>
					<?php endforeach; ?>
				</div>
				<button class="cp_weekly_menu_sidebar_show_more" id="cp_weekly_show_more_allergens">Show more allergens <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></button>
				<p class="cp_weekly_menu_sidebar_disclaimer">Due to production methods, we cannot guarantee our products are completely free from any allergen such as <strong>Peanuts, Tree Nuts, Sesame Seeds, Milk, Egg, Fish, Crustaceans, Molluscs, Soya, Wheat, Gluten, Lupin, Mustard, Sulphur dioxide and Celery.</strong></p>
			</div>
		</div>
		<?php
	}
}
