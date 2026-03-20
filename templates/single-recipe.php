<?php
// This is a placeholder template for Recipe Product single page.
echo 'hello';
// You can replace this with your custom markup or logic.


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salmon Recipe Page</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --cp_product_color-brand: #ff6b4a;
            --cp_product_color-brand-light: #fff0ed;
            --cp_product_color-text-main: #1a1a1a;
            --cp_product_color-text-muted: #666666;
            --cp_product_color-bg-light: #f9f9f9;
            --cp_product_color-border: #e5e5e5;
            --cp_product_color-white: #ffffff;
            --cp_product_font-sans: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .cp_product_reset * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .cp_product_body {
            font-family: var(--cp_product_font-sans);
            color: var(--cp_product_color-text-main);
            line-height: 1.5;
            background-color: var(--cp_product_color-white);
            padding: 2rem 1rem;
        }

        .cp_product_container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Header Section */
        .cp_product_header {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        @media (min-width: 768px) {
            .cp_product_header {
                grid-template-columns: 1fr 1fr;
            }
        }

        .cp_product_hero-image-container {
            position: relative;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .cp_product_hero-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }

        .cp_product_time-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background-color: var(--cp_product_color-brand);
            color: var(--cp_product_color-white);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .cp_product_title {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 0.5rem;
        }

        .cp_product_sub-title {
            font-size: 1.25rem;
            color: var(--cp_product_color-text-muted);
            font-style: italic;
            margin-bottom: 1rem;
        }

        .cp_product_description {
            color: var(--cp_product_color-text-muted);
            margin-bottom: 1.5rem;
        }

        .cp_product_reviews {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .cp_product_stars {
            display: flex;
            color: var(--cp_product_color-brand);
        }

        .cp_product_review-count {
            font-size: 0.875rem;
            color: var(--cp_product_color-text-muted);
        }

        .cp_product_macros {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .cp_product_macro-divider {
            color: var(--cp_product_color-border);
            margin: 0 0.5rem;
        }

        .cp_product_macro-item {
            display: flex;
            align-items: center;
        }

        .cp_product_icon-small {
            width: 14px;
            height: 14px;
        }

        .cp_product_icon-medium {
            width: 16px;
            height: 16px;
        }

        .cp_product_icon-large {
            width: 18px;
            height: 18px;
        }

        .cp_product_icon-fill {
            fill: currentColor;
        }

        .cp_product_icon-muted {
            color: #e5e5e5;
        }

        .cp_product_badges {
            display: flex;
            gap: 0.5rem;
        }

        .cp_product_badge {
            background-color: var(--cp_product_color-brand-light);
            color: var(--cp_product_color-brand);
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Main Grid */
        .cp_product_main-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 3rem;
        }

        @media (min-width: 1024px) {
            .cp_product_main-grid {
                grid-template-columns: 2fr 1fr;
            }
        }

        /* Instructions */
        .cp_product_section-title {
            font-size: 1.5rem;
            color: var(--cp_product_color-brand);
            margin-bottom: 1rem;
        }

        .cp_product_before-start {
            margin-bottom: 2.5rem;
        }

        .cp_product_before-start-text {
            font-size: 0.875rem;
            color: var(--cp_product_color-text-muted);
        }

        .cp_product_step {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        @media (min-width: 640px) {
            .cp_product_step {
                flex-direction: row;
            }
        }

        .cp_product_step-image {
            width: 100%;
            height: 120px;
            border-radius: 0.75rem;
            object-fit: cover;
            background-color: var(--cp_product_color-bg-light);
        }

        @media (min-width: 640px) {
            .cp_product_step-image {
                width: 180px;
                flex-shrink: 0;
            }
        }

        .cp_product_step-title {
            font-size: 1.25rem;
            color: var(--cp_product_color-brand);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
        }

        .cp_product_step-number {
            margin-right: 0.5rem;
        }

        .cp_product_step-list {
            list-style: none;
            padding-left: 1.25rem;
        }

        .cp_product_step-list-item {
            position: relative;
            font-size: 0.875rem;
            color: var(--cp_product_color-text-muted);
            margin-bottom: 0.5rem;
        }

        .cp_product_step-list-item::before {
            content: "•";
            position: absolute;
            left: -1.25rem;
            color: var(--cp_product_color-text-muted);
        }

        .cp_product_bold-text {
            font-weight: 700;
            color: var(--cp_product_color-text-main);
        }

        .cp_product_tip-box {
            background-color: var(--cp_product_color-brand-light);
            border: 1px solid rgba(255, 107, 74, 0.2);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: var(--cp_product_color-text-muted);
        }

        .cp_product_tip-label {
            color: var(--cp_product_color-brand);
            font-weight: 700;
        }

        /* Sidebar */
        .cp_product_sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .cp_product_download-btn {
            width: 100%;
            padding: 0.75rem;
            background-color: transparent;
            border: 2px solid var(--cp_product_color-brand);
            color: var(--cp_product_color-brand);
            font-weight: 700;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .cp_product_download-btn:hover {
            background-color: var(--cp_product_color-brand);
            color: var(--cp_product_color-white);
        }

        .cp_product_ingredients-card {
            border: 1px solid var(--cp_product_color-border);
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .cp_product_ingredients-title {
            font-size: 1.5rem;
            color: var(--cp_product_color-brand);
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .cp_product_ingredient-group {
            margin-bottom: 1.5rem;
        }

        .cp_product_ingredient-group-title {
            font-size: 1rem;
            font-weight: 700;
            border-bottom: 1px solid var(--cp_product_color-border);
            padding-bottom: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .cp_product_ingredient-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            padding: 0.5rem;
            border-radius: 0.25rem;
        }

        .cp_product_ingredient-row.cp_product_highlight {
            background-color: rgba(255, 107, 74, 0.05);
        }

        .cp_product_ingredient-amount {
            font-weight: 500;
        }

        .cp_product_sidebar-section-title {
            font-size: 1.25rem;
            color: var(--cp_product_color-brand);
            margin-bottom: 1rem;
        }

        .cp_product_allergens-text {
            font-size: 0.75rem;
            color: var(--cp_product_color-text-muted);
            line-height: 1.6;
        }

        .cp_product_nutrition-table {
            width: 100%;
            font-size: 0.75rem;
            border-collapse: collapse;
        }

        .cp_product_nutrition-header {
            text-align: right;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--cp_product_color-border);
        }

        .cp_product_nutrition-cell {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--cp_product_color-border);
        }

        .cp_product_nutrition-row.cp_product_alt {
            background-color: var(--cp_product_color-bg-light);
        }

        .cp_product_nutrition-cell-right {
            text-align: right;
        }

        .cp_product_nutrition-disclaimer {
            font-size: 0.625rem;
            color: var(--cp_product_color-text-muted);
            font-style: italic;
            margin-top: 1rem;
        }
        
    </style>
</head>
<body class="cp_product_reset cp_product_body">
    <div class="cp_product_container">
        <!-- Header Section -->
        <header class="cp_product_header">
            <div class="cp_product_hero-image-container">
                <img src="https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&q=80&w=1000" alt="Pan-fried Salmon" class="cp_product_hero-image">
                <div class="cp_product_time-badge">
                    <i data-lucide="clock" class="cp_product_icon-small"></i>
                    30 min
                </div>
            </div>
            
            <div class="cp_product_header-content">
                <h1 class="cp_product_title">Pan-fried Salmon with <br>White Bean Ragout</h1>
                <p class="cp_product_sub-title">and Olive Tapenade</p>
                
                <p class="cp_product_description">
                    This light bean ragout with sun dried tomatoes and peppers makes for a perfect summer night supper.
                </p>
                
                <div class="cp_product_reviews">
                    <div class="cp_product_stars">
                        <i data-lucide="star" class="cp_product_icon-medium cp_product_icon-fill"></i>
                        <i data-lucide="star" class="cp_product_icon-medium cp_product_icon-fill"></i>
                        <i data-lucide="star" class="cp_product_icon-medium cp_product_icon-fill"></i>
                        <i data-lucide="star" class="cp_product_icon-medium cp_product_icon-fill"></i>
                        <i data-lucide="star" class="cp_product_icon-medium cp_product_icon-muted"></i>
                    </div>
                    <span class="cp_product_review-count">550 Reviews</span>
                </div>
                
                <div class="cp_product_macros">
                    <span class="cp_product_macro-item">Cals 560</span>
                    <span class="cp_product_macro-divider">|</span>
                    <span class="cp_product_macro-item">Prot 43</span>
                    <span class="cp_product_macro-divider">|</span>
                    <span class="cp_product_macro-item">Carbs 34</span>
                    <span class="cp_product_macro-divider">|</span>
                    <span class="cp_product_macro-item">Fat 28</span>
                </div>
                
                <div class="cp_product_badges">
                    <span class="cp_product_badge">Low Carb</span>
                    <span class="cp_product_badge">Calorie Smart</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="cp_product_main-grid">
            
            <!-- Instructions Column -->
            <div class="cp_product_instructions-column">
                <section class="cp_product_before-start">
                    <h2 class="cp_product_section-title">Before you start</h2>
                    <p class="cp_product_before-start-text">Please wash your hands and rinse all fresh fruits and vegetables prior to cooking.</p>
                </section>

                <section class="cp_product_instructions-section">
                    <h2 class="cp_product_section-title">Instructions</h2>
                    
                    <!-- Step 1 -->
                    <div class="cp_product_step">
                        <img src="https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&q=80&w=400" alt="Prep" class="cp_product_step-image">
                        <div class="cp_product_step-content">
                            <h3 class="cp_product_step-title"><span class="cp_product_step-number">1</span>Prep</h3>
                            <ul class="cp_product_step-list">
                                <li class="cp_product_step-list-item">Peel and finely chop the <span class="cp_product_bold-text">onion</span>.</li>
                                <li class="cp_product_step-list-item">Deseed and chop the <span class="cp_product_bold-text">pepper</span> into small bite-size pieces.</li>
                                <li class="cp_product_step-list-item">Roughly chop the <span class="cp_product_bold-text">sun dried tomatoes</span>.</li>
                                <li class="cp_product_step-list-item">Drain and rinse the <span class="cp_product_bold-text">white beans</span>.</li>
                                <li class="cp_product_step-list-item">Finely chop the <span class="cp_product_bold-text">capers</span> and <span class="cp_product_bold-text">olive slices</span>.</li>
                                <li class="cp_product_step-list-item">Add the <span class="cp_product_bold-text">capers</span> and <span class="cp_product_bold-text">olives</span> to a small bowl with the <span class="cp_product_bold-text">olive oil</span>.</li>
                                <li class="cp_product_step-list-item">Mix and set aside.</li>
                            </ul>
                            <div class="cp_product_tip-box">
                                <span class="cp_product_tip-label">Tip!</span> Add the capers and olives to a mortar and pestle and crush for a finer paste.
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="cp_product_step">
                        <img src="https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&q=80&w=400" alt="Fry" class="cp_product_step-image">
                        <div class="cp_product_step-content">
                            <h3 class="cp_product_step-title"><span class="cp_product_step-number">2</span>Fry</h3>
                            <ul class="cp_product_step-list">
                                <li class="cp_product_step-list-item">Heat a large pan over medium heat with a drizzle of <span class="cp_product_bold-text">olive oil</span>.</li>
                                <li class="cp_product_step-list-item">Once hot, fry the <span class="cp_product_bold-text">onions</span> and <span class="cp_product_bold-text">peppers</span> with a pinch of <span class="cp_product_bold-text">salt</span> for 5 min.</li>
                                <li class="cp_product_step-list-item">Add the <span class="cp_product_bold-text">sun-dried tomatoes, garlic powder, dried thyme, beans</span> and <span class="cp_product_bold-text">measured water</span>.</li>
                                <li class="cp_product_step-list-item">Simmer for 3 min or until the <span class="cp_product_bold-text">ragout</span> begins to thicken.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="cp_product_step">
                        <img src="https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&q=80&w=400" alt="Fry salmon" class="cp_product_step-image">
                        <div class="cp_product_step-content">
                            <h3 class="cp_product_step-title"><span class="cp_product_step-number">3</span>Fry salmon</h3>
                            <ul class="cp_product_step-list">
                                <li class="cp_product_step-list-item">Meanwhile, portion the <span class="cp_product_bold-text">salmon</span> into individual <span class="cp_product_bold-text">fillets</span>.</li>
                                <li class="cp_product_step-list-item">Heat a second pan over medium heat with a drizzle of <span class="cp_product_bold-text">olive oil</span>.</li>
                                <li class="cp_product_step-list-item">Once hot, fry the <span class="cp_product_bold-text">salmon fillets</span> skin side down with a pinch of <span class="cp_product_bold-text">salt</span> for 3-4 min on either side until cooked through.</li>
                                <li class="cp_product_step-list-item">Season with <span class="cp_product_bold-text">pepper</span>.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="cp_product_step">
                        <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=400" alt="Serve" class="cp_product_step-image">
                        <div class="cp_product_step-content">
                            <h3 class="cp_product_step-title"><span class="cp_product_step-number">4</span>Serve</h3>
                            <ul class="cp_product_step-list">
                                <li class="cp_product_step-list-item">Meanwhile, finely chop the <span class="cp_product_bold-text">parsley</span>.</li>
                                <li class="cp_product_step-list-item">Divide the <span class="cp_product_bold-text">Salmon</span> with the <span class="cp_product_bold-text">White Bean Ragout</span>.</li>
                                <li class="cp_product_step-list-item">Top with <span class="cp_product_bold-text">olive tapenade</span> and <span class="cp_product_bold-text">fresh parsley</span>.</li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Sidebar Column -->
            <aside class="cp_product_sidebar">
                <button class="cp_product_download-btn">
                    <i data-lucide="download" class="cp_product_icon-large"></i>
                    Download PDF
                </button>

                <div class="cp_product_ingredients-card">
                    <h2 class="cp_product_ingredients-title">Ingredients</h2>
                    
                    <div class="cp_product_ingredient-group">
                        <h3 class="cp_product_ingredient-group-title">Salmon</h3>
                        <div class="cp_product_ingredient-row cp_product_highlight">
                            <span class="cp_product_ingredient-name">Skin-on salmon fillet 6*</span>
                            <span class="cp_product_ingredient-amount">350 Grams</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Olive oil</span>
                            <span class="cp_product_ingredient-amount">0.5 Tbsp</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Salt</span>
                            <span class="cp_product_ingredient-amount">0.5 Tsp</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Black Ground Pepper</span>
                            <span class="cp_product_ingredient-amount">0.5 Tsp</span>
                        </div>
                    </div>

                    <div class="cp_product_ingredient-group">
                        <h3 class="cp_product_ingredient-group-title">Ragout</h3>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Red onion</span>
                            <span class="cp_product_ingredient-amount">1 Piece</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Red pepper</span>
                            <span class="cp_product_ingredient-amount">1 Piece</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Sun dried tomatoes</span>
                            <span class="cp_product_ingredient-amount">30 Grams</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">White beans</span>
                            <span class="cp_product_ingredient-amount">240 Grams</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Olive oil</span>
                            <span class="cp_product_ingredient-amount">0.5 Tbsp</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Garlic powder</span>
                            <span class="cp_product_ingredient-amount">2 Grams</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Dried thyme</span>
                            <span class="cp_product_ingredient-amount">2 Grams</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Water</span>
                            <span class="cp_product_ingredient-amount">100 ML</span>
                        </div>
                    </div>

                    <div class="cp_product_ingredient-group">
                        <h3 class="cp_product_ingredient-group-title">Olive</h3>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Capers</span>
                            <span class="cp_product_ingredient-amount">20 Grams</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Black olive slices</span>
                            <span class="cp_product_ingredient-amount">40 Grams</span>
                        </div>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Olive oil</span>
                            <span class="cp_product_ingredient-amount">0.5 Tbsp</span>
                        </div>
                    </div>

                    <div class="cp_product_ingredient-group">
                        <h3 class="cp_product_ingredient-group-title">To serve</h3>
                        <div class="cp_product_ingredient-row">
                            <span class="cp_product_ingredient-name">Fresh parsley</span>
                            <span class="cp_product_ingredient-amount">15 Grams</span>
                        </div>
                    </div>
                </div>

                <section class="cp_product_allergens-section">
                    <h2 class="cp_product_sidebar-section-title">Allergens</h2>
                    <p class="cp_product_allergens-text"><strong>*6 Fish</strong></p>
                    <p class="cp_product_allergens-text">Due to production methods, we cannot guarantee our products are completely free from any allergen such as <strong>Peanuts, Tree Nuts, Sesame Seeds, Milk, Egg, Fish, Crustaceans, Molluscs, Soya, Wheat, Gluten, Lupin, Mustard, Sulphur dioxide</strong> and <strong>Celery</strong>.</p>
                </section>

                <section class="cp_product_nutrition-section">
                    <h2 class="cp_product_sidebar-section-title">Nutritional information</h2>
                    <table class="cp_product_nutrition-table">
                        <thead>
                            <tr class="cp_product_nutrition-row">
                                <th class="cp_product_nutrition-header" colspan="2">Per Serving*</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="cp_product_nutrition-row">
                                <td class="cp_product_nutrition-cell">Energy (kJ/kcal)</td>
                                <td class="cp_product_nutrition-cell cp_product_nutrition-cell-right">2358 / 560</td>
                            </tr>
                            <tr class="cp_product_nutrition-row cp_product_alt">
                                <td class="cp_product_nutrition-cell">Fats (g)</td>
                                <td class="cp_product_nutrition-cell cp_product_nutrition-cell-right">28.3</td>
                            </tr>
                            <tr class="cp_product_nutrition-row">
                                <td class="cp_product_nutrition-cell">of which saturated (g)</td>
                                <td class="cp_product_nutrition-cell cp_product_nutrition-cell-right">5.3</td>
                            </tr>
                            <tr class="cp_product_nutrition-row cp_product_alt">
                                <td class="cp_product_nutrition-cell">Carbohydrates (g)</td>
                                <td class="cp_product_nutrition-cell cp_product_nutrition-cell-right">34</td>
                            </tr>
                            <tr class="cp_product_nutrition-row">
                                <td class="cp_product_nutrition-cell">of which sugars (g)</td>
                                <td class="cp_product_nutrition-cell cp_product_nutrition-cell-right">12.7</td>
                            </tr>
                            <tr class="cp_product_nutrition-row cp_product_alt">
                                <td class="cp_product_nutrition-cell">Fibers (g)</td>
                                <td class="cp_product_nutrition-cell cp_product_nutrition-cell-right">11.8</td>
                            </tr>
                            <tr class="cp_product_nutrition-row">
                                <td class="cp_product_nutrition-cell">Proteins (g)</td>
                                <td class="cp_product_nutrition-cell cp_product_nutrition-cell-right">43.1</td>
                            </tr>
                            <tr class="cp_product_nutrition-row cp_product_alt">
                                <td class="cp_product_nutrition-cell">Salt (g)</td>
                                <td class="cp_product_nutrition-cell cp_product_nutrition-cell-right">2</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="cp_product_nutrition-disclaimer">
                        *The nutritional information only applies to ingredients supplied by Hello Chef. The cooking process and additional ingredients added at home can affect the nutritional values.
                    </p>
                </section>
            </aside>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>
