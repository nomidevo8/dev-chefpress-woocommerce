# Dev ChefPress — Dual-Mode Filtering System

## Overview

A flexible, scalable filtering system for recipes that supports three modes:
- **Frontend (Fast Mode)**: Load all recipes once, filter instantly in JS
- **Backend (Scalable Mode)**: AJAX calls on every filter change
- **Auto (Recommended)**: Automatically choose based on recipe count (threshold: 50)

---

## Implementation Summary

### ✅ Step 1: Settings Infrastructure
**File**: `app/Services/PluginSettings.php`

Added:
- `filter_mode` setting (default: `auto`)
- `get_filter_mode()` getter
- `sanitize_filter_mode()` sanitizer
- Settings saved via `save_from_post()`

### ✅ Step 2: Admin UI
**File**: `app/Admin/views/settings-page.php`

Added:
- Filter Mode dropdown in settings tab
- Options: Auto (Recommended), Frontend (Fast Mode), Backend (Scalable Mode)
- Help text explaining each mode

### ✅ Step 3: Backend Filtering Service
**File**: `app/Services/FilterService.php`

Provides:
- `filter_recipes()` - Main query builder
  - Accepts unified filter structure
  - Builds complex tax_query and meta_query
  - Returns both HTML (for backend mode) and JSON (for frontend mode)
- `get_recipes_data()` - Returns structured recipe data
- `render_recipes()` - HTML rendering
- `handle_ajax_filter()` - AJAX handler

### ✅ Step 4: AJAX Registration
**File**: `app/Frontend/Frontend.php`

Added:
- AJAX action hooks for `wp_ajax_chefpress_filter_recipes`
- `wp_localize_script()` passes:
  - `filter_mode`: Current setting
  - `nonce`: Security nonce
  - `ajax_url`: Admin-ajax endpoint

### ✅ Step 5: Frontend JavaScript
**File**: `resources/js/frontend.js`

Implements:

#### Unified Filter Structure
```javascript
var FilterState = {
    week:       '',
    category:   '',
    tags:       [],
    allergens:  [],
    sort:       '',
    page:       1
};
```

#### Frontend Mode (FrontendMode)
- Load all recipes for a week in one AJAX call
- Store in-memory as JavaScript array
- Filter, sort, paginate in JS
- No additional API calls
- Instant feedback

#### Backend Mode (BackendMode)
- Debounce filter changes (300ms)
- AJAX call on every significant filter change
- Load paginated results
- Show loading state
- Update pagination UI

#### Auto Mode (AutoMode)
- Checks recipe count from initial load
- If ≤50: Use FrontendMode
- If >50: Use BackendMode
- Seamlessly switches based on data size

---

## Unified Filter Object

Used consistently across both modes:

```javascript
{
    week:       'week-1',           // Required: taxonomy slug
    category:   'breakfast',        // Optional: product category slug
    tags:       ['low-carb'],       // Optional: recipe tag slugs
    allergens:  ['peanuts'],        // Optional: allergen tag slugs
    sort:       'calories:ASC',     // Optional: 'field:DIRECTION'
    page:       1                   // Pagination
}
```

### Sorting Options
- `calories:ASC|DESC`
- `carbs:ASC|DESC`
- `protein:ASC|DESC`
- `fat:ASC|DESC`
- `time:ASC|DESC` (cooking time)

---

## AJAX API Endpoint

**Action**: `wp_ajax_chefpress_filter_recipes`

### Request
```javascript
{
    action: 'chefpress_filter_recipes',
    nonce: 'xxx',
    filters: {
        week: 'week-1',
        category: '',
        tags: [],
        allergens: [],
        sort: '',
        page: 1
    }
}
```

### Response (Backend Mode)
```json
{
    "html": "<div class=\"cp-recipes-grid\">...</div>",
    "total_pages": 5,
    "current_page": 1,
    "total_count": 42,
    "recipes_count": 42,
    "recipes": []
}
```

### Response (Frontend Mode)
```json
{
    "recipes": [
        {
            "id": 123,
            "title": "Pasta Carbonara",
            "url": "http://site.com/recipe/pasta",
            "image": "http://cdn/image.jpg",
            "subtitle": "Classic Italian dish",
            "cookingTime": "20 mins",
            "calories": 450,
            "protein": 15.2,
            "carbs": 52.1,
            "fat": 18.5,
            "categories": ["lunch", "italian"],
            "tags": ["quick", "easy"],
            "allergens": ["wheat", "eggs"]
        }
    ],
    "recipes_count": 128
}
```

---

## Query Building Logic

