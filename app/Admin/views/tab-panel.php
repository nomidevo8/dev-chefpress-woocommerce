<?php
/**
 * ChefPress Recipe Builder — WooCommerce Product Data Panel
 *
 * @var \DevChefPress\Models\Recipe $recipe
 */
defined( 'ABSPATH' ) || exit;

$hero       = $recipe->get_hero();
$nutrition  = $recipe->get_nutrition();
$tags       = $recipe->get_tags();
$before     = $recipe->get_before_start();
$steps      = $recipe->get_steps();
$groups     = $recipe->get_ingredients();
$allergens  = $recipe->get_allergens();
$nutr_table = $recipe->get_nutrition_table();
?>
<div id="chefpress_recipe_data" class="panel woocommerce_options_panel chefpress-panel">

	<?php wp_nonce_field( 'dev_chefpress_save', '_chefpress_nonce' ); ?>

	<!-- ╔══════════════════════════════════════╗ -->
	<!-- ║         SUB-NAVIGATION TABS          ║ -->
	<!-- ╚══════════════════════════════════════╝ -->
	<div class="cp-subnav">
		<button type="button" class="cp-subnav__btn active" data-tab="cp-tab-overview">
			<span class="cp-subnav__icon"></span> <?php esc_html_e( 'Overview', 'dev-chefpress' ); ?>
		</button>
		<button type="button" class="cp-subnav__btn" data-tab="cp-tab-ingredients">
			<span class="cp-subnav__icon"></span> <?php esc_html_e( 'Ingredients', 'dev-chefpress' ); ?>
		</button>
		<button type="button" class="cp-subnav__btn" data-tab="cp-tab-instructions">
			<span class="cp-subnav__icon"></span> <?php esc_html_e( 'Instructions', 'dev-chefpress' ); ?>
		</button>
		<button type="button" class="cp-subnav__btn" data-tab="cp-tab-nutrition">
			<span class="cp-subnav__icon"></span> <?php esc_html_e( 'Nutrition', 'dev-chefpress' ); ?>
		</button>
		<button type="button" class="cp-subnav__btn" data-tab="cp-tab-allergens">
			<span class="cp-subnav__icon"></span> <?php esc_html_e( 'Allergens', 'dev-chefpress' ); ?>
		</button>
	</div>

	<!-- ╔══════════════════════════════════════╗ -->
	<!-- ║          TAB: OVERVIEW               ║ -->
	<!-- ╚══════════════════════════════════════╝ -->
	<div id="cp-tab-overview" class="cp-tab-content active">
		<div class="cp-tab-inner">

			<!-- Hero Section Card -->
			<div class="cp-card">
				<div class="cp-card__header" data-toggle="hero-body">
					<div class="cp-card__header-left">
						<span class="cp-card__icon"></span>
						<h3 class="cp-card__title"><?php esc_html_e( 'Hero Section', 'dev-chefpress' ); ?></h3>
					</div>
					<span class="cp-card__arrow">▼</span>
				</div>
				<div class="cp-card__body" id="hero-body">
					<div class="cp-field-grid cp-field-grid--2">
						<div class="cp-field">
							<label class="cp-label"><?php esc_html_e( 'Subtitle', 'dev-chefpress' ); ?></label>
							<input type="text"
								   name="_chefpress_subtitle"
								   value="<?php echo esc_attr( $hero['subtitle'] ?? '' ); ?>"
								   class="cp-input"
								   placeholder="<?php esc_attr_e( 'e.g. A quick and delicious weeknight dinner', 'dev-chefpress' ); ?>" />
						</div>
						<div class="cp-field">
							<label class="cp-label"><?php esc_html_e( 'Cooking Time', 'dev-chefpress' ); ?></label>
							<input type="text"
								   name="_chefpress_cooking_time"
								   value="<?php echo esc_attr( $hero['cooking_time'] ?? '' ); ?>"
								   class="cp-input"
								   placeholder="<?php esc_attr_e( 'e.g. 30 mins', 'dev-chefpress' ); ?>" />
						</div>
						<div class="cp-field">
							<label class="cp-label"><?php esc_html_e( 'Reviews Count', 'dev-chefpress' ); ?></label>
							<input type="number"
								   name="_chefpress_reviews_count"
								   value="<?php echo esc_attr( $hero['reviews_count'] ?? '' ); ?>"
								   class="cp-input"
								   min="0"
								   placeholder="0" />
						</div>
						<div class="cp-field">
							<label class="cp-label">
								<?php esc_html_e( 'Rating (0–5)', 'dev-chefpress' ); ?>
							</label>
							<div class="cp-star-rating-wrap">
								<input type="number"
									   name="_chefpress_rating"
									   value="<?php echo esc_attr( $hero['rating'] ?? '' ); ?>"
									   class="cp-input cp-rating-input"
									   min="0" max="5" step="0.1"
									   placeholder="4.5" />
								<div class="cp-star-preview" data-rating="<?php echo esc_attr( $hero['rating'] ?? '0' ); ?>"></div>
							</div>
						</div>
					</div>
				</div>
			</div><!-- /.cp-card Hero -->

			<!-- Tags Card -->
			<div class="cp-card">
				<div class="cp-card__header" data-toggle="tags-body">
					<div class="cp-card__header-left">
						<span class="cp-card__icon"></span>
						<h3 class="cp-card__title"><?php esc_html_e( 'Recipe Tags / Labels', 'dev-chefpress' ); ?></h3>
					</div>
					<span class="cp-card__arrow">▼</span>
				</div>
				<div class="cp-card__body" id="tags-body">
					<p class="cp-hint"><?php esc_html_e( 'Add labels like "Low Carb", "Vegan", "Gluten Free" etc. Press Enter or comma to add.', 'dev-chefpress' ); ?></p>
					<div class="cp-tags-wrap">
						<div class="cp-tags-list" id="cp-tags-list">
							<?php foreach ( $tags as $tag ) : ?>
								<span class="cp-tag">
									<?php echo esc_html( $tag ); ?>
									<button type="button" class="cp-tag__remove" aria-label="<?php esc_attr_e( 'Remove tag', 'dev-chefpress' ); ?>">×</button>
									<input type="hidden" name="_chefpress_tags[]" value="<?php echo esc_attr( $tag ); ?>" />
								</span>
							<?php endforeach; ?>
						</div>
						<input type="text" id="cp-tag-input" class="cp-input cp-tag-input"
							   placeholder="<?php esc_attr_e( 'Type a tag and press Enter…', 'dev-chefpress' ); ?>" />
					</div>
				</div>
			</div><!-- /.cp-card Tags -->

			<!-- Before You Start Card -->
			<div class="cp-card">
				<div class="cp-card__header" data-toggle="before-body">
					<div class="cp-card__header-left">
						<span class="cp-card__icon"></span>
						<h3 class="cp-card__title"><?php esc_html_e( 'Before You Start', 'dev-chefpress' ); ?></h3>
					</div>
					<span class="cp-card__arrow">▼</span>
				</div>
				<div class="cp-card__body" id="before-body">
					<div class="cp-field">
						<label class="cp-label"><?php esc_html_e( 'Notes / Tips before cooking', 'dev-chefpress' ); ?></label>
						<textarea name="_chefpress_before_start"
								  class="cp-textarea"
								  rows="5"
								  placeholder="<?php esc_attr_e( 'e.g. Make sure all ingredients are at room temperature before starting…', 'dev-chefpress' ); ?>"><?php echo esc_textarea( $before ); ?></textarea>
					</div>
				</div>
			</div><!-- /.cp-card Before Start -->

		</div>
	</div><!-- /#cp-tab-overview -->

	<!-- ╔══════════════════════════════════════╗ -->
	<!-- ║        TAB: INGREDIENTS              ║ -->
	<!-- ╚══════════════════════════════════════╝ -->
	<div id="cp-tab-ingredients" class="cp-tab-content">
		<div class="cp-tab-inner">
			<div class="cp-card">
				<div class="cp-card__header-static">
					<span class="cp-card__icon"></span>
					<h3 class="cp-card__title"><?php esc_html_e( 'Ingredient Groups', 'dev-chefpress' ); ?></h3>
					<p class="cp-card__subtitle"><?php esc_html_e( 'Organise ingredients into groups (e.g. "For the sauce", "For the dough"). Drag to reorder.', 'dev-chefpress' ); ?></p>
				</div>
				<div class="cp-card__body">
					<div id="cp-groups-list" class="cp-groups-list">
						<?php if ( ! empty( $groups ) ) : ?>
							<?php foreach ( $groups as $gi => $group ) : ?>
								<?php include __DIR__ . '/partials/group-item.php'; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</div><!-- /#cp-groups-list -->

					<div class="cp-add-actions">
						<button type="button" id="cp-add-group" class="cp-btn cp-btn--primary">
							<span>＋</span> <?php esc_html_e( 'Add Ingredient Group', 'dev-chefpress' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div><!-- /#cp-tab-ingredients -->

	<!-- ╔══════════════════════════════════════╗ -->
	<!-- ║        TAB: INSTRUCTIONS             ║ -->
	<!-- ╚══════════════════════════════════════╝ -->
	<div id="cp-tab-instructions" class="cp-tab-content">
		<div class="cp-tab-inner">
			<div class="cp-card">
				<div class="cp-card__header-static">
					<span class="cp-card__icon"></span>
					<h3 class="cp-card__title"><?php esc_html_e( 'Cooking Steps', 'dev-chefpress' ); ?></h3>
					<p class="cp-card__subtitle"><?php esc_html_e( 'Add step-by-step cooking instructions. Drag to reorder.', 'dev-chefpress' ); ?></p>
				</div>
				<div class="cp-card__body">
					<div id="cp-steps-list" class="cp-steps-list">
						<?php if ( ! empty( $steps ) ) : ?>
							<?php foreach ( $steps as $si => $step ) : ?>
								<?php include __DIR__ . '/partials/step-item.php'; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</div><!-- /#cp-steps-list -->

					<div class="cp-add-actions">
						<button type="button" id="cp-add-step" class="cp-btn cp-btn--primary">
							<span>＋</span> <?php esc_html_e( 'Add Step', 'dev-chefpress' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div><!-- /#cp-tab-instructions -->

	<!-- ╔══════════════════════════════════════╗ -->
	<!-- ║          TAB: NUTRITION              ║ -->
	<!-- ╚══════════════════════════════════════╝ -->
	<div id="cp-tab-nutrition" class="cp-tab-content">
		<div class="cp-tab-inner">
			<div class="cp-card">
				<div class="cp-card__header-static">
					<span class="cp-card__icon"></span>
					<h3 class="cp-card__title"><?php esc_html_e( 'Full Nutrition Table', 'dev-chefpress' ); ?></h3>
					<p class="cp-card__subtitle"><?php esc_html_e( 'Detailed nutritional information per serving.', 'dev-chefpress' ); ?></p>
				</div>
				<div class="cp-card__body">
					<div class="cp-field-grid cp-field-grid--2">
						<div class="cp-field">
							<label class="cp-label"><?php esc_html_e( 'Per Serving Label', 'dev-chefpress' ); ?></label>
							<input type="text" name="_chefpress_per_serving_label"
								   value="<?php echo esc_attr( $nutr_table['per_serving_label'] ?? '' ); ?>"
								   class="cp-input"
								   placeholder="<?php esc_attr_e( 'e.g. Per 100g / Per serving', 'dev-chefpress' ); ?>" />
						</div>
						<div class="cp-field">
							<label class="cp-label"><?php esc_html_e( 'Nutrition Note', 'dev-chefpress' ); ?></label>
							<input type="text" name="_chefpress_nutrition_note"
								   value="<?php echo esc_attr( $nutr_table['nutrition_note'] ?? '' ); ?>"
								   class="cp-input"
								   placeholder="<?php esc_attr_e( 'e.g. Values are approximate', 'dev-chefpress' ); ?>" />
						</div>
					</div>

					<div class="cp-nutr-table-grid">
						<?php
						$nutr_fields = [
							'energy_kj'      => __( 'Energy (kJ)', 'dev-chefpress' ),
							'energy_kcal'    => __( 'Energy (kcal)', 'dev-chefpress' ),
							'fats'           => __( 'Fats (g)', 'dev-chefpress' ),
							'saturated_fats' => __( 'of which Saturated (g)', 'dev-chefpress' ),
							'carbs'          => __( 'Carbohydrates (g)', 'dev-chefpress' ),
							'sugars'         => __( 'of which Sugars (g)', 'dev-chefpress' ),
							'fibers'         => __( 'Fibre (g)', 'dev-chefpress' ),
							'proteins'       => __( 'Protein (g)', 'dev-chefpress' ),
							'salt'           => __( 'Salt (g)', 'dev-chefpress' ),
						];
						foreach ( $nutr_fields as $key => $label ) :
						?>
							<div class="cp-nutr-field">
								<label class="cp-label cp-label--small"><?php echo esc_html( $label ); ?></label>
								<input type="number"
									   name="_chefpress_nutr_<?php echo esc_attr( $key ); ?>"
									   value="<?php echo esc_attr( $nutr_table[ $key ] ?? '' ); ?>"
									   class="cp-input cp-input--sm"
									   min="0" step="0.01"
									   placeholder="0" />
							</div>
						<?php endforeach; ?>
					</div><!-- /.cp-nutr-table-grid -->
				</div>
			</div>
		</div>
	</div><!-- /#cp-tab-nutrition -->

	<!-- ╔══════════════════════════════════════╗ -->
	<!-- ║          TAB: ALLERGENS              ║ -->
	<!-- ╚══════════════════════════════════════╝ -->
	<div id="cp-tab-allergens" class="cp-tab-content">
		<div class="cp-tab-inner">
			<div class="cp-card">
				<div class="cp-card__header-static">
					<span class="cp-card__icon"></span>
					<h3 class="cp-card__title"><?php esc_html_e( 'Allergen Information', 'dev-chefpress' ); ?></h3>
					<p class="cp-card__subtitle"><?php esc_html_e( 'Provide clear allergen warnings for your recipe.', 'dev-chefpress' ); ?></p>
				</div>
				<div class="cp-card__body">
					<div class="cp-field-grid cp-field-grid--2">
						<div class="cp-field">
							<label class="cp-label"><?php esc_html_e( 'Main Allergen', 'dev-chefpress' ); ?></label>
							<input type="text"
								   name="_chefpress_main_allergen"
								   value="<?php echo esc_attr( $allergens['main_allergen'] ?? '' ); ?>"
								   class="cp-input"
								   placeholder="<?php esc_attr_e( 'e.g. Gluten, Nuts, Dairy', 'dev-chefpress' ); ?>" />
						</div>
						<div class="cp-field">
							<label class="cp-label"><?php esc_html_e( 'Allergen Tags', 'dev-chefpress' ); ?></label>
							<div class="cp-tags-wrap">
								<div class="cp-tags-list" id="cp-allergen-tags-list">
									<?php
									$allergen_list = $allergens['allergen_list'] ?? [];
									foreach ( $allergen_list as $at ) :
									?>
										<span class="cp-tag cp-tag--danger">
											<?php echo esc_html( $at ); ?>
											<button type="button" class="cp-tag__remove">×</button>
											<input type="hidden" name="_chefpress_allergen_list[]" value="<?php echo esc_attr( $at ); ?>" />
										</span>
									<?php endforeach; ?>
								</div>
								<input type="text" id="cp-allergen-tag-input" class="cp-input cp-tag-input" data-list="cp-allergen-tags-list" data-field-name="_chefpress_allergen_list[]"
									   placeholder="<?php esc_attr_e( 'e.g. Wheat, Eggs — press Enter', 'dev-chefpress' ); ?>" />
							</div>
						</div>
					</div>
					<div class="cp-field">
						<label class="cp-label"><?php esc_html_e( 'Allergen Description', 'dev-chefpress' ); ?></label>
						<textarea name="_chefpress_allergen_description"
								  class="cp-textarea"
								  rows="4"
								  placeholder="<?php esc_attr_e( 'Describe allergen details, warnings, or substitution advice…', 'dev-chefpress' ); ?>"><?php echo esc_textarea( $allergens['allergen_description'] ?? '' ); ?></textarea>
					</div>
				</div>
			</div>
		</div>
	</div><!-- /#cp-tab-allergens -->

</div><!-- /#chefpress_recipe_data -->

<!-- ╔══════════════════════════════════════╗ -->
<!-- ║          JS TEMPLATES               ║ -->
<!-- ╚══════════════════════════════════════╝ -->
<!-- Step Template (for JS cloning) -->
<script type="text/template" id="cp-step-template">
<?php
$si   = '{{INDEX}}';
$step = [];
include __DIR__ . '/partials/step-item.php';
?>
</script>

<!-- Group Template -->
<script type="text/template" id="cp-group-template">
<?php
$gi    = '{{INDEX}}';
$group = [];
include __DIR__ . '/partials/group-item.php';
?>
</script>

<!-- Ingredient Template (for JS cloning inside groups) -->
<script type="text/template" id="cp-ingredient-template">
<?php include __DIR__ . '/partials/ingredient-item.php'; ?>
</script>
