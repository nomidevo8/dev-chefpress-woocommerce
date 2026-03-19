<?php
/**
 * Template Part: Hero
 *
 * @var \DevChefPress\Models\Recipe $recipe
 * @var array<string, mixed>        $hero
 * @var array<string, mixed>        $nutrition
 * @var string[]                    $tags
 * @var \WC_Product                 $product
 */
defined( 'ABSPATH' ) || exit;

use DevChefPress\Helpers\Formatter;

$rating        = (float) ( $hero['rating'] ?? 0 );
$reviews_count = (int) ( $hero['reviews_count'] ?? 0 );
$subtitle      = (string) ( $hero['subtitle'] ?? '' );
$cooking_time  = (string) ( $hero['cooking_time'] ?? '' );
?>
<section class="cp-hero">
	<div class="cp-hero__inner">
		<div class="cp-hero__content">

			<?php if ( ! empty( $tags ) ) : ?>
				<div class="cp-hero__tags">
					<?php foreach ( $tags as $tag ) : ?>
						<span class="cp-tag-label"><?php echo esc_html( $tag ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<h1 class="cp-hero__title" itemprop="name"><?php echo esc_html( get_the_title() ); ?></h1>

			<?php if ( $subtitle ) : ?>
				<p class="cp-hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>

			<div class="cp-hero__meta">
				<?php if ( $rating > 0 ) : ?>
					<div class="cp-hero__rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
						<meta itemprop="ratingValue" content="<?php echo esc_attr( (string) $rating ); ?>" />
						<meta itemprop="ratingCount" content="<?php echo esc_attr( (string) $reviews_count ); ?>" />
						<?php echo wp_kses_post( Formatter::stars( $rating ) ); ?>
						<span class="cp-hero__rating-value"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
						<?php if ( $reviews_count > 0 ) : ?>
							<span class="cp-hero__reviews">
								(<?php echo esc_html( number_format_i18n( $reviews_count ) ); ?> <?php esc_html_e( 'reviews', 'dev-chefpress' ); ?>)
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $cooking_time ) : ?>
					<div class="cp-hero__time">
						<span class="cp-hero__time-icon">⏱</span>
						<span itemprop="totalTime"><?php echo esc_html( $cooking_time ); ?></span>
					</div>
				<?php endif; ?>
			</div>

		</div><!-- /.cp-hero__content -->

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="cp-hero__image">
				<?php the_post_thumbnail( 'large', [ 'class' => 'cp-hero__img', 'loading' => 'lazy', 'itemprop' => 'image' ] ); ?>
			</div>
		<?php endif; ?>
	</div><!-- /.cp-hero__inner -->

	<!-- Nutrition Quick-Bar -->
	<?php
	$calories = (int) ( $nutrition['calories'] ?? 0 );
	$protein  = (float) ( $nutrition['protein'] ?? 0 );
	$carbs    = (float) ( $nutrition['carbs'] ?? 0 );
	$fat      = (float) ( $nutrition['fat'] ?? 0 );

	if ( $calories || $protein || $carbs || $fat ) :
	?>
		<div class="cp-nutr-bar" itemprop="nutrition" itemscope itemtype="https://schema.org/NutritionInformation">
			<?php if ( $calories ) : ?>
				<div class="cp-nutr-bar__item">
					<span class="cp-nutr-bar__value" itemprop="calories"><?php echo esc_html( number_format_i18n( $calories ) ); ?></span>
					<span class="cp-nutr-bar__label"><?php esc_html_e( 'Calories', 'dev-chefpress' ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $protein ) : ?>
				<div class="cp-nutr-bar__item">
					<span class="cp-nutr-bar__value" itemprop="proteinContent"><?php echo esc_html( Formatter::number( $protein ) ); ?>g</span>
					<span class="cp-nutr-bar__label"><?php esc_html_e( 'Protein', 'dev-chefpress' ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $carbs ) : ?>
				<div class="cp-nutr-bar__item">
					<span class="cp-nutr-bar__value" itemprop="carbohydrateContent"><?php echo esc_html( Formatter::number( $carbs ) ); ?>g</span>
					<span class="cp-nutr-bar__label"><?php esc_html_e( 'Carbs', 'dev-chefpress' ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $fat ) : ?>
				<div class="cp-nutr-bar__item">
					<span class="cp-nutr-bar__value" itemprop="fatContent"><?php echo esc_html( Formatter::number( $fat ) ); ?>g</span>
					<span class="cp-nutr-bar__label"><?php esc_html_e( 'Fat', 'dev-chefpress' ); ?></span>
				</div>
			<?php endif; ?>
		</div><!-- /.cp-nutr-bar -->
	<?php endif; ?>

</section><!-- /.cp-hero -->
