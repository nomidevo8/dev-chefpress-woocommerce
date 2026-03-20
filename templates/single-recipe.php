<?php
/**
 * Single Recipe Template
 *
 * @var \DevChefPress\Models\Recipe   $recipe
 * @var \WC_Product                   $product
 */
defined( 'ABSPATH' ) || exit;

use DevChefPress\Frontend\TemplateLoader;

$loader    = new TemplateLoader();
$hero      = $recipe->get_hero();
$nutrition = $recipe->get_nutrition();
$tags      = $recipe->get_tags();
$before    = $recipe->get_before_start();
$steps     = $recipe->get_steps();
$groups    = $recipe->get_ingredients();
$allergens = $recipe->get_allergens();
$nutr_tbl  = $recipe->get_nutrition_table();

/**
 * Hook: devchefpress_before_recipe
 */
do_action( 'devchefpress_before_recipe', $recipe, $product );
?>
<div class="cp-recipe" itemscope itemtype="https://schema.org/Recipe">

	<!-- Hero -->
	<?php $loader->render_part( 'hero', [ 'recipe' => $recipe, 'hero' => $hero, 'nutrition' => $nutrition, 'tags' => $tags, 'product' => $product ] ); ?>

	<div class="cp-recipe__body">

		<!-- Before You Start -->
		<?php if ( $before ) : ?>
			<section class="cp-section cp-section--before-start">
				<h2 class="cp-section__title"><?php esc_html_e( 'Before You Start', 'dev-chefpress' ); ?></h2>
				<div class="cp-before-start-box">
					<span class="cp-before-start-box__icon">💡</span>
					<div class="cp-before-start-box__text"><?php echo wp_kses_post( nl2br( esc_html( $before ) ) ); ?></div>
				</div>
			</section>
		<?php endif; ?>

		<div class="cp-recipe__two-col">

			<!-- Ingredients -->
			<?php if ( ! empty( $groups ) ) : ?>
				<?php $loader->render_part( 'ingredients', [ 'groups' => $groups ] ); ?>
			<?php endif; ?>

			<!-- Instructions -->
			<?php if ( ! empty( $steps ) ) : ?>
				<?php $loader->render_part( 'steps', [ 'steps' => $steps ] ); ?>
			<?php endif; ?>

		</div><!-- /.cp-recipe__two-col -->

		<!-- Nutrition Table -->
		<?php if ( $recipe->has_nutrition_table_content() ) : ?>
			<?php $loader->render_part( 'nutrition', [ 'nutr_tbl' => $nutr_tbl ] ); ?>
		<?php endif; ?>

		<!-- Allergens -->
		<?php if ( $allergens['main_allergen'] || ! empty( $allergens['allergen_list'] ) || $allergens['allergen_description'] ) : ?>
			<?php $loader->render_part( 'allergens', [ 'allergens' => $allergens ] ); ?>
		<?php endif; ?>

	</div><!-- /.cp-recipe__body -->

</div><!-- /.cp-recipe -->

<?php
/**
 * Hook: devchefpress_after_recipe
 */
do_action( 'devchefpress_after_recipe', $recipe, $product );
?>
