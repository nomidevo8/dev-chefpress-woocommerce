
<?php
use DevChefPress\Frontend\ProductHero;
use DevChefPress\Frontend\ProductHeader;
use DevChefPress\Frontend\ProductInstructions;

if ( ! defined( 'ABSPATH' ) ) exit;
// get_header();

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

        <!-- Recipe Video Section -->
        <?php
        $recipe = new \DevChefPress\Models\Recipe( $product_id );
        $hero = $recipe->get_hero();
        if ( ! empty( $hero['video_url'] ) ) :
        ?>
        <section class="cp_product_video-section">
            <div class="cp_product_container">
                <h2 class="cp_product_video-title">
                    <?php echo esc_html( $hero['video_title'] ?: __( 'Recipe Video', 'dev-chefpress' ) ); ?>
                </h2>
                <div class="cp_product_video-wrapper">
                    <?php
                    $video_url = $hero['video_url'];
                    if ( strpos( $video_url, 'youtube.com' ) !== false || strpos( $video_url, 'youtu.be' ) !== false ) {
                        // YouTube video
                        $video_id = '';
                        if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches ) ) {
                            $video_id = $matches[1];
                        }
                        if ( $video_id ) {
                            echo '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . esc_attr( $video_id ) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                        }
                    } elseif ( strpos( $video_url, 'vimeo.com' ) !== false ) {
                        // Vimeo video
                        $video_id = '';
                        if ( preg_match( '/vimeo\.com\/(\d+)/', $video_url, $matches ) ) {
                            $video_id = $matches[1];
                        }
                        if ( $video_id ) {
                            echo '<iframe width="560" height="315" src="https://player.vimeo.com/video/' . esc_attr( $video_id ) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                        }
                    } else {
                        // Direct video file or other URL
                        echo '<video width="560" height="315" controls>';
                        echo '<source src="' . esc_url( $video_url ) . '" type="video/mp4">';
                        echo 'Your browser does not support the video tag.';
                        echo '</video>';
                    }
                    ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <script>
        // Initialize Lucide icons
        
    </script>
</body>

