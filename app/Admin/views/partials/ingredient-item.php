<?php
/**
 * Single ingredient item — used as JS template.
 * Placeholders: {{GROUP_INDEX}}, {{ING_INDEX}}
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="cp-ingredient-item" data-ingredient-index="{{ING_INDEX}}">
	<span class="cp-drag-handle cp-drag-handle--sm">⠿</span>
	<div class="cp-ingredient-fields">
		<input type="text"
			   name="_chefpress_groups[{{GROUP_INDEX}}][ingredients][{{ING_INDEX}}][ingredient_name]"
			   value=""
			   class="cp-input cp-input--flex"
			   placeholder="<?php esc_attr_e( 'Ingredient name', 'dev-chefpress' ); ?>" />
		<input type="text"
			   name="_chefpress_groups[{{GROUP_INDEX}}][ingredients][{{ING_INDEX}}][quantity]"
			   value=""
			   class="cp-input cp-input--qty"
			   placeholder="<?php esc_attr_e( 'Qty', 'dev-chefpress' ); ?>" />
		<input type="text"
			   name="_chefpress_groups[{{GROUP_INDEX}}][ingredients][{{ING_INDEX}}][unit]"
			   value=""
			   class="cp-input cp-input--unit"
			   placeholder="<?php esc_attr_e( 'Unit', 'dev-chefpress' ); ?>" />
	</div>
	<button type="button" class="cp-icon-btn cp-btn-remove-ingredient" title="<?php esc_attr_e( 'Remove', 'dev-chefpress' ); ?>">✕</button>
</div>
