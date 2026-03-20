<?php
/**
 * ChefPress global settings.
 *
 * @var array<string, mixed> $settings
 * @var bool                 $saved
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'chefpress_render_preset_editor' ) ) {
	/**
	 * Preset chip editor (ingredients / allergens / labels).
	 *
	 * @param string   $field Option sub-key under chefpress_settings.
	 * @param string[] $items Existing values.
	 */
	function chefpress_render_preset_editor( string $field, array $items ): void {
		?>
		<div class="chefpress-preset-editor" data-field="<?php echo esc_attr( $field ); ?>">
			<div class="chefpress-preset-chips" role="list">
				<?php foreach ( $items as $item ) : ?>
					<span class="chefpress-preset-chip" role="listitem">
						<?php echo esc_html( $item ); ?>
						<button type="button" class="chefpress-preset-chip__x" aria-label="<?php esc_attr_e( 'Remove', 'dev-chefpress' ); ?>">×</button>
						<input type="hidden" name="chefpress_settings[<?php echo esc_attr( $field ); ?>][]" value="<?php echo esc_attr( $item ); ?>" />
					</span>
				<?php endforeach; ?>
			</div>
			<div class="chefpress-preset-add">
				<input type="text" class="cp-input chefpress-preset-input" placeholder="<?php esc_attr_e( 'Add item — Enter to add', 'dev-chefpress' ); ?>" autocomplete="off" />
				<button type="button" class="cp-btn cp-btn--secondary cp-btn--sm chefpress-preset-add-btn">
					<?php esc_html_e( 'Add', 'dev-chefpress' ); ?>
				</button>
			</div>
		</div>
		<?php
	}
}

