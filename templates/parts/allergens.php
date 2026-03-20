<?php
/**
 * Template Part: Allergens
 *
 * @var array<string, mixed> $allergens
 */
defined( 'ABSPATH' ) || exit;

$main_allergen = (string) ( $allergens['main_allergen'] ?? '' );
$description   = (string) ( $allergens['allergen_description'] ?? '' );
$list          = (array) ( $allergens['allergen_list'] ?? [] );
?>
<section class="cp-section cp-section--allergens">
	<h2 class="cp-section__title"><?php esc_html_e( 'Allergen Information', 'dev-chefpress' ); ?></h2>

	<div class="cp-allergen-box">
		<div class="cp-allergen-box__icon">⚠️</div>
		<div class="cp-allergen-box__content">
			<?php if ( $main_allergen ) : ?>
				<p class="cp-allergen-box__main">
					<strong><?php esc_html_e( 'Contains:', 'dev-chefpress' ); ?></strong>
					<?php echo esc_html( $main_allergen ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $list ) ) : ?>
				<div class="cp-allergen-tags">
					<?php foreach ( $list as $tag ) : ?>
						<span class="cp-allergen-tag"><?php echo esc_html( $tag ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $description ) : ?>
				<div class="cp-allergen-box__desc cp-allergen-box__desc--richtext">
					<?php echo wp_kses_post( $description ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
