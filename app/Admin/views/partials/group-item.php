<?php
/**
 * Ingredient group item partial.
 *
 * Variables:
 *   $gi    - group index (int or '{{INDEX}}')
 *   $group - group data array
 */
defined( 'ABSPATH' ) || exit;

$group_name  = $group['group_name'] ?? '';
$ingredients = $group['ingredients'] ?? [];
?>
<div class="cp-group-item cp-repeater-item" data-index="<?php echo esc_attr( (string) $gi ); ?>">
	<div class="cp-repeater-item__header">
		<span class="cp-drag-handle">⠿</span>
		<span class="cp-repeater-item__badge">
			<?php echo esc_html( sprintf( __( 'Group %s', 'dev-chefpress' ), $gi === '{{INDEX}}' ? '#' : (int) $gi + 1 ) ); ?>
		</span>
		<span class="cp-repeater-item__preview-title">
			<?php echo esc_html( $group_name ?: __( '(No group name)', 'dev-chefpress' ) ); ?>
		</span>
		<div class="cp-repeater-item__actions">
			<button type="button" class="cp-icon-btn cp-btn-duplicate" title="<?php esc_attr_e( 'Duplicate', 'dev-chefpress' ); ?>">⧉</button>
			<button type="button" class="cp-icon-btn cp-btn-toggle" title="<?php esc_attr_e( 'Toggle', 'dev-chefpress' ); ?>">▼</button>
			<button type="button" class="cp-icon-btn cp-btn-remove" title="<?php esc_attr_e( 'Remove group', 'dev-chefpress' ); ?>">✕</button>
		</div>
	</div>
	<div class="cp-repeater-item__body">
		<div class="cp-field">
			<label class="cp-label"><?php esc_html_e( 'Group Name', 'dev-chefpress' ); ?></label>
			<input type="text"
				   name="_chefpress_groups[<?php echo esc_attr( (string) $gi ); ?>][group_name]"
				   value="<?php echo esc_attr( $group_name ); ?>"
				   class="cp-input cp-group-name-input"
				   placeholder="<?php esc_attr_e( 'e.g. For the Sauce, For the Dough…', 'dev-chefpress' ); ?>" />
		</div>

		<!-- Nested ingredients -->
		<div class="cp-ingredients-subheader">
			<span><?php esc_html_e( 'Ingredients in this group', 'dev-chefpress' ); ?></span>
		</div>
		<div class="cp-ingredients-list" data-group-index="<?php echo esc_attr( (string) $gi ); ?>">
			<?php if ( ! empty( $ingredients ) ) : ?>
				<?php foreach ( $ingredients as $ii => $ingredient ) : ?>
					<?php
					$ing_name     = $ingredient['ingredient_name'] ?? '';
					$ing_quantity = $ingredient['quantity'] ?? '';
					$ing_unit     = $ingredient['unit'] ?? '';
					?>
					<div class="cp-ingredient-item" data-ingredient-index="<?php echo esc_attr( (string) $ii ); ?>">
						<span class="cp-drag-handle cp-drag-handle--sm">⠿</span>
						<div class="cp-ingredient-fields">
							<input type="text"
								   name="_chefpress_groups[<?php echo esc_attr( (string) $gi ); ?>][ingredients][<?php echo esc_attr( (string) $ii ); ?>][ingredient_name]"
								   value="<?php echo esc_attr( $ing_name ); ?>"
								   class="cp-input cp-input--flex"
								   placeholder="<?php esc_attr_e( 'Ingredient name', 'dev-chefpress' ); ?>" />
							<input type="text"
								   name="_chefpress_groups[<?php echo esc_attr( (string) $gi ); ?>][ingredients][<?php echo esc_attr( (string) $ii ); ?>][quantity]"
								   value="<?php echo esc_attr( $ing_quantity ); ?>"
								   class="cp-input cp-input--qty"
								   placeholder="<?php esc_attr_e( 'Qty', 'dev-chefpress' ); ?>" />
							<input type="text"
								   name="_chefpress_groups[<?php echo esc_attr( (string) $gi ); ?>][ingredients][<?php echo esc_attr( (string) $ii ); ?>][unit]"
								   value="<?php echo esc_attr( $ing_unit ); ?>"
								   class="cp-input cp-input--unit"
								   placeholder="<?php esc_attr_e( 'Unit', 'dev-chefpress' ); ?>" />
						</div>
						<button type="button" class="cp-icon-btn cp-btn-remove-ingredient" title="<?php esc_attr_e( 'Remove', 'dev-chefpress' ); ?>">✕</button>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div><!-- /.cp-ingredients-list -->

		<button type="button" class="cp-btn cp-btn--ghost cp-btn--sm cp-add-ingredient" data-group-index="<?php echo esc_attr( (string) $gi ); ?>">
			＋ <?php esc_html_e( 'Add Ingredient', 'dev-chefpress' ); ?>
		</button>
	</div>
</div>
