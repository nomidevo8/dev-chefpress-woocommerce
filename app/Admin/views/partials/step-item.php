<?php
/**
 * Step item partial.
 *
 * Variables available:
 *   $si   - step index (int or '{{INDEX}}' for JS template)
 *   $step - step data array
 */
defined( 'ABSPATH' ) || exit;

$step_number      = $step['step_number'] ?? '';
$step_title       = $step['step_title'] ?? '';
$step_description = $step['step_description'] ?? '';
$step_image_id    = $step['step_image_id'] ?? '';
$step_image_url   = $step_image_id ? wp_get_attachment_image_url( (int) $step_image_id, 'thumbnail' ) : '';
$step_tip         = $step['step_tip'] ?? '';
?>
<div class="cp-step-item cp-repeater-item" data-index="<?php echo esc_attr( (string) $si ); ?>">
	<div class="cp-repeater-item__header">
		<span class="cp-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'dev-chefpress' ); ?>">⠿</span>
		<span class="cp-repeater-item__badge">
			<?php echo esc_html( sprintf( __( 'Step %s', 'dev-chefpress' ), $si === '{{INDEX}}' ? '#' : (int) $si + 1 ) ); ?>
		</span>
		<span class="cp-repeater-item__preview-title">
			<?php echo esc_html( $step_title ?: __( '(No title)', 'dev-chefpress' ) ); ?>
		</span>
		<div class="cp-repeater-item__actions">
			<button type="button" class="cp-icon-btn cp-btn-duplicate" title="<?php esc_attr_e( 'Duplicate', 'dev-chefpress' ); ?>">⧉</button>
			<button type="button" class="cp-icon-btn cp-btn-toggle" title="<?php esc_attr_e( 'Toggle', 'dev-chefpress' ); ?>">▼</button>
			<button type="button" class="cp-icon-btn cp-btn-remove" title="<?php esc_attr_e( 'Remove', 'dev-chefpress' ); ?>">✕</button>
		</div>
	</div>
	<div class="cp-repeater-item__body">
		<div class="cp-field-grid cp-field-grid--2">
			<div class="cp-field">
				<label class="cp-label"><?php esc_html_e( 'Step Number', 'dev-chefpress' ); ?></label>
				<input type="number"
					   name="_chefpress_steps[<?php echo esc_attr( (string) $si ); ?>][step_number]"
					   value="<?php echo esc_attr( $step_number ); ?>"
					   class="cp-input"
					   min="1"
					   placeholder="1" />
			</div>
			<div class="cp-field">
				<label class="cp-label"><?php esc_html_e( 'Step Title', 'dev-chefpress' ); ?></label>
				<input type="text"
					   name="_chefpress_steps[<?php echo esc_attr( (string) $si ); ?>][step_title]"
					   value="<?php echo esc_attr( $step_title ); ?>"
					   class="cp-input cp-step-title-input"
					   placeholder="<?php esc_attr_e( 'e.g. Prepare the vegetables', 'dev-chefpress' ); ?>" />
			</div>
		</div>
		<div class="cp-field cp-field--step-description">
			<label class="cp-label"><?php esc_html_e( 'Description', 'dev-chefpress' ); ?></label>
			<?php
			$is_tpl = ( '{{INDEX}}' === (string) $si );
			if ( $is_tpl ) :
				?>
				<textarea id="chefpress_step_desc_{{INDEX}}"
						  name="_chefpress_steps[{{INDEX}}][step_description]"
						  class="widefat cp-textarea cp-step-description-field"
						  rows="6"
						  placeholder="<?php esc_attr_e( 'Describe this cooking step in detail…', 'dev-chefpress' ); ?>"></textarea>
				<?php
			else :
				wp_editor(
					(string) $step_description,
					'chefpress_step_desc_' . (int) $si,
					[
						'textarea_name' => '_chefpress_steps[' . (int) $si . '][step_description]',
						'textarea_rows' => 6,
						'teeny'         => true,
						'media_buttons' => false,
						'quicktags'     => true,
						'editor_class'  => 'cp-step-description-field',
					]
				);
			endif;
			?>
		</div>
		<div class="cp-field-grid cp-field-grid--2">
			<div class="cp-field">
				<label class="cp-label"><?php esc_html_e( 'Step Image (optional)', 'dev-chefpress' ); ?></label>
				<div class="cp-media-field">
					<div class="cp-media-preview <?php echo $step_image_url ? 'has-image' : ''; ?>">
						<?php if ( $step_image_url ) : ?>
							<img src="<?php echo esc_url( $step_image_url ); ?>" alt="" />
						<?php endif; ?>
					</div>
					<input type="hidden"
						   name="_chefpress_steps[<?php echo esc_attr( (string) $si ); ?>][step_image_id]"
						   value="<?php echo esc_attr( $step_image_id ); ?>"
						   class="cp-media-id" />
					<div class="cp-media-buttons">
						<button type="button" class="cp-btn cp-btn--secondary cp-btn-media-upload cp-btn--sm">
							<?php esc_html_e( 'Select Image', 'dev-chefpress' ); ?>
						</button>
						<button type="button" class="cp-btn cp-btn--ghost cp-btn-media-remove cp-btn--sm <?php echo ! $step_image_url ? 'cp-hidden' : ''; ?>">
							<?php esc_html_e( 'Remove', 'dev-chefpress' ); ?>
						</button>
					</div>
				</div>
			</div>
			<div class="cp-field">
				<label class="cp-label"><?php esc_html_e( 'Chef Tip (optional)', 'dev-chefpress' ); ?></label>
				<textarea name="_chefpress_steps[<?php echo esc_attr( (string) $si ); ?>][step_tip]"
						  class="cp-textarea"
						  rows="3"
						  placeholder="<?php esc_attr_e( 'Pro tip for this step…', 'dev-chefpress' ); ?>"><?php echo esc_textarea( $step_tip ); ?></textarea>
			</div>
		</div>
	</div>
</div>
