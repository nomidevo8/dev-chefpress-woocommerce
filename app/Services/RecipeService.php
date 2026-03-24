<?php
declare( strict_types=1 );

namespace DevChefPress\Services;

use DevChefPress\Helpers\Sanitizer;
use DevChefPress\Models\Recipe;
use DevChefPress\Services\PluginSettings;

/**
 * Class RecipeService
 *
 * Processes and saves recipe data from POST data.
 */
class RecipeService {

	private int $post_id;

	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Process and save all recipe data from raw POST input.
	 *
	 * @param array<string, mixed> $post
	 */
	public function process_and_save( array $post ): void {
		$this->save_hero( $post );
		$this->save_nutrition_summary( $post );
		$this->save_tags( $post );
		$this->save_before_start( $post );
		$this->save_steps( $post );
		$this->save_groups( $post );
		$this->save_allergens( $post );
		$this->save_weeks( $post );
		$this->save_nutrition_table( $post );

		// Invalidate model cache.
		( new Recipe( $this->post_id ) )->invalidate_cache();
	}

	/**
	 * Save weekly menu assignments.
	 *
	 * @param array<string, mixed> $post
	 */
	private function save_weeks( array $post ): void {
		$raw = $post['chefpress_weeks'] ?? [];
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		$week_ids = array_filter(
			array_map( static function ( $term_id ) {
				return absint( $term_id );
			}, $raw ),
			static function ( $id ) {
				return $id > 0;
			}
		);

		if ( empty( $week_ids ) ) {
			wp_set_object_terms( $this->post_id, [], 'chefpress_week', false );
			return;
		}

		wp_set_object_terms( $this->post_id, $week_ids, 'chefpress_week', false );
	}

	/**
	 * Save hero section fields.
	 *
	 * @param array<string, mixed> $post
	 */
	private function save_hero( array $post ): void {
		$this->update_meta( '_chefpress_subtitle', Sanitizer::text( $post['_chefpress_subtitle'] ?? '' ) );
		$this->update_meta( '_chefpress_cooking_time', Sanitizer::text( $post['_chefpress_cooking_time'] ?? '' ) );
		$this->update_meta( '_chefpress_reviews_count', absint( $post['_chefpress_reviews_count'] ?? 0 ) );

		$rating = (float) ( $post['_chefpress_rating'] ?? 0 );
		$rating = max( 0.0, min( 5.0, $rating ) ); // Validate 0–5.
		$this->update_meta( '_chefpress_rating', $rating );
	}

	/**
	 * Save quick nutrition summary.
	 *
	 * @param array<string, mixed> $post
	 */
	private function save_nutrition_summary( array $post ): void {
		$calories = (int) ( $post['_chefpress_calories'] ?? 0 );
		if ( $calories < 0 ) {
			$calories = 0;
		}
		$this->update_meta( '_chefpress_calories', $calories );

		foreach ( [ 'protein', 'carbs', 'fat' ] as $field ) {
			$val = (float) ( $post[ '_chefpress_' . $field ] ?? 0 );
			$val = max( 0.0, $val );
			$this->update_meta( '_chefpress_' . $field, $val );
		}
	}

	/**
	 * Save recipe tags.
	 *
	 * @param array<string, mixed> $post
	 */
	private function save_tags( array $post ): void {
		$tags = $this->normalize_terms( $post['_chefpress_tags'] ?? [] );
		$this->update_meta( '_chefpress_tags', $tags );
		wp_set_object_terms( $this->post_id, $tags, 'chefpress_recipe_tag', false );
	}

	/**
	 * Save before-start content.
	 *
	 * @param array<string, mixed> $post
	 */
	private function save_before_start( array $post ): void {
		$this->update_meta( '_chefpress_before_start', Sanitizer::textarea( $post['_chefpress_before_start'] ?? '' ) );
	}

	/**
	 * Save cooking steps.
	 *
	 * @param array<string, mixed> $post
	 */
	private function save_steps( array $post ): void {
		$raw_steps = $post['_chefpress_steps'] ?? [];
		if ( ! is_array( $raw_steps ) ) {
			delete_post_meta( $this->post_id, '_chefpress_steps' );
			return;
		}

		$steps = [];
		foreach ( $raw_steps as $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$title       = Sanitizer::text( $step['step_title'] ?? '' );
			$description = Sanitizer::html( (string) ( $step['step_description'] ?? '' ) );
			// Skip completely empty steps (ignore HTML-only whitespace).
			if ( '' === $title && '' === trim( wp_strip_all_tags( $description ) ) ) {
				continue;
			}
			$steps[] = [
				'step_number'      => absint( $step['step_number'] ?? 1 ),
				'step_title'       => $title,
				'step_description' => $description,
				'step_image_id'    => absint( $step['step_image_id'] ?? 0 ),
				'step_tip'         => Sanitizer::textarea( $step['step_tip'] ?? '' ),
			];
		}

		$this->update_meta( '_chefpress_steps', $steps );
	}

