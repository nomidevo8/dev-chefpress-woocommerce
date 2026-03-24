<?php
/**
 * Test Recipe Generator Script
 * 
 * Generates 100 recipe products with varied data for API testing.
 * Run this from WordPress root: wp eval-file generate-test-recipes.php
 * 
 * Or include in your plugin and call: generate_test_recipes();
 */

// Make sure WP is loaded
if ( ! function_exists( 'wp_insert_post' ) ) {
	require_once( dirname( __DIR__, 3 ) . '/wp-load.php' );
}


function generate_test_recipes() {
	// Check if WooCommerce is active
	if ( ! function_exists( 'wc_get_product' ) ) {
		echo "ERROR: WooCommerce is not active.\n";
		return;
	}

	// Sample data arrays for variety
	$recipe_templates = [
		[
			'title_base'     => 'Pasta',
			'subtitles'      => [ 'Creamy Alfredo', 'Carbonara Style', 'Marinara Delight', 'Pesto Fresh', 'Aglio e Olio' ],
			'cooking_times'  => [ '15 mins', '20 mins', '25 mins' ],
			'calories_range' => [ 350, 550 ],
			'protein_range'  => [ 12, 18 ],
			'carbs_range'    => [ 45, 65 ],
			'fat_range'      => [ 8, 15 ],
			'tags'           => [ 'quick', 'italian', 'family-friendly' ],
		],
		[
			'title_base'     => 'Chicken',
			'subtitles'      => [ 'Grilled Herb', 'BBQ Glazed', 'Mediterranean', 'Crispy Fried', 'Lemon Garlic' ],
			'cooking_times'  => [ '25 mins', '30 mins', '35 mins', '40 mins' ],
			'calories_range' => [ 280, 420 ],
			'protein_range'  => [ 35, 45 ],
			'carbs_range'    => [ 5, 20 ],
			'fat_range'      => [ 8, 16 ],
			'tags'           => [ 'protein-rich', 'low-carb', 'healthy' ],
		],
		[
			'title_base'     => 'Salad',
			'subtitles'      => [ 'Greek Fresh', 'Caesar Classic', 'Kale Power', 'Caprese Summer', 'Rainbow Vegetables' ],
			'cooking_times'  => [ '5 mins', '10 mins', '15 mins' ],
			'calories_range' => [ 150, 280 ],
			'protein_range'  => [ 8, 15 ],
			'carbs_range'    => [ 15, 30 ],
			'fat_range'      => [ 8, 14 ],
			'tags'           => [ 'vegan', 'healthy', 'quick', 'vegetarian' ],
		],
		[
			'title_base'     => 'Fish',
			'subtitles'      => [ 'Baked Salmon', 'Grilled Tuna', 'Pan-seared Cod', 'Fish Tacos', 'Mediterranean Sea Bass' ],
			'cooking_times'  => [ '20 mins', '25 mins', '30 mins' ],
			'calories_range' => [ 220, 350 ],
			'protein_range'  => [ 30, 40 ],
			'carbs_range'    => [ 5, 25 ],
			'fat_range'      => [ 8, 15 ],
			'tags'           => [ 'seafood', 'healthy', 'omega-3', 'lean' ],
		],
		[
			'title_base'     => 'Soup',
			'subtitles'      => [ 'Tomato Basil', 'Minestrone', 'Chicken Noodle', 'Lentil Power', 'Mushroom Bisque' ],
			'cooking_times'  => [ '20 mins', '30 mins', '40 mins', '45 mins' ],
			'calories_range' => [ 120, 250 ],
			'protein_range'  => [ 6, 14 ],
			'carbs_range'    => [ 15, 35 ],
			'fat_range'      => [ 3, 10 ],
			'tags'           => [ 'comfort-food', 'vegetarian', 'warm' ],
		],
		[
			'title_base'     => 'Stir-Fry',
			'subtitles'      => [ 'Vegetable Mix', 'Beef Satay', 'Tofu Ginger', 'Shrimp Garlic', 'Broccoli Sesame' ],
			'cooking_times'  => [ '15 mins', '20 mins', '25 mins' ],
			'calories_range' => [ 280, 420 ],
			'protein_range'  => [ 18, 28 ],
			'carbs_range'    => [ 20, 35 ],
			'fat_range'      => [ 10, 18 ],
			'tags'           => [ 'asian', 'quick', 'vegetable-based' ],
		],
		[
			'title_base'     => 'Burger',
			'subtitles'      => [ 'Classic Beef', 'Turkey Lite', 'Veggie Patty', 'Mushroom Swiss', 'Spicy Jalapeño' ],
			'cooking_times'  => [ '10 mins', '15 mins', '20 mins' ],
			'calories_range' => [ 450, 650 ],
			'protein_range'  => [ 25, 35 ],
			'carbs_range'    => [ 35, 50 ],
			'fat_range'      => [ 15, 25 ],
			'tags'           => [ 'comfort-food', 'family-friendly', 'casual' ],
		],
		[
			'title_base'     => 'Bowl',
			'subtitles'      => [ 'Power Buddha', 'Poke Ahi', 'Grain Harvest', 'Burrito', 'Quinoa Superfood' ],
			'cooking_times'  => [ '10 mins', '15 mins', '20 mins' ],
			'calories_range' => [ 350, 550 ],
			'protein_range'  => [ 15, 25 ],
			'carbs_range'    => [ 45, 65 ],
			'fat_range'      => [ 10, 18 ],
			'tags'           => [ 'trendy', 'balanced', 'customizable' ],
		],
		[
			'title_base'     => 'Pizza',
			'subtitles'      => [ 'Margherita', 'Pepperoni Classic', 'BBQ Chicken', 'Veggie Loaded', 'Truffle Mushroom' ],
			'cooking_times'  => [ '15 mins', '20 mins', '25 mins' ],
			'calories_range' => [ 250, 400 ],
			'protein_range'  => [ 12, 18 ],
			'carbs_range'    => [ 30, 45 ],
			'fat_range'      => [ 10, 18 ],
			'tags'           => [ 'italian', 'family-friendly', 'weekend' ],
		],
		[
			'title_base'     => 'Smoothie',
			'subtitles'      => [ 'Berry Blast', 'Tropical Paradise', 'Green Power', 'Protein Shake', 'Açai Bowl' ],
			'cooking_times'  => [ '5 mins', '10 mins' ],
			'calories_range' => [ 200, 350 ],
			'protein_range'  => [ 10, 20 ],
			'carbs_range'    => [ 35, 50 ],
			'fat_range'      => [ 2, 8 ],
			'tags'           => [ 'breakfast', 'healthy', 'vegan', 'quick' ],
		],
	];

	// Common taxonomy values
	$weeks = [ 'week-1', 'week-2', 'week-3', 'week-4' ];
	$categories = [ 'appetizers', 'mains', 'sides', 'desserts', 'breakfast' ];
	$recipe_tags = [ 'quick', 'healthy', 'vegan', 'vegetarian', 'family-friendly', 'low-carb', 'protein-rich', 'comfort-food', 'italian', 'asian' ];
	$allergens = [ 'peanuts', 'tree-nuts', 'shellfish', 'fish', 'soy', 'gluten', 'dairy', 'eggs' ];

	// Cooking steps templates
	$step_templates = [
		[ 'Prepare and gather ingredients', 'Measure out all ingredients needed' ],
		[ 'Preheat cooking equipment', 'Set oven or stovetop to proper temperature' ],
		[ 'Mix dry ingredients', 'Combine all dry ingredients in a bowl' ],
		[ 'Combine wet ingredients', 'Mix all wet ingredients separately' ],
		[ 'Combine wet and dry', 'Gently fold wet mixture into dry ingredients' ],
		[ 'Cook or prepare', 'Place in oven or on stovetop as per instructions' ],
		[ 'Let rest', 'Allow food to rest for 2-3 minutes' ],
		[ 'Serve hot', 'Plate and serve immediately while hot' ],
		[ 'Garnish if desired', 'Add fresh herbs or toppings as garnish' ],
		[ 'Enjoy', 'Serve to guests or family and enjoy the meal' ],
	];

	// Ingredient groups
	$ingredient_groups = [
		[
			'group_name' => 'Base Ingredients',
			'items'      => [
				[ 'name' => 'Main Ingredient', 'amount' => '500g', 'unit' => 'grams' ],
				[ 'name' => 'Oil or Butter', 'amount' => '2', 'unit' => 'tablespoons' ],
				[ 'name' => 'Salt and Pepper', 'amount' => 'to taste', 'unit' => '' ],
			],
		],
		[
			'group_name' => 'Vegetables',
			'items'      => [
				[ 'name' => 'Primary Vegetable', 'amount' => '2', 'unit' => 'medium' ],
				[ 'name' => 'Secondary Vegetable', 'amount' => '1', 'unit' => 'large' ],
				[ 'name' => 'Garlic', 'amount' => '2', 'unit' => 'cloves' ],
			],
		],
		[
			'group_name' => 'Seasonings',
			'items'      => [
				[ 'name' => 'Primary Seasoning', 'amount' => '1', 'unit' => 'teaspoon' ],
				[ 'name' => 'Secondary Seasoning', 'amount' => '1/2', 'unit' => 'teaspoon' ],
				[ 'name' => 'Fresh Herbs', 'amount' => '2', 'unit' => 'tablespoons' ],
			],
		],
	];

	echo "Starting to generate 100 test recipe products...\n";
	$created_count = 0;
	$error_count = 0;

	for ( $i = 1; $i <= 100; $i++ ) {
		try {
			// Select a random template
			$template = $recipe_templates[ array_rand( $recipe_templates ) ];

			// Generate product title
			$variant = ( $i % 5 ) + 1;
			$subtitle = $template['subtitles'][ ( $i - 1 ) % count( $template['subtitles'] ) ];
			$product_title = $template['title_base'] . ' #' . $i . ' - ' . $subtitle;

			// Create WooCommerce product
			$product = new \WC_Product_Simple();
			$product->set_name( $product_title );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_price( rand( 8, 25 ) );
			$product->set_regular_price( rand( 8, 25 ) );
			$product->set_manage_stock( false );
			$product->set_stock_status( 'instock' );

			// Add description
			$product->set_description( 'A delicious ' . strtolower( $template['title_base'] ) . ' recipe perfect for any occasion. This recipe includes detailed instructions and nutritional information.' );
			$product->set_short_description( 'Enjoy this tasty ' . $subtitle . ' at home!' );

			// Save the product
			$product_id = $product->save();

			if ( ! $product_id || is_wp_error( $product_id ) ) {
				echo "ERROR: Failed to create product #$i\n";
				$error_count++;
				continue;
			}

			// Add ChefPress recipe meta data
			$cooking_time = $template['cooking_times'][ array_rand( $template['cooking_times'] ) ];
			$calories = rand( $template['calories_range'][0], $template['calories_range'][1] );
			$protein = rand( (int) $template['protein_range'][0], (int) $template['protein_range'][1] ) + ( rand( 0, 10 ) / 10 );
			$carbs = rand( (int) $template['carbs_range'][0], (int) $template['carbs_range'][1] ) + ( rand( 0, 10 ) / 10 );
			$fat = rand( (int) $template['fat_range'][0], (int) $template['fat_range'][1] ) + ( rand( 0, 10 ) / 10 );

			update_post_meta( $product_id, '_chefpress_subtitle', sanitize_text_field( $subtitle ) );
			update_post_meta( $product_id, '_chefpress_cooking_time', sanitize_text_field( $cooking_time ) );
			update_post_meta( $product_id, '_chefpress_reviews_count', rand( 0, 250 ) );
			update_post_meta( $product_id, '_chefpress_rating', rand( 30, 50 ) / 10 ); // 3.0 - 5.0
			update_post_meta( $product_id, '_chefpress_calories', $calories );
			update_post_meta( $product_id, '_chefpress_protein', $protein );
			update_post_meta( $product_id, '_chefpress_carbs', $carbs );
			update_post_meta( $product_id, '_chefpress_fat', $fat );

			// Add before-you-start content
			update_post_meta( $product_id, '_chefpress_before_start', 'Make sure you have all ingredients ready before starting. This recipe takes approximately ' . $cooking_time . ' to prepare and cook.' );

			// Add cooking steps
			$steps = [];
			$step_count = rand( 5, 10 );
			for ( $s = 0; $s < $step_count; $s++ ) {
				$step_template = $step_templates[ $s % count( $step_templates ) ];
				$steps[] = [
					'step_number'      => $s + 1,
					'step_title'       => $step_template[0],
					'step_description' => '<p>' . $step_template[1] . ' for this recipe.</p>',
				];
			}
			update_post_meta( $product_id, '_chefpress_steps', $steps );

			// Add ingredient groups
			update_post_meta( $product_id, '_chefpress_groups', $ingredient_groups );

			// Add tags
			$selected_tags = array_rand( $recipe_tags, rand( 2, 4 ) );
			$selected_tags = is_array( $selected_tags ) ? $selected_tags : [ $selected_tags ];
			$tags_to_save = [];
			foreach ( $selected_tags as $tag_idx ) {
				$tags_to_save[] = $recipe_tags[ $tag_idx ];
			}
			update_post_meta( $product_id, '_chefpress_tags', $tags_to_save );

			// Add week assignment (required for filtering)
			$week = $weeks[ array_rand( $weeks ) ];
			wp_set_object_terms( $product_id, $week, 'chefpress_week', false );

			// Add category
			$category = $categories[ array_rand( $categories ) ];
			wp_set_object_terms( $product_id, $category, 'product_cat', false );

			// Add recipe tags taxonomy
			foreach ( $tags_to_save as $tag ) {
				wp_set_object_terms( $product_id, $tag, 'chefpress_recipe_tag', true );
			}

			// Add nutrition table meta
			update_post_meta( $product_id, '_chefpress_per_serving_label', 'Per serving' );
			update_post_meta( $product_id, '_chefpress_nutrition_note', 'Nutritional values are approximate and based on standard ingredient measurements.' );

			// Optionally add allergen information (50% of products)
			if ( rand( 0, 1 ) === 1 ) {
				$selected_allergens = array_rand( $allergens, rand( 1, 3 ) );
				$selected_allergens = is_array( $selected_allergens ) ? $selected_allergens : [ $selected_allergens ];
				$allergens_to_assign = [];
				foreach ( $selected_allergens as $allergen_idx ) {
					$allergens_to_assign[] = $allergens[ $allergen_idx ];
				}

				update_post_meta( $product_id, '_chefpress_main_allergen', $allergens_to_assign[0] ?? '' );
				update_post_meta( $product_id, '_chefpress_allergen_description', 'This recipe contains one or more common allergens. Please check ingredients carefully.' );
				update_post_meta( $product_id, '_chefpress_allergen_list', $allergens_to_assign );

				// Add allergen taxonomy
				foreach ( $allergens_to_assign as $allergen ) {
					wp_set_object_terms( $product_id, $allergen, 'chefpress_allergen_tag', true );
				}
			}

			$created_count++;
			if ( $created_count % 10 === 0 ) {
				echo "Created $created_count products...\n";
			}
		} catch ( Exception $e ) {
			echo "ERROR on product #$i: " . $e->getMessage() . "\n";
			$error_count++;
		}
	}

	echo "\n";
	echo "✅ DONE! Created: $created_count products\n";
	if ( $error_count > 0 ) {
		echo "❌ Errors: $error_count products\n";
	}
	echo "\nProducts are assigned to:\n";
	echo "  - Weeks: week-1, week-2, week-3, week-4\n";
	echo "  - Categories: appetizers, mains, sides, desserts, breakfast\n";
	echo "  - Tags: quick, healthy, vegan, vegetarian, family-friendly, low-carb, protein-rich, comfort-food, italian, asian\n";
	echo "  - 50% have allergen data\n";
	echo "\nYou can now test your APIs!\n";
}

add_action('plugins_loaded', function () {

    if ( ! class_exists('WooCommerce') ) {
        echo "ERROR: WooCommerce is not active. Please activate WooCommerce to use the test recipe generator.\n";
        die;
        return;
    }

    // Trigger manually via URL
    if ( isset($_GET['generate_recipes']) ) {

        generate_test_recipes();

        exit('Recipes generated successfully.');
        die;
    }

}, 20); 
?>
