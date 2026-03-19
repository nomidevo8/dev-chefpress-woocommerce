<?php
/**
 * Template Part: Steps / Instructions
 *
 * @var array<int, array<string, mixed>> $steps
 */
defined( 'ABSPATH' ) || exit;
?>
<section class="cp-section cp-section--steps">
	<h2 class="cp-section__title"><?php esc_html_e( 'Instructions', 'dev-chefpress' ); ?></h2>

	<ol class="cp-steps-list" itemprop="recipeInstructions" itemscope itemtype="https://schema.org/ItemList">
		<?php foreach ( $steps as $index => $step ) :
			$step_number      = (int) ( $step['step_number'] ?? $index + 1 );
			$step_title       = (string) ( $step['step_title'] ?? '' );
			$step_description = (string) ( $step['step_description'] ?? '' );
			$step_image_id    = (int) ( $step['step_image_id'] ?? 0 );
			$step_tip         = (string) ( $step['step_tip'] ?? '' );
			if ( ! $step_description && ! $step_title ) continue;
		?>
			<li class="cp-step" itemprop="itemListElement" itemscope itemtype="https://schema.org/HowToStep">
				<div class="cp-step__number"><?php echo esc_html( (string) $step_number ); ?></div>
				<div class="cp-step__content">
					<?php if ( $step_title ) : ?>
						<h3 class="cp-step__title" itemprop="name"><?php echo esc_html( $step_title ); ?></h3>
					<?php endif; ?>
					<?php if ( $step_description ) : ?>
						<p class="cp-step__desc" itemprop="text"><?php echo esc_html( $step_description ); ?></p>
					<?php endif; ?>
					<?php if ( $step_tip ) : ?>
						<div class="cp-step__tip">
							<span class="cp-step__tip-icon">💡</span>
							<span class="cp-step__tip-text"><?php echo esc_html( $step_tip ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $step_image_id ) : ?>
						<div class="cp-step__image">
							<?php echo wp_get_attachment_image( $step_image_id, 'medium', false, [ 'loading' => 'lazy', 'itemprop' => 'image' ] ); ?>
						</div>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
