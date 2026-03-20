<?php
namespace DevChefPress\Frontend;

use DevChefPress\Services\PluginSettings;

class ProductSidebar {
    private $post_id;

    public function __construct( $post_id ) {
        $this->post_id = $post_id;
    }

    public function get_groups() {
        $groups = get_post_meta( $this->post_id, '_chefpress_groups', true );
        return is_array( $groups ) ? $groups : [];
    }

    public function get_allergens() {
        return [
            'main'        => get_post_meta( $this->post_id, '_chefpress_main_allergen', true ),
            'description' => get_post_meta( $this->post_id, '_chefpress_allergen_description', true ),
            'list'        => get_post_meta( $this->post_id, '_chefpress_allergen_list', true ),
        ];
    }

    public function get_nutrition() {
        $fields = PluginSettings::get_nutrition_fields();
        $nutrition = [];
        foreach ( $fields as $def ) {
            $key = '_chefpress_nutr_' . $def['key'];
            $nutrition[] = [
                'label' => $def['label'],
                'unit'  => $def['unit'] ?? '',
                'value' => get_post_meta( $this->post_id, $key, true ),
            ];
        }
        return $nutrition;
    }

    public function get_nutrition_note() {
        return get_post_meta( $this->post_id, '_chefpress_nutrition_note', true );
    }

    public function get_per_serving_label() {
        return get_post_meta( $this->post_id, '_chefpress_per_serving_label', true );
    }

    public function render() {
        $groups = $this->get_groups();
        $allergens = $this->get_allergens();
        $nutrition = $this->get_nutrition();
        $nutrition_note = $this->get_nutrition_note();
        $per_serving_label = $this->get_per_serving_label();
        ?>
        <aside class="cp_product_sidebar">
            <button class="cp_product_download-btn">
                <i data-lucide="download" class="cp_product_icon-large"></i>
                Download PDF
            </button>

            <div class="cp_product_ingredients-card">
                <h2 class="cp_product_ingredients-title">Ingredients</h2>
                <?php foreach ( $groups as $group ) : ?>
                    <div class="cp_product_ingredient-group">
                        <h3 class="cp_product_ingredient-group-title"><?php echo esc_html( $group['group_name'] ?? '' ); ?></h3>
                        <?php if ( ! empty( $group['ingredients'] ) && is_array( $group['ingredients'] ) ) :
                            foreach ( $group['ingredients'] as $ingredient ) : ?>
                                <div class="cp_product_ingredient-row<?php echo !empty($ingredient['highlight']) ? ' cp_product_highlight' : ''; ?>">
                                    <span class="cp_product_ingredient-name"><?php echo esc_html( $ingredient['ingredient_name'] ?? '' ); ?></span>
                                    <span class="cp_product_ingredient-amount"><?php echo esc_html( $ingredient['quantity'] ?? '' ) . ' ' . esc_html( $ingredient['unit'] ?? '' ); ?></span>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <section class="cp_product_allergens-section">
                <h2 class="cp_product_sidebar-section-title">Allergens</h2>
                <?php if ( ! empty( $allergens['main'] ) ) : ?>
                    <p class="cp_product_allergens-text"><strong><?php echo esc_html( $allergens['main'] ); ?></strong></p>
                <?php endif; ?>
                <?php if ( ! empty( $allergens['list'] ) && is_array( $allergens['list'] ) ) : ?>
                    <p class="cp_product_allergens-text">
                        <?php echo esc_html( implode( ', ', $allergens['list'] ) ); ?>
                    </p>
                <?php endif; ?>
                <?php if ( ! empty( $allergens['description'] ) ) : ?>
                    <p class="cp_product_allergens-text"><?php echo esc_html( $allergens['description'] ); ?></p>
                <?php endif; ?>
            </section>

            <section class="cp_product_nutrition-section">
                <h2 class="cp_product_sidebar-section-title">Nutritional information</h2>
                <table class="cp_product_nutrition-table">
                    <thead>
                        <tr class="cp_product_nutrition-row">
                            <th class="cp_product_nutrition-header" colspan="2">
                                <?php echo esc_html( $per_serving_label ?: 'Per Serving*' ); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $nutrition as $row ) : ?>
                            <tr class="cp_product_nutrition-row">
                                <td class="cp_product_nutrition-cell"><?php echo esc_html( $row['label'] ); ?><?php if ( $row['unit'] ) echo ' (' . esc_html( $row['unit'] ) . ')'; ?></td>
                                <td class="cp_product_nutrition-cell cp_product_nutrition-cell-right"><?php echo esc_html( $row['value'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ( $nutrition_note ) : ?>
                    <p class="cp_product_nutrition-disclaimer"><?php echo esc_html( $nutrition_note ); ?></p>
                <?php endif; ?>
            </section>
        </aside>
        <?php
    }
}