$nutrition   = $settings['nutrition_fields'];
$ingredients = $settings['preset_ingredients'];
$allergens_p = $settings['preset_allergens'];
$labels_p    = $settings['preset_recipe_labels'];
?>
<div class="wrap chefpress-settings-wrap">
	<h1 class="chefpress-settings-title">
		<span class="chefpress-settings-title__brand">ChefPress</span>
		<span class="chefpress-settings-title__sep">—</span>
		<?php esc_html_e( 'Settings', 'dev-chefpress' ); ?>
	</h1>
	<p class="chefpress-settings-lead">
		<?php esc_html_e( 'Define nutrition rows for all products, and preset ingredients, allergens, and recipe labels for quick selection on each product.', 'dev-chefpress' ); ?>
	</p>

	<?php if ( ! empty( $saved ) ) : ?>
		<div class="chefpress-notice chefpress-notice--success">
			<?php esc_html_e( 'Settings saved.', 'dev-chefpress' ); ?>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( \DevChefPress\Admin\SettingsPage::settings_url() ); ?>" class="chefpress-settings-form" id="chefpress-settings-form">
		<?php wp_nonce_field( 'chefpress_settings_save', 'chefpress_settings_nonce' ); ?>

		<div class="chefpress-settings-grid">
			<section class="cp-card cp-card--settings">
				<div class="cp-card__header-static">
					<span class="cp-card__icon cp-card__icon--nutr"></span>
					<div>
						<h2 class="cp-card__title"><?php esc_html_e( 'Nutrition table (global)', 'dev-chefpress' ); ?></h2>
						<p class="cp-card__subtitle"><?php esc_html_e( 'These rows appear on every recipe product. Use a unique key (letters, numbers, underscores).', 'dev-chefpress' ); ?></p>
					</div>
				</div>
				<div class="cp-card__body">
					<div class="chefpress-table-scroll">
						<table class="chefpress-schema-table" id="chefpress-nutrition-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Key (ID)', 'dev-chefpress' ); ?></th>
									<th><?php esc_html_e( 'Label', 'dev-chefpress' ); ?></th>
									<th><?php esc_html_e( 'Unit', 'dev-chefpress' ); ?></th>
									<th class="chefpress-col-actions"></th>
								</tr>
							</thead>
							<tbody id="chefpress-nutrition-rows">
								<?php foreach ( $nutrition as $i => $row ) : ?>
									<tr class="chefpress-nutr-row">
										<td>
											<input type="text"
												name="chefpress_settings[nutrition][<?php echo esc_attr( (string) $i ); ?>][key]"
												value="<?php echo esc_attr( $row['key'] ); ?>"
												class="cp-input cp-input--sm"
												pattern="[a-z0-9_\-]+"
												required />
										</td>
										<td>
											<input type="text"
												name="chefpress_settings[nutrition][<?php echo esc_attr( (string) $i ); ?>][label]"
												value="<?php echo esc_attr( $row['label'] ); ?>"
												class="cp-input cp-input--sm"
												required />
										</td>
										<td>
											<input type="text"
												name="chefpress_settings[nutrition][<?php echo esc_attr( (string) $i ); ?>][unit]"
												value="<?php echo esc_attr( $row['unit'] ); ?>"
												class="cp-input cp-input--sm"
												placeholder="<?php esc_attr_e( 'e.g. g', 'dev-chefpress' ); ?>" />
										</td>
										<td>
											<button type="button" class="cp-btn cp-btn--ghost cp-btn--sm chefpress-remove-row" aria-label="<?php esc_attr_e( 'Remove row', 'dev-chefpress' ); ?>">×</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<button type="button" class="cp-btn cp-btn--secondary" id="chefpress-add-nutrition-row">
						<?php esc_html_e( '+ Add nutrition field', 'dev-chefpress' ); ?>
					</button>
				</div>
			</section>

			<div class="chefpress-settings-stack">
				<section class="cp-card cp-card--settings">
					<div class="cp-card__header-static">
						<span class="cp-card__icon cp-card__icon--ing"></span>
						<div>
							<h2 class="cp-card__title"><?php esc_html_e( 'Preset ingredients', 'dev-chefpress' ); ?></h2>
							<p class="cp-card__subtitle"><?php esc_html_e( 'Shown on products — click to add an ingredient line.', 'dev-chefpress' ); ?></p>
						</div>
					</div>
					<div class="cp-card__body">
						<?php chefpress_render_preset_editor( 'preset_ingredients', $ingredients ); ?>
					</div>
				</section>

				<section class="cp-card cp-card--settings">
					<div class="cp-card__header-static">
						<span class="cp-card__icon cp-card__icon--all"></span>
						<div>
							<h2 class="cp-card__title"><?php esc_html_e( 'Preset allergens', 'dev-chefpress' ); ?></h2>
							<p class="cp-card__subtitle"><?php esc_html_e( 'Quick toggles for allergen tags on products.', 'dev-chefpress' ); ?></p>
						</div>
					</div>
					<div class="cp-card__body">
						<?php chefpress_render_preset_editor( 'preset_allergens', $allergens_p ); ?>
					</div>
				</section>

				<section class="cp-card cp-card--settings">
					<div class="cp-card__header-static">
						<span class="cp-card__icon cp-card__icon--tag"></span>
						<div>
							<h2 class="cp-card__title"><?php esc_html_e( 'Preset recipe labels', 'dev-chefpress' ); ?></h2>
							<p class="cp-card__subtitle"><?php esc_html_e( 'Quick toggles for recipe tags / labels.', 'dev-chefpress' ); ?></p>
						</div>
					</div>
					<div class="cp-card__body">
						<?php chefpress_render_preset_editor( 'preset_recipe_labels', $labels_p ); ?>
					</div>
				</section>
			</div>
		</div>

		<section class="cp-card cp-card--settings">
			<div class="cp-card__header-static">
				<span class="cp-card__icon cp-card__icon--color"></span>
				<div>
					<h2 class="cp-card__title"><?php esc_html_e( 'Frontend theme colors', 'dev-chefpress' ); ?></h2>
					<p class="cp-card__subtitle"><?php esc_html_e( 'Customize the color variables applied to recipe pages.', 'dev-chefpress' ); ?></p>
				</div>
			</div>
			<div class="cp-card__body">
				<div class="cp-field-grid cp-field-grid--2">
					<?php
					$colors = $settings['theme_colors'];
					$colorFields = [
						'brand' => __( 'Brand color', 'dev-chefpress' ),
						'brand_light' => __( 'Brand light', 'dev-chefpress' ),
						'text_main' => __( 'Text main', 'dev-chefpress' ),
						'text_muted' => __( 'Text muted', 'dev-chefpress' ),
						'bg_light' => __( 'Background light', 'dev-chefpress' ),
						'border' => __( 'Border color', 'dev-chefpress' ),
						'white' => __( 'White color', 'dev-chefpress' ),
					];
					foreach ( $colorFields as $key => $label ) : ?>
						<div class="cp-field">
							<label class="cp-label"><?php echo esc_html( $label ); ?></label>
							<input type="text"
								name="chefpress_settings[theme_colors][<?php echo esc_attr( $key ); ?>]"
								value="<?php echo esc_attr( $colors[ $key ] ?? '' ); ?>"
								class="cp-input cp-input--color"
								placeholder="#000000" />
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<p class="submit chefpress-submit-wrap">
			<button type="submit" class="cp-btn cp-btn--primary cp-btn--lg">
				<?php esc_html_e( 'Save settings', 'dev-chefpress' ); ?>
			</button>
		</p>
	</form>
</div>

<script type="text/template" id="tmpl-chefpress-nutrition-row">
	<tr class="chefpress-nutr-row">
		<td>
			<input type="text" name="chefpress_settings[nutrition][{{IDX}}][key]" value="" class="cp-input cp-input--sm" pattern="[a-z0-9_\-]+" required />
		</td>
		<td>
			<input type="text" name="chefpress_settings[nutrition][{{IDX}}][label]" value="" class="cp-input cp-input--sm" required />
		</td>
		<td>
			<input type="text" name="chefpress_settings[nutrition][{{IDX}}][unit]" value="" class="cp-input cp-input--sm" placeholder="<?php echo esc_attr__( 'e.g. g', 'dev-chefpress' ); ?>" />
		</td>
		<td>
			<button type="button" class="cp-btn cp-btn--ghost cp-btn--sm chefpress-remove-row" aria-label="<?php echo esc_attr__( 'Remove row', 'dev-chefpress' ); ?>">×</button>
		</td>
	</tr>
</script>
