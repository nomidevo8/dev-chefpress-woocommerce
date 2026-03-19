<?php
/**
 * Template Part: Ingredients
 *
 * @var array<int, array<string, mixed>> $groups
 */
defined( 'ABSPATH' ) || exit;
?>
<section class="cp-section cp-section--ingredients" itemprop="recipeIngredient">
	<h2 class="cp-section__title"><?php esc_html_e( 'Ingredients', 'dev-chefpress' ); ?></h2>

	<?php foreach ( $groups as $group ) :
		$group_name  = (string) ( $group['group_name'] ?? '' );
		$ingredients = (array) ( $group['ingredients'] ?? [] );
		if ( empty( $ingredients ) ) continue;
	?>
		<div class="cp-ingredient-group">
			<?php if ( $group_name ) : ?>
				<h3 class="cp-ingredient-group__name"><?php echo esc_html( $group_name ); ?></h3>
			<?php endif; ?>
			<ul class="cp-ingredient-list">
				<?php foreach ( $ingredients as $ing ) :
					$name     = (string) ( $ing['ingredient_name'] ?? '' );
					$quantity = (string) ( $ing['quantity'] ?? '' );
					$unit     = (string) ( $ing['unit'] ?? '' );
					if ( ! $name ) continue;
				?>
					<li class="cp-ingredient-list__item">
						<span class="cp-ingredient-list__check">✓</span>
						<?php if ( $quantity || $unit ) : ?>
							<span class="cp-ingredient-list__amount">
								<?php echo esc_html( trim( $quantity . ' ' . $unit ) ); ?>
							</span>
						<?php endif; ?>
						<span class="cp-ingredient-list__name"><?php echo esc_html( $name ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div><!-- /.cp-ingredient-group -->
	<?php endforeach; ?>
</section>
