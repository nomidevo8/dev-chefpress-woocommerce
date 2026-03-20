
<?php
use DevChefPress\Frontend\ProductHero;
use DevChefPress\Frontend\ProductHeader;
use DevChefPress\Frontend\ProductInstructions;

if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$product_id = get_the_ID();
?>
<body class="cp_product_reset cp_product_body">

    <div class="cp_product_container">
        <!-- Header Section -->
        <header class="cp_product_header">
            <?php
            $hero = new ProductHero( $product_id );
            $hero->render();

            // Render dynamic product header content
            $header = new ProductHeader( $product_id );
            $header->render();
            ?>
        </header>

        <!-- Main Content -->
        <div class="cp_product_main-grid">
            <?php
            $instructions = new ProductInstructions( $product_id );
            $instructions->render();

            // Product Sidebar with Ingredients, Allergens, Nutrition
            $sidebar = new \DevChefPress\Frontend\ProductSidebar( $product_id );
            $sidebar->render();
            ?>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        
    </script>
</body>

