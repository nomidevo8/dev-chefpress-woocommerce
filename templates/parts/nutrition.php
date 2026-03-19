<?php
/**
 * Template Part: Nutrition Table
 *
 * @var array<string, mixed> $nutr_tbl
 */
defined( 'ABSPATH' ) || exit;

$per_label = (string) ( $nutr_tbl['per_serving_label'] ?? '' );
$note      = (string) ( $nutr_tbl['nutrition_note'] ?? '' );

$rows = [
	[ 'label' => __( 'Energy', 'dev-chefpress' ), 'value' => trim( ( $nutr_tbl['energy_kj'] ? $nutr_tbl['energy_kj'] . ' kJ' : '' ) . ' / ' . ( $nutr_tbl['energy_kcal'] ? $nutr_tbl['energy_kcal'] . ' kcal' : '' ) ), 'class' => '' ],
	[ 'label' => __( 'Fat', 'dev-chefpress' ), 'value' => $nutr_tbl['fats'] ? $nutr_tbl['fats'] . 'g' : '', 'class' => '' ],
	[ 'label' => __( 'of which Saturated', 'dev-chefpress' ), 'value' => $nutr_tbl['saturated_fats'] ? $nutr_tbl['saturated_fats'] . 'g' : '', 'class' => 'cp-nutr-row--sub' ],
	[ 'label' => __( 'Carbohydrates', 'dev-chefpress' ), 'value' => $nutr_tbl['carbs'] ? $nutr_tbl['carbs'] . 'g' : '', 'class' => '' ],
	[ 'label' => __( 'of which Sugars', 'dev-chefpress' ), 'value' => $nutr_tbl['sugars'] ? $nutr_tbl['sugars'] . 'g' : '', 'class' => 'cp-nutr-row--sub' ],
	[ 'label' => __( 'Fibre', 'dev-chefpress' ), 'value' => $nutr_tbl['fibers'] ? $nutr_tbl['fibers'] . 'g' : '', 'class' => '' ],
	[ 'label' => __( 'Protein', 'dev-chefpress' ), 'value' => $nutr_tbl['proteins'] ? $nutr_tbl['proteins'] . 'g' : '', 'class' => '' ],
	[ 'label' => __( 'Salt', 'dev-chefpress' ), 'value' => $nutr_tbl['salt'] ? $nutr_tbl['salt'] . 'g' : '', 'class' => '' ],
];
?>
<section class="cp-section cp-section--nutrition">
	<h2 class="cp-section__title"><?php esc_html_e( 'Nutrition Information', 'dev-chefpress' ); ?></h2>

	<div class="cp-nutr-table-wrap">
		<table class="cp-nutr-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Nutrient', 'dev-chefpress' ); ?></th>
					<th><?php echo esc_html( $per_label ?: __( 'Per Serving', 'dev-chefpress' ) ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) :
					if ( '' === $row['value'] || '/' === trim( $row['value'], ' /' ) ) continue;
				?>
					<tr class="cp-nutr-row <?php echo esc_attr( $row['class'] ); ?>">
						<td class="cp-nutr-row__label"><?php echo esc_html( $row['label'] ); ?></td>
						<td class="cp-nutr-row__value"><?php echo esc_html( $row['value'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $note ) : ?>
			<p class="cp-nutr-note"><?php echo esc_html( $note ); ?></p>
		<?php endif; ?>
	</div>
</section>
