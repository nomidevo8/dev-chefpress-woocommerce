# Dev ChefPress — API Testing Guide

Complete instructions for testing the filtering system AJAX endpoint with curl, Postman, or JavaScript.

---

## Quick Reference

**Endpoint:** `https://your-site.local/wp-admin/admin-ajax.php`  
**Action:** `chefpress_filter_recipes`  
**Method:** POST  
**Nonce Parameter:** `chefpress_nonce` (required for security)  
**Default Filter Mode:** `auto` (switch weights between Frontend and Backend modes at 50 recipes)

---

## Getting a Valid Nonce

The nonce is generated server-side and passed to JavaScript via `wp_localize_script`. To test via curl/Postman:

### Option 1: Extract from Frontend (Easiest)

1. Visit any page with Chefpress recipes
2. Open browser Console (F12 → Console)
3. Run: `console.log(ChefPressConfig.nonce)`
4. Copy the nonce value

### Option 2: Generate via PHP (For Automated Testing)

If you have WP-CLI access:
```bash
wp eval 'echo wp_create_nonce("chefpress_filter");'
```

---

## Test 1: Basic Weekly Menu Filter

**Scenario:** Filter recipes by week only

### cURL Command
```bash
curl -X POST "https://your-site.local/wp-admin/admin-ajax.php" \
  -d "action=chefpress_filter_recipes" \
  -d "nonce=PASTE_NONCE_HERE" \
  -d "week=1" \
  -d "filter_mode=auto"
```

### Postman Setup
1. **URL:** `https://your-site.local/wp-admin/admin-ajax.php`
2. **Method:** POST
3. **Body (form-data):**
   - `action` = `chefpress_filter_recipes`
   - `nonce` = `PASTE_NONCE_HERE`
   - `week` = `1`
   - `filter_mode` = `auto`

### Expected Response (≤50 recipes - Frontend Mode)
```json
{
  "success": true,
  "mode": "frontend",
  "recipes": [
    {
      "id": 1234,
      "title": "Grilled Salmon",
      "excerpt": "Quick and healthy salmon recipe",
      "image": "https://...",
      "categories": ["Protein", "Healthy"],
      "tags": ["keto", "paleo"],
      "allergens": ["fish"],
      "calories": 450,
      "carbs": 5,
      "protein": 35,
      "fat": 28,
      "cooking_time": "15 mins"
    },
    ...
  ],
  "recipes_count": 12,
  "total_pages": 2
}
```

---

## Test 2: Multi-Tag Filter

**Scenario:** Filter by week + multiple tags (AND relationship)

### cURL Command
```bash
curl -X POST "https://your-site.local/wp-admin/admin-ajax.php" \
  -d "action=chefpress_filter_recipes" \
  -d "nonce=PASTE_NONCE_HERE" \
  -d "week=2" \
  -d "tags[]=keto" \
  -d "tags[]=quick" \
  -d "filter_mode=backend"
```

### Postman Setup
1. **Body (form-data):**
   - `action` = `chefpress_filter_recipes`
   - `nonce` = `PASTE_NONCE_HERE`
   - `week` = `2`
   - `tags[]` = `keto`
   - `tags[]` = `quick`
   - `filter_mode` = `backend`

### Expected Response (Backend Mode - HTML)
```json
{
  "success": true,
  "mode": "backend",
  "html": "<div class='cp-recipes-grid'>..recipe cards...</div>",
  "pagination_html": "<div class='cp-pagination'>..pagination links..</div>",
  "recipes_count": 3,
  "total_pages": 1
}
```

---

## Test 3: Allergen Exclusion

**Scenario:** Filter by week + exclude allergens

### cURL Command
```bash
curl -X POST "https://your-site.local/wp-admin/admin-ajax.php" \
  -d "action=chefpress_filter_recipes" \
  -d "nonce=PASTE_NONCE_HERE" \
  -d "week=1" \
  -d "allergens[]=peanuts" \
  -d "allergens[]=shellfish"
```

### Expected Behavior
- Returns only recipes from week 1 that do NOT have peanuts or shellfish allergens
- Prioritizes allergen safety (if no allergens = safe)

---

## Test 4: Sorting

**Scenario:** Sort by calories (ascending)

### cURL Command
```bash
curl -X POST "https://your-site.local/wp-admin/admin-ajax.php" \
  -d "action=chefpress_filter_recipes" \
  -d "nonce=PASTE_NONCE_HERE" \
  -d "week=1" \
  -d "sort=calories:ASC" \
  -d "filter_mode=frontend"
```

### Valid Sort Options
- `calories:ASC` or `calories:DESC`
- `carbs:ASC` or `carbs:DESC`
- `protein:ASC` or `protein:DESC`
- `fat:ASC` or `fat:DESC`
- `cooking_time:ASC` or `cooking_time:DESC`
- `date:DESC` (default) - most recent recipes first

### Expected Response
Recipes ordered from lowest to highest calories

---

## Test 5: Pagination

**Scenario:** Get page 2 of results

### cURL Command
```bash
curl -X POST "https://your-site.local/wp-admin/admin-ajax.php" \
  -d "action=chefpress_filter_recipes" \
  -d "nonce=PASTE_NONCE_HERE" \
  -d "week=1" \
  -d "page=2" \
  -d "filter_mode=backend"
```

