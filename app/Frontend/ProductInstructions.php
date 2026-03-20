<?php
namespace DevChefPress\Frontend;

class ProductInstructions {
    private $post_id;

    public function __construct( $post_id ) {
        $this->post_id = $post_id;
    }

    public function get_before_start() {
        return get_post_meta( $this->post_id, '_chefpress_before_start', true );
    }

    public function get_steps() {
        $steps = get_post_meta( $this->post_id, '_chefpress_steps', true );
        return is_array( $steps ) ? $steps : [];
    }

    public function render() {
        $before_start = $this->get_before_start();
        $steps = $this->get_steps();
        ?>
        <div class="cp_product_instructions-column">
            <?php if ( $before_start ) : ?>
            <section class="cp_product_before-start">
                <h2 class="cp_product_section-title">Before you start</h2>
                <p class="cp_product_before-start-text"><?php echo esc_html( $before_start ); ?></p>
            </section>
            <?php endif; ?>

            <section class="cp_product_instructions-section">
                <h2 class="cp_product_section-title">Instructions</h2>
                <?php foreach ( $steps as $step ) :
                    $img_url = '';
                    if ( ! empty( $step['step_image_id'] ) ) {
                        $img_url = wp_get_attachment_image_url( $step['step_image_id'], 'large' );
                    }
                ?>
                <div class="cp_product_step">
                    <?php if ( $img_url ) : ?>
                        <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $step['step_title'] ?? '' ); ?>" class="cp_product_step-image">
                    <?php endif; ?>
                    <div class="cp_product_step-content">
                        <h3 class="cp_product_step-title">
                            <span class="cp_product_step-number"><?php echo esc_html( $step['step_number'] ?? '' ); ?></span>
                            <?php echo esc_html( $step['step_title'] ?? '' ); ?>
                        </h3>
                        <?php if ( ! empty( $step['step_description'] ) ) : ?>
                        <ul class="cp_product_step-list">
                            <li class="cp_product_step-list-item"><?php echo wp_kses_post( $step['step_description'] ); ?></li>
                        </ul>
                        <?php endif; ?>
                        <?php if ( ! empty( $step['step_tip'] ) ) : ?>
                        <div class="cp_product_tip-box">
                            <span class="cp_product_tip-label">Tip!</span> <?php echo esc_html( $step['step_tip'] ); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
        </div>
        <?php
    }
}