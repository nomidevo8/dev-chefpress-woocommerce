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
	$weeks = [ 'week-1', 'week-2', 'week-3', 'week-4', 'week-5', 'week-6' ];
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
            $product = new \WC_Product_Recipe_product();
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
			$calories = (string) rand( $template['calories_range'][0], $template['calories_range'][1] );
			$protein = number_format( rand( (int) $template['protein_range'][0], (int) $template['protein_range'][1] ) + ( rand( 0, 10 ) / 10 ), 1 );
			$carbs = number_format( rand( (int) $template['carbs_range'][0], (int) $template['carbs_range'][1] ) + ( rand( 0, 10 ) / 10 ), 1 );
			$fat = number_format( rand( (int) $template['fat_range'][0], (int) $template['fat_range'][1] ) + ( rand( 0, 10 ) / 10 ), 1 );

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

			// Add week assignment (required for filtering) - use term ID for reliability
			$week_slug = $weeks[ array_rand( $weeks ) ];
			wp_set_object_terms( $product_id, $week_slug, 'chefpress_week', false );

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

			// Add sample nutrition table values as strings
			update_post_meta( $product_id, '_chefpress_nutr_energy', (string) rand( 200, 800 ) );
			update_post_meta( $product_id, '_chefpress_nutr_fat', number_format( rand( 5, 50 ) + ( rand( 0, 10 ) / 10 ), 1 ) );
			update_post_meta( $product_id, '_chefpress_nutr_saturates', number_format( rand( 1, 20 ) + ( rand( 0, 10 ) / 10 ), 1 ) );
			update_post_meta( $product_id, '_chefpress_nutr_carbohydrate', number_format( rand( 10, 100 ) + ( rand( 0, 10 ) / 10 ), 1 ) );
			update_post_meta( $product_id, '_chefpress_nutr_sugars', number_format( rand( 5, 50 ) + ( rand( 0, 10 ) / 10 ), 1 ) );
			update_post_meta( $product_id, '_chefpress_nutr_fibre', number_format( rand( 1, 10 ) + ( rand( 0, 10 ) / 10 ), 1 ) );
			update_post_meta( $product_id, '_chefpress_nutr_protein', number_format( rand( 5, 50 ) + ( rand( 0, 10 ) / 10 ), 1 ) );
			update_post_meta( $product_id, '_chefpress_nutr_salt', number_format( rand( 0, 5 ) + ( rand( 0, 10 ) / 10 ), 1 ) );

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


// Add a submenu page under WooCommerce
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=chefpress',
        'Generate Test Recipes',
        'Generate Test Recipes',
        'manage_options',
        'generate-test-recipes',
        'render_generate_test_recipes_page'
    );
});

// Render the admin page
function render_generate_test_recipes_page() {
    // Check if button was clicked
    if ( isset($_POST['generate_test_recipes']) && check_admin_referer('generate_test_recipes_nonce') ) {
        echo '<div class="notice notice-success"><p>';
        generate_test_recipes(); // Call your existing function
        echo '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Generate Test Recipes</h1>
        <p>Click the button below to generate 100 test recipe products for testing APIs.</p>
        <form method="post">
            <?php wp_nonce_field('generate_test_recipes_nonce'); ?>
            <input type="submit" name="generate_test_recipes" class="button button-primary" value="Generate Recipes">
        </form>
    </div>
    <?php
}


// Add a submenu page under "WooCommerce"
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=chefpress',
        'Assign Weekly Terms',        // Page title
        'Assign Weekly Terms',        // Menu title
        'manage_options',             // Capability
        'assign-weekly-terms',        // Menu slug
        'render_assign_weekly_terms_page' // Callback function
    );
});

// Render the admin page
function render_assign_weekly_terms_page() {
    // Check if button was clicked
    if ( isset($_POST['assign_week_terms']) && check_admin_referer('assign_week_terms_nonce') ) {
        echo '<div class="notice notice-success"><p>';
        assign_random_week_terms_to_products();
        echo '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Assign Random Weekly Terms to Products</h1>
        <p>Click the button below to assign random <strong>chefpress_week</strong> terms to all products.</p>
        <form method="post">
            <?php wp_nonce_field('assign_week_terms_nonce'); ?>
            <input type="submit" name="assign_week_terms" class="button button-primary" value="Assign Weekly Terms">
        </form>
    </div>
    <?php
}

// Function to assign random week terms
function assign_random_week_terms_to_products() {
    $product_ids = get_posts([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ]);

    if ( empty($product_ids) ) {
        echo "No products found.";
        return;
    }

    $week_terms = get_terms([
        'taxonomy'   => 'chefpress_week',
        'hide_empty' => false,
    ]);

    if ( empty($week_terms) || is_wp_error($week_terms) ) {
        echo "No chefpress_week terms found.";
        return;
    }

    foreach ( $product_ids as $product_id ) {
        $random_term = $week_terms[ array_rand($week_terms) ];
        wp_set_object_terms( $product_id, intval($random_term->term_id), 'chefpress_week', false );
        echo "Assigned '{$random_term->name}' to product ID {$product_id}<br>";
    }

    echo "✅ Done assigning random weekly terms to all products.";
}



// Add a submenu page under WooCommerce
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=chefpress',
        'Assign Categories to Recipes',
        'Assign Recipe Categories',
        'manage_options',
        'assign-recipe-categories',
        'render_assign_recipe_categories_page'
    );
});