### Tax Query
```php
'tax_query' => [
    'relation' => 'AND',
    [
        'taxonomy' => 'chefpress_week',
        'field' => 'slug',
        'terms' => 'week-1',
        'operator' => 'IN'
    ],
    [
        'taxonomy' => 'product_cat',
        'terms' => ['breakfast'],
        'operator' => 'IN'
    ],
    [
        'taxonomy' => 'chefpress_recipe_tag',
        'terms' => ['low-carb'],
        'operator' => 'IN'
    ],
    [
        'taxonomy' => 'chefpress_allergen_tag',
        'terms' => ['peanuts'],
        'operator' => 'NOT IN'  // Exclude allergens
    ]
]
```

### Meta Query (if sorting)
```php
'meta_query' => [
    [
        'key' => '_chefpress_calories',
        'compare' => 'EXISTS'
    ]
],
'orderby' => 'meta_value_num',
'order' => 'ASC'
```

---

## Performance Rules

✅ **Best Practices**
1. Always filter by `chefpress_week` first (required)
2. Use `tax_query` instead of `meta_query` when possible
3. Only load meta when sorting requires it
4. Debounce filter changes (300ms)
5. Cache-friendly: Same queries return same results

⚠️ **Constraints**
- Max 9 items per page (configurable in FilterService)
- Meta sorting limited to numeric fields
- Frontend mode limited to ~50 recipes for performance
- Allergens use `NOT IN` (exclude) logic

---

## Testing Guide

### Test 1: Frontend Mode
1. Go to ChefPress Settings
2. Set Filter Mode to "Frontend (Fast Mode)"
3. Save
4. Create a recipe page with Week 1 recipes
5. Select a week → Observe instant filter (no API calls)
6. Change category/tags → Instant results
7. Check browser DevTools Network → No requests

### Test 2: Backend Mode
1. Set Filter Mode to "Backend (Scalable Mode)"
2. Create page with recipes
3. Select a week → Observe AJAX call
4. Change filter → 300ms debounce → AJAX call
5. Check DevTools Network → See POST to admin-ajax.php

### Test 3: Auto Mode
1. Set Filter Mode to "Auto (Recommended)"
2. With ≤50 recipes:
   - Should act like Frontend mode
   - Instant results, no API calls
3. With >50 recipes:
   - Should act like Backend mode
   - AJAX on filter change

### Test 4: API Testing

**cURL Example:**
```bash
curl -X POST http://localhost/wp-admin/admin-ajax.php \
  -d "action=chefpress_filter_recipes" \
  -d "nonce=YOUR_NONCE" \
  -d "filters[week]=week-1" \
  -d "filters[page]=1"
```

**Expected Response:**
```json
{
    "html": "...",
    "total_pages": 3,
    "current_page": 1,
    "total_count": 24,
    "recipes_count": 24,
    "recipes": []
}
```

---

## Frontend Integration

### HTML Structure (Required)
```html
<div class="cp-recipes-container"></div>
<div class="cp-pagination-container"></div>

<!-- Filters (optional) -->
<select class="cp-filter-week" data-filter-type="week">
    <option value="week-1">Week 1</option>
</select>

<select class="cp-filter-sort" data-filter-type="sort">
    <option value="">Default</option>
    <option value="calories:ASC">Calories (Low)</option>
    <option value="calories:DESC">Calories (High)</option>
</select>
```

### JavaScript Initialization
Automatic on `$(document).ready()` via `ChefPressConfig.filter_mode`

---

## Settings saved to

**Option Key**: `chefpress_plugin_settings`

```php
[
    'filter_mode' => 'auto',
    'nutrition_fields' => [...],
    'preset_ingredients' => [...],
    'preset_allergens' => [...],
    'preset_recipe_labels' => [...],
    'theme_colors' => [...]
]
```

---

## Future Enhancements

1. **Analytics**: Track popular filters
2. **Caching**: Cache filter results per week
3. **Advanced Search**: Full-text search in recipe titles/descriptions
4. **Favorites**: Save favorite filter combinations
5. **Pagination**: Client-side infinite scroll for frontend mode
6. **Sorting UI**: Render sorting options dynamically from settings
7. **Filter Persistence**: Save filter state in URL hash

---

## Troubleshooting

### Filters Not Working
- ✓ Check `chefpress_week` taxonomy exists
- ✓ Recipes assigned to weeks
- ✓ Verify AJAX nonce is correct

### Slow Performance
- ✓ Check recipe count (>50 should use backend)
- ✓ Verify meta_query only used when necessary
- ✓ Check for missing indexes on `_chefpress_*` meta

### AJAX Returns Empty
- ✓ Verify `week` filter is provided
- ✓ Check product post type is `product`
- ✓ Verify recipes exist for selected week

---

**Implementation Complete ✅**