### Expected Behavior
- Returns recipes 10-19 (assuming 9 per page)
- Pagination HTML shows page 2 as active
- Response includes `total_pages` count

---

## Test 6: Category Filter

**Scenario:** Filter by product category

### cURL Command
```bash
curl -X POST "https://your-site.local/wp-admin/admin-ajax.php" \
  -d "action=chefpress_filter_recipes" \
  -d "nonce=PASTE_NONCE_HERE" \
  -d "week=1" \
  -d "category=breakfast" \
  -d "filter_mode=auto"
```

### Expected Response
Only recipes from week 1 in the "breakfast" product category

---

## Error Cases

### Invalid Nonce
```json
{
  "success": false,
  "error": "Nonce verification failed. Please refresh the page."
}
```

### Missing Required Week
```json
{
  "success": false,
  "error": "Week is required. Please select a week."
}
```

### Invalid Week ID
```json
{
  "success": false,
  "error": "Invalid week selected."
}
```

### No Recipes Found
```json
{
  "success": true,
  "mode": "backend",
  "html": "<div class='cp-no-results'>No recipes found matching your filters.</div>",
  "recipes_count": 0,
  "total_pages": 0
}
```

---

## Filter Mode Behavior

### When to Use Each Mode

**Frontend Mode** (≤50 recipes)
- ✅ Instant filtering (no server roundtrip)
- ✅ Better UX for small recipe libraries
- ❌ Loads all recipes into memory
- Use when: Few recipes, need speed

**Backend Mode** (>50 recipes)
- ✅ Scales to thousands of recipes
- ✅ Pagination-friendly
- ✅ Lower initial page load
- ❌ AJAX delay on each filter
- Use when: Large recipe library, many filters

**Auto Mode** (Recommended)
- ✅ Automatically switches at 50 recipe threshold
- ✅ Transparent to user
- ✅ Best of both worlds
- Use when: Uncertain about recipe count

### Force a Specific Mode
```bash
# Force backend (always AJAX)
curl ... -d "filter_mode=backend"

# Force frontend (load all, filter with JS)
curl ... -d "filter_mode=frontend"

# Auto (switches at 50 recipes)
curl ... -d "filter_mode=auto"
```

---

## JavaScript Testing (Browser)

If you want to manually test via browser console:

### Load all recipes for week 1
```javascript
fetch('/wp-admin/admin-ajax.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({
    action: 'chefpress_filter_recipes',
    nonce: ChefPressConfig.nonce,
    week: 1,
    filter_mode: 'frontend'
  })
})
  .then(r => r.json())
  .then(data => console.log(data))
  .catch(err => console.error('Error:', err));
```

### Filter by multiple tags
```javascript
const formData = new URLSearchParams({
  action: 'chefpress_filter_recipes',
  nonce: ChefPressConfig.nonce,
  week: 1,
  'tags[]': 'keto',
  'tags[]': 'quick',
  filter_mode: 'frontend'
});

fetch('/wp-admin/admin-ajax.php', {
  method: 'POST',
  body: formData
})
  .then(r => r.json())
  .then(data => console.log('Found', data.recipes_count, 'recipes'));
```

---

## Troubleshooting

### 400 / 403 Errors

**Problem:** cURL returns 400 or 403  
**Solution:** 
1. Ensure nonce is valid (refresh page, get new nonce)
2. Ensure URL is correct (no extra slashes)
3. Test with `filter_mode=auto` (most permissive)

### Empty Results

**Problem:** Endpoint returns `recipes_count: 0`  
**Solution:**
1. Verify week exists (check admin: Settings → Filter Mode)
2. Verify recipes are assigned to that week
3. Check Database: `wp_term_relationships` for `chefpress_week` term_id

### Slow Response

**Problem:** Endpoint takes >1000ms  
**Solution:**
1. If backend mode: Check if too many filters, add indexes to `wp_postmeta` for calorie/protein fields
2. If frontend mode: Reduce number of recipes or use `filter_mode=backend`

### AJAX Returns HTML Instead of JSON

**Problem:** Response is HTML page instead of JSON  
**Solution:**
1. You're not hitting `/wp-admin/admin-ajax.php` - check URL
2. You're hitting wrong action - should be `action=chefpress_filter_recipes`
3. Run: `wp plugin list` to verify plugin is active

---

## Performance Benchmarks

Expected response times on typical WordPress hosting:

| Scenario | Frontend Mode | Backend Mode |
|----------|---------------|--------------|
| Week only | <50ms first call | 80-150ms |
| Week + 2 tags | <50ms (JS filter) | 100-200ms |
| Week + sort | <50ms (JS sort) | 120-250ms |
| Page 2 pagination | <50ms (JS) | 80-150ms |

*Times vary based on server resources and database optimization*

---

## Next Steps

After successful API testing:

1. **Template Integration:** Create page template with filter UI dropdowns
2. **User Testing:** Test with actual filters on your site
3. **Performance Tuning:** Monitor slow query logs, add indexes if needed
4. **UI Polish:** Adjust CSS if needed (use filters.css)

Need help? Check `FILTERING_SYSTEM.md` for complete architecture documentation.
