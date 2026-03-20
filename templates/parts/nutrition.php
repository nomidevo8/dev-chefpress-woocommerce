<?php
/**
 * Template Part: Nutrition Table
 *
 * @var array<string, mixed> $nutr_tbl
 */
defined( 'ABSPATH' ) || exit;

use DevChefPress\Services\PluginSettings;

$per_label = (string) ( $nutr_tbl['per_serving_label'] ?? '' );
$note      = (string) ( $nutr_tbl['nutrition_note'] ?? '' );
$defs      = PluginSettings::get_nutrition_fields();

$rows = [];
foreach ( $defs as $def ) {
	$key = $def['key'];
	$raw = $nutr_tbl[ $key ] ?? '';
	if ( '' === $raw || null === $raw ) {
		continue;
	}
	$num = is_numeric( $raw ) ? (float) $raw : null;
	$unit = (string) ( $def['unit'] ?? '' );
	if ( null !== $num ) {
		$val = ( abs( $num - round( $num ) ) < 0.0001 )
			? (string) (int) round( $num )
			: (string) $num;
		if ( '' !== $unit ) {
			$val .= ' ' . $unit;
		}
	} else {
		$val = (string) $raw;
	}
	$rows[] = [
		'label' => (string) ( $def['label'] ?? $key ),
		'value' => $val,
		'class' => '',
	];
}
?>
<section class="cp-section cp-section--nutrition">
	<h2 class="cp-section__title"><?php esc_html_e( 'Nutrition Information', 'dev-chefpress' ); ?></h2>

	<div class="cp-nutr-table-wrap">
		<?php if ( ! empty( $rows ) ) : ?>
			<table class="cp-nutr-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Nutrient', 'dev-chefpress' ); ?></th>
						<th><?php echo esc_html( $per_label ?: __( 'Per Serving', 'dev-chefpress' ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr class="cp-nutr-row <?php echo esc_attr( $row['class'] ); ?>">
							<td class="cp-nutr-row__label"><?php echo esc_html( $row['label'] ); ?></td>
							<td class="cp-nutr-row__value"><?php echo esc_html( $row['value'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php if ( $note ) : ?>
			<p class="cp-nutr-note"><?php echo esc_html( $note ); ?></p>
		<?php endif; ?>
	</div>
</section>
