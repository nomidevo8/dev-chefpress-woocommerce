<?php
/**
 * Product Hero Section for Recipe Product Page
 */

namespace DevChefPress\Frontend;

class ProductHero {
    private $post_id;
    private $recipe;

    public function __construct( $post_id ) {
        $this->post_id = $post_id;
        $this->recipe = get_post( $post_id );
    }

    /**
     * Get the product (recipe) image URL
     *
     * @return string
     */
    public function get_image_url() {
        $image_id = get_post_thumbnail_id( $this->post_id );
        if ( $image_id ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'large' );
            if ( $image_url ) {
                return $image_url;
            }
        }
        // Fallback image (optional)
        return '';
    }

    /**
     * Get the recipe cooking time
     *
     * @return string
     */
    public function get_cooking_time() {
        $cooking_time = get_post_meta( $this->post_id, '_chefpress_cooking_time', true );
        return $cooking_time ? esc_html( $cooking_time ) : '';
    }

    /**
     * Render the hero section HTML
     */
    public function render() {
        $image_url = $this->get_image_url();
        $cooking_time = $this->get_cooking_time();
        ?>
        <div class="cp_product_hero-image-container">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title( $this->post_id ) ); ?>" class="cp_product_hero-image">
            <?php endif; ?>
            <?php if ( $cooking_time ) : ?>
                <div class="cp_product_time-badge">
                    <i data-lucide="clock" class="cp_product_icon-small"></i>
                    <?php echo $cooking_time; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
