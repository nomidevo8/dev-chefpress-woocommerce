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
$meal_prices = $settings['meal_prices'] ?? [];
$plan_discounts = $settings['plan_discounts'] ?? [];
$promo_codes = $settings['promo_codes'] ?? [];
?>

<div class="wrap chefpress-settings-wrap">
	<div class="chefpress-settings-header">
		<h1 class="chefpress-settings-title">
			<span class="chefpress-settings-title__brand">ChefPress</span>
			<span class="chefpress-settings-title__sep">—</span>
			<?php esc_html_e( 'Settings', 'dev-chefpress' ); ?>
		</h1>
		<?php if ( ! empty( $saved ) ) : ?>
			<div class="chefpress-notice chefpress-notice--success chefpress-notice--floating">
				<?php esc_html_e( 'Settings saved successfully.', 'dev-chefpress' ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="chefpress-settings-dashboard">
		<!-- Sidebar Navigation -->
		<div class="chefpress-settings-sidebar">
			<nav class="chefpress-nav">
				<div class="chefpress-nav__header">
					<h3><?php esc_html_e( 'Settings Menu', 'dev-chefpress' ); ?></h3>
				</div>
				<ul class="chefpress-nav__list">
					<li class="chefpress-nav__item">
						<a href="#nutrition" class="chefpress-nav__link chefpress-nav__link--active" data-section="nutrition">
							<span class="chefpress-nav__icon">📊</span>
							<span class="chefpress-nav__text"><?php esc_html_e( 'Nutrition Schema', 'dev-chefpress' ); ?></span>
						</a>
					</li>
					<li class="chefpress-nav__item">
						<a href="#presets" class="chefpress-nav__link" data-section="presets">
							<span class="chefpress-nav__icon">🏷️</span>
							<span class="chefpress-nav__text"><?php esc_html_e( 'Preset Lists', 'dev-chefpress' ); ?></span>
						</a>
					</li>
					<li class="chefpress-nav__item">
						<a href="#pricing" class="chefpress-nav__link" data-section="pricing">
							<span class="chefpress-nav__icon">💰</span>
							<span class="chefpress-nav__text"><?php esc_html_e( 'Pricing & Plans', 'dev-chefpress' ); ?></span>
						</a>
					</li>
					<li class="chefpress-nav__item">
						<a href="#appearance" class="chefpress-nav__link" data-section="appearance">
							<span class="chefpress-nav__icon">🎨</span>
							<span class="chefpress-nav__text"><?php esc_html_e( 'Appearance', 'dev-chefpress' ); ?></span>
						</a>
					</li>
					<li class="chefpress-nav__item">
						<a href="#system" class="chefpress-nav__link" data-section="system">
							<span class="chefpress-nav__icon">⚙️</span>
							<span class="chefpress-nav__text"><?php esc_html_e( 'System', 'dev-chefpress' ); ?></span>
						</a>
					</li>
				</ul>
			</nav>
		</div>

		<!-- Main Content Area -->
		<div class="chefpress-settings-content">
			<form method="post" action="<?php echo esc_url( \DevChefPress\Admin\SettingsPage::settings_url() ); ?>" class="chefpress-settings-form" id="chefpress-settings-form">
				<?php wp_nonce_field( 'chefpress_settings_save', 'chefpress_settings_nonce' ); ?>

				<!-- Nutrition Schema Section -->
				<div id="chefpress-section-nutrition" class="chefpress-section chefpress-section--active">
					<div class="chefpress-section__header">
						<h2 class="chefpress-section__title">
							<span class="chefpress-section__icon">📊</span>
							<?php esc_html_e( 'Nutrition Schema', 'dev-chefpress' ); ?>
						</h2>
						<p class="chefpress-section__description">
							<?php esc_html_e( 'Define nutrition rows for all recipe products. These rows appear on every recipe product page.', 'dev-chefpress' ); ?>
						</p>
					</div>
					<div class="chefpress-section__content">
						<div class="chefpress-card">
							<div class="chefpress-card__body">
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
								<div class="chefpress-card__actions">
									<button type="button" class="cp-btn cp-btn--secondary" id="chefpress-add-nutrition-row">
										<?php esc_html_e( '+ Add nutrition field', 'dev-chefpress' ); ?>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Preset Lists Section -->
				<div id="chefpress-section-presets" class="chefpress-section">
					<div class="chefpress-section__header">
						<h2 class="chefpress-section__title">
							<span class="chefpress-section__icon">🏷️</span>
							<?php esc_html_e( 'Preset Lists', 'dev-chefpress' ); ?>
						</h2>
						<p class="chefpress-section__description">
							<?php esc_html_e( 'Manage preset ingredients, allergens, and recipe labels for quick selection when creating recipes.', 'dev-chefpress' ); ?>
						</p>
					</div>
					<div class="chefpress-section__content">
						<div class="chefpress-grid chefpress-grid--3">
							<div class="chefpress-card">
								<div class="chefpress-card__header">
									<h3 class="chefpress-card__title">
										<span class="chefpress-card__icon chefpress-card__icon--ing"></span>
										<?php esc_html_e( 'Preset Ingredients', 'dev-chefpress' ); ?>
									</h3>
									<p class="chefpress-card__subtitle"><?php esc_html_e( 'Common ingredients for quick selection.', 'dev-chefpress' ); ?></p>
								</div>
								<div class="chefpress-card__body">
									<?php chefpress_render_preset_editor( 'preset_ingredients', $ingredients ); ?>
								</div>
							</div>

							<div class="chefpress-card">
								<div class="chefpress-card__header">
									<h3 class="chefpress-card__title">
										<span class="chefpress-card__icon chefpress-card__icon--all"></span>
										<?php esc_html_e( 'Preset Allergens', 'dev-chefpress' ); ?>
									</h3>
									<p class="chefpress-card__subtitle"><?php esc_html_e( 'Common allergens for recipe tagging.', 'dev-chefpress' ); ?></p>
								</div>
								<div class="chefpress-card__body">
									<?php chefpress_render_preset_editor( 'preset_allergens', $allergens_p ); ?>
								</div>
							</div>

							<div class="chefpress-card">
								<div class="chefpress-card__header">
									<h3 class="chefpress-card__title">
										<span class="chefpress-card__icon chefpress-card__icon--tag"></span>
										<?php esc_html_e( 'Recipe Labels', 'dev-chefpress' ); ?>
									</h3>
									<p class="chefpress-card__subtitle"><?php esc_html_e( 'Tags and labels for recipe categorization.', 'dev-chefpress' ); ?></p>
								</div>
								<div class="chefpress-card__body">
									<?php chefpress_render_preset_editor( 'preset_recipe_labels', $labels_p ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Pricing & Plans Section -->
				<div id="chefpress-section-pricing" class="chefpress-section">
					<div class="chefpress-section__header">
						<h2 class="chefpress-section__title">
							<span class="chefpress-section__icon">💰</span>
							<?php esc_html_e( 'Pricing & Plans', 'dev-chefpress' ); ?>
						</h2>
						<p class="chefpress-section__description">
							<?php esc_html_e( 'Configure meal prices and plan discount rates for the subscription system.', 'dev-chefpress' ); ?>
						</p>
					</div>
					<div class="chefpress-section__content">
						<div class="cp-field-grid cp-field-grid--2">
							<div class="chefpress-card">
								<div class="chefpress-card__header">
									<h3 class="chefpress-card__title">
										<span class="chefpress-card__icon">🍽️</span>
										<?php esc_html_e( 'Meal Pricing', 'dev-chefpress' ); ?>
									</h3>
									<p class="chefpress-card__subtitle"><?php esc_html_e( 'Set base prices per meal type.', 'dev-chefpress' ); ?></p>
								</div>
								<div class="chefpress-card__body">
									<div class="chefpress-table-scroll">
										<table class="chefpress-schema-table" id="chefpress-meal-prices-table">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Meal Type', 'dev-chefpress' ); ?></th>
													<th><?php esc_html_e( 'Price (per unit)', 'dev-chefpress' ); ?></th>
													<th><?php esc_html_e( 'Currency', 'dev-chefpress' ); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $meal_prices as $meal => $price ) : ?>
													<tr>
														<td>
															<strong><?php echo esc_html( $meal ); ?></strong>
														</td>
														<td>
															<input type="number"
																name="chefpress_settings[meal_prices][<?php echo esc_attr( $meal ); ?>]"
																value="<?php echo esc_attr( number_format( (float) $price, 2, '.', '' ) ); ?>"
																step="0.01"
																min="0"
																class="cp-input cp-input--sm"
																required />
														</td>
														<td style="text-align: center; color: var(--text_muted); font-size: 0.875rem;">AED</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
							</div>

							<div class="chefpress-card">
								<div class="chefpress-card__header">
									<h3 class="chefpress-card__title">
										<span class="chefpress-card__icon">🏷️</span>
										<?php esc_html_e( 'Plan Discounts', 'dev-chefpress' ); ?>
									</h3>
									<p class="chefpress-card__subtitle"><?php esc_html_e( 'Discount rates for different plan durations.', 'dev-chefpress' ); ?></p>
								</div>
								<div class="chefpress-card__body">
									<div class="chefpress-table-scroll">
										<table class="chefpress-schema-table" id="chefpress-plan-discounts-table">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Plan Duration', 'dev-chefpress' ); ?></th>
													<th><?php esc_html_e( 'Discount (%)', 'dev-chefpress' ); ?></th>
													<th><?php esc_html_e( 'Description', 'dev-chefpress' ); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php
												$discount_descs = [
													'1 Week' => 'No discount for weekly plans',
													'1 Month' => 'Discount for monthly commitment',
													'3 Months' => 'Discount for quarterly commitment',
													'6 Months' => 'Discount for half-year commitment',
												];
												foreach ( $plan_discounts as $plan => $discount ) : ?>
													<tr>
														<td>
															<strong><?php echo esc_html( $plan ); ?></strong>
														</td>
														<td>
															<input type="number"
																name="chefpress_settings[plan_discounts][<?php echo esc_attr( $plan ); ?>]"
																value="<?php echo esc_attr( number_format( (float) $discount, 2, '.', '' ) ); ?>"
																step="0.01"
																min="0"
																max="100"
																class="cp-input cp-input--sm"
																required />
														</td>
														<td style="font-size: 0.875rem; color: var(--text_muted);">
															<?php echo esc_html( $discount_descs[ $plan ] ?? '' ); ?>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
								</div>
							</div>
							</div>
						</div>
					</div>

					<div class="chefpress-card chefpress-card--full-width">
						<div class="chefpress-card__header">
							<h3 class="chefpress-card__title">
								<span class="chefpress-card__icon">🎫</span>
								<?php esc_html_e( 'Promo Codes', 'dev-chefpress' ); ?>
							</h3>
							<p class="chefpress-card__subtitle"><?php esc_html_e( 'Create, update, and remove promo codes with discount rates.', 'dev-chefpress' ); ?></p>
						</div>
						<div class="chefpress-card__body">
							<div class="chefpress-table-scroll">
								<table class="chefpress-schema-table" id="chefpress-promo-codes-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Promo Code', 'dev-chefpress' ); ?></th>
											<th><?php esc_html_e( 'Discount (%)', 'dev-chefpress' ); ?></th>
											<th></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $promo_codes as $index => $discount ) : ?>
											<tr class="chefpress-promo-row">
												<td>
													<input type="text"
													name="chefpress_settings[promo_codes][<?php echo esc_attr( $index ); ?>][code]"
													value="<?php echo esc_attr( strtoupper( (string) $index ) ); ?>"
													class="cp-input cp-input--sm"
													required />
											</td>
											<td>
												<input type="number"
													name="chefpress_settings[promo_codes][<?php echo esc_attr( $index ); ?>][discount]"
													value="<?php echo esc_attr( number_format( (float) $discount, 2, '.', '' ) ); ?>"
													step="0.01"
													min="0"
													max="100"
													class="cp-input cp-input--sm"
													required />
											</td>
											<td>
												<button type="button" class="cp-btn cp-btn--ghost cp-btn--sm chefpress-remove-promo-row" aria-label="<?php esc_attr_e( 'Remove promo row', 'dev-chefpress' ); ?>">×</button>
											</td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
							<div class="chefpress-card__actions">
								<button type="button" class="cp-btn cp-btn--secondary" id="chefpress-add-promo-row">
									<?php esc_html_e( '+ Add promo code', 'dev-chefpress' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Appearance Section -->
				<div id="chefpress-section-appearance" class="chefpress-section">
					<div class="chefpress-section__header">
						<h2 class="chefpress-section__title">
							<span class="chefpress-section__icon">🎨</span>
							<?php esc_html_e( 'Appearance', 'dev-chefpress' ); ?>
						</h2>
						<p class="chefpress-section__description">
							<?php esc_html_e( 'Customize the visual appearance and color scheme of your recipe pages.', 'dev-chefpress' ); ?>
						</p>
					</div>
					<div class="chefpress-section__content">
						<div class="chefpress-card">
							<div class="chefpress-card__header">
								<h3 class="chefpress-card__title">
									<span class="chefpress-card__icon">🎨</span>
									<?php esc_html_e( 'Theme Colors', 'dev-chefpress' ); ?>
								</h3>
								<p class="chefpress-card__subtitle"><?php esc_html_e( 'Customize color variables applied to recipe pages.', 'dev-chefpress' ); ?></p>
							</div>
							<div class="chefpress-card__body">
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
						</div>
					</div>
				</div>

				<!-- System Section -->
				<div id="chefpress-section-system" class="chefpress-section">
					<div class="chefpress-section__header">
						<h2 class="chefpress-section__title">
							<span class="chefpress-section__icon">⚙️</span>
							<?php esc_html_e( 'System Configuration', 'dev-chefpress' ); ?>
						</h2>
						<p class="chefpress-section__description">
							<?php esc_html_e( 'Configure system-wide settings and preferences.', 'dev-chefpress' ); ?>
						</p>
					</div>
					<div class="chefpress-section__content">
						<div class="chefpress-card">
							<div class="chefpress-card__header">
								<h3 class="chefpress-card__title">
									<span class="chefpress-card__icon">📅</span>
									<?php esc_html_e( 'Weekly Menu System', 'dev-chefpress' ); ?>
								</h3>
								<p class="chefpress-card__subtitle"><?php esc_html_e( 'Set the start date for the weekly menu rotation system.', 'dev-chefpress' ); ?></p>
							</div>
							<div class="chefpress-card__body">
								<div class="cp-field">
									<label class="cp-label"><?php esc_html_e( 'Weekly Start Date', 'dev-chefpress' ); ?></label>
									<p class="cp-field__help"><?php esc_html_e( 'This date defines the beginning of Week 1. All subsequent weeks are calculated from this date.', 'dev-chefpress' ); ?></p>
									<input type="date"
										name="chefpress_settings[weekly_start_date]"
										value="<?php echo esc_attr( $settings['weekly_start_date'] ?? '' ); ?>"
										class="cp-input"
										required />
									<p class="cp-field__help-small"><?php esc_html_e( 'Current week will automatically be highlighted based on this date.', 'dev-chefpress' ); ?></p>
								</div>
							</div>
						</div>
						<div class="chefpress-card">
							<div class="chefpress-card__header">
								<h3 class="chefpress-card__title">
									<span class="chefpress-card__icon">🧮</span>
									<?php esc_html_e( 'Calorie Calculator', 'dev-chefpress' ); ?>
								</h3>
								<p class="chefpress-card__subtitle"><?php esc_html_e( 'Select the BMR formula for calorie calculations.', 'dev-chefpress' ); ?></p>
							</div>
							<div class="chefpress-card__body">
								<div class="cp-field">
									<label class="cp-label"><?php esc_html_e( 'BMR Formula', 'dev-chefpress' ); ?></label>
									<p class="cp-field__help"><?php esc_html_e( 'Choose the formula used to calculate Basal Metabolic Rate.', 'dev-chefpress' ); ?></p>
									<select name="chefpress_settings[bmr_formula]" class="cp-input">
										<option value="Mifflin-St Jeor" <?php selected( $settings['bmr_formula'] ?? 'Mifflin-St Jeor', 'Mifflin-St Jeor' ); ?>><?php esc_html_e( 'Mifflin-St Jeor', 'dev-chefpress' ); ?></option>
										<option value="Revised Harris-Benedict" <?php selected( $settings['bmr_formula'] ?? 'Mifflin-St Jeor', 'Revised Harris-Benedict' ); ?>><?php esc_html_e( 'Revised Harris-Benedict', 'dev-chefpress' ); ?></option>
										<option value="Katch-McArdle" <?php selected( $settings['bmr_formula'] ?? 'Mifflin-St Jeor', 'Katch-McArdle' ); ?>><?php esc_html_e( 'Katch-McArdle', 'dev-chefpress' ); ?></option>
									</select>
									<p class="cp-field__help-small"><?php esc_html_e( 'Katch-McArdle requires body fat percentage input.', 'dev-chefpress' ); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Save Button -->
				<div class="chefpress-settings-actions">
					<button type="submit" class="cp-btn cp-btn--primary cp-btn--lg chefpress-save-btn">
						<?php esc_html_e( 'Save All Settings', 'dev-chefpress' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
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

<script type="text/template" id="tmpl-chefpress-promo-code-row">
	<tr class="chefpress-promo-row">
		<td>
			<input type="text"
				name="chefpress_settings[promo_codes][{{IDX}}][code]"
				value=""
				class="cp-input cp-input--sm"
				required />
		</td>
		<td>
			<input type="number"
				name="chefpress_settings[promo_codes][{{IDX}}][discount]"
				value="0"
				step="0.01"
				min="0"
				max="100"
				class="cp-input cp-input--sm"
				required />
		</td>
		<td>
			<button type="button" class="cp-btn cp-btn--ghost cp-btn--sm chefpress-remove-promo-row" aria-label="<?php esc_attr_e( 'Remove promo row', 'dev-chefpress' ); ?>">×</button>
		</td>
	</tr>
</script>
