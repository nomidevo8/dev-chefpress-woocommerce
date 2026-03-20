
<?php
use DevChefPress\Frontend\ProductHero;
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<body class="cp_product_reset cp_product_body">

    <div class="cp_product_container">
        <!-- Header Section -->
        <header class="cp_product_header">
            <?php
            $hero = new ProductHero( get_the_ID() );
            $hero->render();
            ?>
            <!-- Product Header Content -->
        </header>

        <!-- Main Content -->
        <div class="cp_product_main-grid">
            <!-- Product Instructions and Details  -->
            <!-- Product Sidebar with Ingredients and Download Link -->
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>