	/**
	 * Save ingredient groups (nested).
	 *
	 * @param array<string, mixed> $post
	 */
	private function save_groups( array $post ): void {
		$raw_groups = $post['_chefpress_groups'] ?? [];
		if ( ! is_array( $raw_groups ) ) {
			delete_post_meta( $this->post_id, '_chefpress_groups' );
			return;
		}

		$groups = [];
		foreach ( $raw_groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$group_name  = Sanitizer::text( $group['group_name'] ?? '' );
			$raw_ings    = $group['ingredients'] ?? [];
			$ingredients = [];

			if ( is_array( $raw_ings ) ) {
				foreach ( $raw_ings as $ing ) {
					if ( ! is_array( $ing ) ) {
						continue;
					}
					$ing_name = Sanitizer::text( $ing['ingredient_name'] ?? '' );
					if ( '' === $ing_name ) {
						continue;
					}
					$ingredients[] = [
						'ingredient_name' => $ing_name,
						'quantity'        => Sanitizer::text( $ing['quantity'] ?? '' ),
						'unit'            => Sanitizer::text( $ing['unit'] ?? '' ),
					];
				}
			}

			if ( '' === $group_name && empty( $ingredients ) ) {
				continue;
			}

			$groups[] = [
				'group_name'  => $group_name,
				'ingredients' => $ingredients,
			];
		}

		$this->update_meta( '_chefpress_groups', $groups );
	}

	/**
	 * Save allergen data.
	 *
	 * @param array<string, mixed> $post
	 */
	private function save_allergens( array $post ): void {
		$this->update_meta( '_chefpress_main_allergen', Sanitizer::text( $post['_chefpress_main_allergen'] ?? '' ) );
		$this->update_meta( '_chefpress_allergen_description', Sanitizer::html( (string) ( $post['_chefpress_allergen_description'] ?? '' ) ) );

		$list = $this->normalize_terms( $post['_chefpress_allergen_list'] ?? [] );
		$this->update_meta( '_chefpress_allergen_list', $list );
		wp_set_object_terms( $this->post_id, $list, 'chefpress_allergen_tag', false );
	}

	/**
	 * Save full nutrition table.
	 *
	 * @param array<string, mixed> $post
	 */
	private function save_nutrition_table( array $post ): void {
		$allowed = [];
		foreach ( PluginSettings::get_nutrition_fields() as $def ) {
			$field          = $def['key'];
			$allowed[]      = $field;
			$key            = '_chefpress_nutr_' . $field;
			$val            = (float) ( $post[ $key ] ?? 0 );
			$val            = max( 0.0, $val );
			$this->update_meta( $key, $val );
		}

		// Remove stored values for keys no longer in global schema.
		$all_meta = get_post_meta( $this->post_id );
		if ( is_array( $all_meta ) ) {
			foreach ( array_keys( $all_meta ) as $meta_key ) {
				if ( ! is_string( $meta_key ) || ! str_starts_with( $meta_key, '_chefpress_nutr_' ) ) {
					continue;
				}
				$suffix = substr( $meta_key, strlen( '_chefpress_nutr_' ) );
				if ( in_array( $suffix, [ 'per_serving_label', 'nutrition_note' ], true ) ) {
					continue;
				}
				if ( ! in_array( $suffix, $allowed, true ) ) {
					delete_post_meta( $this->post_id, $meta_key );
				}
			}
		}

		$this->update_meta( '_chefpress_per_serving_label', Sanitizer::text( $post['_chefpress_per_serving_label'] ?? '' ) );
		$this->update_meta( '_chefpress_nutrition_note', Sanitizer::text( $post['_chefpress_nutrition_note'] ?? '' ) );
	}

	/**
	 * Update or delete a meta key based on value emptiness.
	 *
	 * @param mixed $value
	 */
	private function update_meta( string $key, $value ): void {
		if ( '' === $value || ( is_array( $value ) && empty( $value ) ) ) {
			delete_post_meta( $this->post_id, $key );
		} else {
			update_post_meta( $this->post_id, $key, $value );
		}
	}

	/**
	 * Normalize free-text tags/allergens for consistent storage and retrieval.
	 *
	 * @param mixed $raw
	 * @return string[]
	 */
	private function normalize_terms( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$terms = array_map(
			static function ( $item ): string {
				return trim( sanitize_text_field( (string) $item ) );
			},
			$raw
		);

		$terms = array_filter(
			$terms,
			static function ( string $item ): bool {
				return '' !== $item;
			}
		);

		// Case-insensitive unique values while preserving first input casing.
		$seen   = [];
		$result = [];
		foreach ( $terms as $term ) {
			$key = strtolower( $term );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$result[]     = $term;
		}

		return array_values( $result );
	}
}