// Render the admin page
function render_assign_recipe_categories_page() {
    // Check if button was clicked
    if ( isset($_POST['assign_recipe_categories']) && check_admin_referer('assign_recipe_categories_nonce') ) {
        echo '<div class="notice notice-success"><p>';
        assign_categories_to_recipes();
        echo '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Assign Categories to Recipe Products</h1>
        <p>Click the button below to create sample categories and assign them randomly to all recipe products.</p>
        <form method="post">
            <?php wp_nonce_field('assign_recipe_categories_nonce'); ?>
            <input type="submit" name="assign_recipe_categories" class="button button-primary" value="Create & Assign Categories">
        </form>
    </div>
    <?php
}

// Function to create categories and assign them
function assign_categories_to_recipes() {
    // Sample categories to create
    $categories = ['Appetizers', 'Mains', 'Sides', 'Desserts', 'Breakfast', 'Lunch', 'Dinner'];

    foreach ( $categories as $cat_name ) {
        // Check if category exists
        $term = term_exists( $cat_name, 'product_cat' );
        if ( $term === 0 || $term === null ) {
            // Create category
            $new_term = wp_insert_term( $cat_name, 'product_cat' );
            if ( is_wp_error( $new_term ) ) {
                echo "Failed to create category '{$cat_name}': {$new_term->get_error_message()}<br>";
                continue;
            }
            $term_id = $new_term['term_id'];
            echo "Created category '{$cat_name}'<br>";
        } else {
            $term_id = $term['term_id'];
            echo "Category '{$cat_name}' already exists<br>";
        }
    }

    // Get all products
    $product_ids = get_posts([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ]);

    if ( empty($product_ids) ) {
        echo "No products found.<br>";
        return;
    }

    // Assign a random category to each product
    foreach ( $product_ids as $product_id ) {
        $random_cat = $categories[ array_rand($categories) ];
        wp_set_object_terms( $product_id, $random_cat, 'product_cat', false );
        echo "Assigned category '{$random_cat}' to product ID {$product_id}<br>";
    }

    echo "✅ Done creating categories and assigning to products.";
}

// Add a submenu page under WooCommerce for assigning images
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=chefpress',
        'Assign Images to Recipes',
        'Assign Recipe Images',
        'manage_options',
        'assign-recipe-images',
        'render_assign_recipe_images_page'
    );
});

// Render the admin page for assigning images
function render_assign_recipe_images_page() {
    // Check if button was clicked
    if ( isset($_POST['assign_recipe_images']) && check_admin_referer('assign_recipe_images_nonce') ) {
        echo '<div class="notice notice-success"><p>';
        assign_random_images_to_recipes();
        echo '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Assign Random Images to Recipe Products</h1>
        <p>Click the button below to assign random images from your WordPress media library to all recipe products as featured images.</p>
        <form method="post">
            <?php wp_nonce_field('assign_recipe_images_nonce'); ?>
            <input type="submit" name="assign_recipe_images" class="button button-primary" value="Assign Images to Products">
        </form>
    </div>
    <?php
}

// Function to get all images from WordPress media library
function get_all_media_images() {
    $images = get_posts([
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'post_status'    => 'inherit',
        'fields'         => 'ids',
    ]);

    return $images;
}

// Function to assign random images to products
function assign_random_images_to_recipes() {
    // Get all published products
    $product_ids = get_posts([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ]);

    if ( empty($product_ids) ) {
        echo "No products found.<br>";
        return;
    }

    // Get all media images
    $media_images = get_all_media_images();

    if ( empty($media_images) ) {
        echo "No images found in media library. Please upload some images first.<br>";
        return;
    }

    echo "Found " . count($media_images) . " images in media library.<br>";
    echo "Assigning images to " . count($product_ids) . " products...<br><br>";

    $assigned_count = 0;
    $failed_count = 0;

    foreach ( $product_ids as $product_id ) {
        try {
            // Get a random image from media library
            $random_image_id = $media_images[ array_rand($media_images) ];

            // Set the image as featured image (thumbnail) for the product
            set_post_thumbnail( $product_id, $random_image_id );

            $image_url = wp_get_attachment_url( $random_image_id );
            $image_name = basename( $image_url );

            echo "✅ Product ID {$product_id}: Assigned image '{$image_name}'<br>";
            $assigned_count++;

        } catch ( Exception $e ) {
            echo "❌ Product ID {$product_id}: Failed to assign image<br>";
            $failed_count++;
        }
    }

    echo "<br>";
    echo "✅ Successfully assigned images to {$assigned_count} products<br>";
    if ( $failed_count > 0 ) {
        echo "❌ Failed to assign images to {$failed_count} products<br>";
    }
}
?>
