# Test Recipe Generator - Usage Guide

## Overview
The `generate-test-recipes.php` script creates **100 realistic recipe products** with diverse data for testing your APIs and filtering system.

## Installation & Execution

### Option 1: Using WP-CLI (Recommended)
```bash
cd /d/laragon/www/aos-fresh

# Run the script using WP-CLI eval-file
wp eval-file wp-content/plugins/dev-chefpress-woocommerce/generate-test-recipes.php
```

### Option 2: Include in a Plugin File
Add to your plugin's main file (`dev-chefpress-woocommerce.php`):
```php
// Add this inside your plugin initialization
if ( current_user_can( 'manage_options' ) && isset( $_GET['generate_test_recipes'] ) ) {
    require DEVCHEFPRESS_PATH . 'generate-test-recipes.php';
}
```

Then visit: `https://aos-fresh.local/?generate_test_recipes=1` (admin only)

### Option 3: Direct PHP Execution
```bash
php -r "require 'wp-load.php'; require_once 'wp-content/plugins/dev-chefpress-woocommerce/generate-test-recipes.php';"
```

## What Gets Created

Each of the 100 products includes:

### Product Data
- Unique titles (Recipe Type + Number + Variant)
- Regular pricing ($8-$25)
- Description and short descriptions
- Published status, visible on site

### ChefPress Recipe Meta
- ✅ Subtitle (e.g., "Creamy Alfredo", "Grilled Herb")
- ✅ Cooking time (5-45 mins)
- ✅ Calories (150-650 depending on recipe type)
- ✅ Nutritional values (protein, carbs, fat)
- ✅ Reviews count (0-250)
- ✅ Rating (3.0-5.0 stars)
- ✅ Steps (5-10 cooking steps with descriptions)
- ✅ Ingredient groups (prepared ingredients, vegetables, seasonings)
- ✅ Before-you-start content

### Taxonomies (for filtering)
- **chefpress_week**: week-1, week-2, week-3, week-4 (required for FilterService)
- **product_cat**: appetizers, mains, sides, desserts, breakfast
- **chefpress_recipe_tag**: quick, healthy, vegan, vegetarian, family-friendly, low-carb, protein-rich, comfort-food, italian, asian
- **chefpress_allergen_tag**: peanuts, tree-nuts, shellfish, fish, soy, gluten, dairy, eggs (50% of products)

### Recipe Templates
The generator includes **10 recipe types** with realistic variations:
1. **Pasta** - Italian pasta dishes
2. **Chicken** - Various chicken preparations
3. **Salad** - Fresh vegetable salads
4. **Fish** - Seafood preparations
5. **Soup** - Hot soups
6. **Stir-Fry** - Asian stir-fry recipes
7. **Burger** - Different burger styles
8. **Bowl** - Modern bowl recipes
9. **Pizza** - Italian pizzas
10. **Smoothie** - Breakfast smoothies

## Testing Your FilterService API

### Test Case 1: Filter by Week
```javascript
// Fetch recipes for week-1
const response = await fetch('/wp-admin/admin-ajax.php', {
  method: 'POST',
  body: new FormData({
    action: 'filter_recipes',
    week: '1',
    page: 1,
    filter_mode: 'frontend'
  })
});
const data = await response.json();
console.log(data); // Should return ~25 recipes
```

### Test Case 2: Filter by Category
```javascript
// Get only "mains" category items from week-1
const response = await fetch('/wp-admin/admin-ajax.php', {
  method: 'POST',
  body: new FormData({
    action: 'filter_recipes',
    week: '1',
    category: 'mains',
    page: 1
  })
});
```

### Test Case 3: Filter by Tags
```javascript
// Get "quick" and "healthy" recipes
const response = await fetch('/wp-admin/admin-ajax.php', {
  method: 'POST',
  body: new FormData({
    action: 'filter_recipes',
    week: '1',
    tags: ['quick', 'healthy'],
    page: 1
  })
});
```

### Test Case 4: Exclude Allergens
```javascript
// Get recipes WITHOUT peanuts and gluten
const response = await fetch('/wp-admin/admin-ajax.php', {
  method: 'POST',
  body: new FormData({
    action: 'filter_recipes',
    week: '1',
    allergens: ['peanuts', 'gluten'],
    page: 1
  })
});
```

### Test Case 5: Sort by Nutrition
```javascript
// Sort by calories (descending)
const response = await fetch('/wp-admin/admin-ajax.php', {
  method: 'POST',
  body: new FormData({
    action: 'filter_recipes',
    week: '1',
    sort: 'calories:DESC',
    page: 1
  })
});

// Available sort options: calories, protein, carbs, fat, time (plus :ASC/:DESC)
```

### Test Case 6: Backend Mode (Large Dataset)
```javascript
// Returns only HTML, no recipe data (for performance with 100 items)
const response = await fetch('/wp-admin/admin-ajax.php', {
  method: 'POST',
  body: new FormData({
    action: 'filter_recipes',
    week: '1',
    filter_mode: 'backend'
  })
});

// response.recipes will be empty array
// response.html will contain rendered HTML
```

## Database Verification

Check the generated products in WordPress:

```php
// In WP-Admin > Products, you should see 100 new products
// Named: "Pasta #1 - Creamy Alfredo", "Chicken #2 - BBQ Glazed", etc.

// Or via WP-CLI:
wp post list --post_type=product --posts_per_page=100
```

## Cleanup (Delete Test Products)

When done testing, remove all generated products:

```bash
# Option 1: WP-CLI
wp post delete $(wp post list --post_type=product --format=ids) --force

# Option 2: MySQL directly (warning: careful!)
# DELETE FROM wp_posts WHERE post_type='product' AND post_title LIKE '% #%';
```

## API Response Structure

When FilterService returns data, you'll get:

```json
{
  "html": "<div class='cp-recipes-grid'>...</div>",
  "total_pages": 3,
  "current_page": 1,
  "total_count": 25,
  "recipes_count": 25,
  "recipes": [
    {
      "id": 123,
      "title": "Pasta #1 - Creamy Alfredo",
      "url": "https://aos-fresh.local/product/pasta-1/",
      "image": "https://...",
      "subtitle": "Creamy Alfredo",
      "cookingTime": "15 mins",
      "calories": 450,
      "protein": 15.5,
      "carbs": 52.3,
      "fat": 12.1,
      "categories": ["mains"],
      "tags": ["quick", "italian"],
      "allergens": ["gluten", "dairy"]
    }
    // ... more recipes
  ]
}
```

## Expected Filtering Results

With 100 products distributed across:
- **4 weeks**: ~25 products per week
- **5 categories**: ~20 products per category
- **10 tags**: ~40 products with multiple tags
- **50+ allergens**: Half the products have allergen info

This provides realistic API test scenarios! ✅
