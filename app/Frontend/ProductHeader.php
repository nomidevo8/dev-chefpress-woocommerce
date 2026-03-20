<?php
namespace DevChefPress\Frontend;

class ProductHeader {
    private $post_id;
    private $post;

    public function __construct( $post_id ) {
        $this->post_id = $post_id;
        $this->post = get_post( $post_id );
    }

    public function get_hero() {
        return [
            'subtitle'      => (string) get_post_meta( $this->post_id, '_chefpress_subtitle', true ),
            'cooking_time'  => (string) get_post_meta( $this->post_id, '_chefpress_cooking_time', true ),
            'reviews_count' => (int) get_post_meta( $this->post_id, '_chefpress_reviews_count', true ),
            'rating'        => (float) get_post_meta( $this->post_id, '_chefpress_rating', true ),
        ];
    }

    public function get_title() {
        return get_the_title( $this->post_id );
    }

    public function get_subtitle() {
        $hero = $this->get_hero();
        return $hero['subtitle'];
    }

    public function get_description() {
        return get_the_excerpt( $this->post_id );
    }

    public function get_reviews_count() {
        $hero = $this->get_hero();
        return $hero['reviews_count'];
    }

    public function get_rating() {
        $hero = $this->get_hero();
        return $hero['rating'];
    }

    public function get_macros() {
        $macros = [];
        $fields = [
            'calories' => 'Cals',
            'protein'  => 'Prot',
            'carbs'    => 'Carbs',
            'fat'      => 'Fat',
        ];
        foreach ( $fields as $key => $label ) {
            $meta_key = '_chefpress_' . $key;
            $value = get_post_meta( $this->post_id, $meta_key, true );
            if ( $value !== '' ) {
                $macros[] = [ 'label' => $label, 'value' => $value ];
            }
        }
        return $macros;
    }

    public function get_tags() {
        $terms = get_the_terms( $this->post_id, 'chefpress_recipe_tag' );
        if ( ! is_wp_error( $terms ) && is_array( $terms ) && ! empty( $terms ) ) {
            return array_values(
                array_filter(
                    array_map(
                        static function ( $term ) {
                            return trim( (string) $term->name );
                        },
                        $terms
                    )
                )
            );
        }
        $tags = get_post_meta( $this->post_id, '_chefpress_tags', true );
        return is_array( $tags ) ? array_values( array_filter( array_map( 'strval', $tags ) ) ) : [];
    }

    public function get_badges() {
        $terms = get_the_terms( $this->post_id, 'product_cat' );
        if ( ! is_wp_error( $terms ) && is_array( $terms ) && ! empty( $terms ) ) {
            return array_values(
                array_filter(
                    array_map(
                        static function ( $term ) {
                            return trim( (string) $term->name );
                        },
                        $terms
                    )
                )
            );
        }
        return [];
    }

    public function render() {
        ?>
        <div class="cp_product_header-content">
            <h1 class="cp_product_title"><?php echo esc_html( $this->get_title() ); ?></h1>
            <p class="cp_product_sub-title"><?php echo esc_html( $this->get_subtitle() ); ?></p>

            <p class="cp_product_description">
                <?php echo esc_html( $this->get_description() ); ?>
            </p>

            <div class="cp_product_reviews">
                <div class="cp_product_stars">
                    <?php
                    $rating = $this->get_rating();
                    for ( $i = 1; $i <= 5; $i++ ) {
                        $icon_class = $i <= round( $rating ) ? 'cp_product_icon-fill' : 'cp_product_icon-muted';
                        echo '<i data-lucide="star" class="cp_product_icon-medium ' . esc_attr( $icon_class ) . '"></i>';
                    }
                    ?>
                </div>
                <span class="cp_product_review-count"><?php echo esc_html( $this->get_reviews_count() ); ?> Reviews</span>
            </div>

            <div class="cp_product_macros">
                <?php
                $macros = $this->get_macros();
                foreach ( $macros as $idx => $macro ) {
                    if ( $idx > 0 ) {
                        echo '<span class="cp_product_macro-divider">|</span>';
                    }
                    echo '<span class="cp_product_macro-item">' . esc_html( $macro['label'] ) . ' ' . esc_html( $macro['value'] ) . '</span>';
                }
                ?>
            </div>

            <div class="cp_product_badges">
                <?php foreach ( $this->get_badges() as $badge ) : ?>
                    <span class="cp_product_badge"><?php echo esc_html( $badge ); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}