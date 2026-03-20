<?php
declare( strict_types=1 );

namespace DevChefPress\Models;

/**
 * Class Recipe
 *
 * Data layer for a recipe product. All meta access goes through this class.
 * No direct get_post_meta calls in templates.
 */
class Recipe {

	private int $post_id;

	/** @var array<string, mixed>|null */
	private ?array $meta_cache = null;

	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Lazy-load all meta in a single call.
	 *
	 * @return array<string, mixed>
	 */
	private function get_all_meta(): array {
		if ( null !== $this->meta_cache ) {
			return $this->meta_cache;
		}

		$cache_key   = 'chefpress_recipe_' . $this->post_id;
		$cached      = wp_cache_get( $cache_key, 'chefpress' );

		if ( false !== $cached && is_array( $cached ) ) {
			$this->meta_cache = $cached;
			return $this->meta_cache;
		}

		$all_meta = get_post_meta( $this->post_id );
		$meta     = [];

		foreach ( $all_meta as $key => $value ) {
			if ( str_starts_with( $key, '_chefpress_' ) ) {
				$meta[ $key ] = maybe_unserialize( $value[0] ?? '' );
			}
		}

		wp_cache_set( $cache_key, $meta, 'chefpress', 300 );
		$this->meta_cache = $meta;
		return $meta;
	}

	/**
	 * Get a single meta value.
	 *
	 * @param mixed $default
	 * @return mixed
	 */
	private function get( string $key, $default = '' ) {
		$meta = $this->get_all_meta();
		return $meta[ $key ] ?? $default;
	}

	/**
	 * Get hero/top section data.
	 *
	 * @return array<string, mixed>
	 */
	public function get_hero(): array {
		return [
			'subtitle'      => (string) $this->get( '_chefpress_subtitle' ),
			'cooking_time'  => (string) $this->get( '_chefpress_cooking_time' ),
			'reviews_count' => (int) $this->get( '_chefpress_reviews_count', 0 ),
			'rating'        => (float) $this->get( '_chefpress_rating', 0.0 ),
		];
	}

	/**
	 * Get quick nutrition summary (top of page).
	 *
	 * @return array<string, mixed>
	 */
	public function get_nutrition(): array {
		return [
			'calories' => (int) $this->get( '_chefpress_calories', 0 ),
			'protein'  => (float) $this->get( '_chefpress_protein', 0 ),
			'carbs'    => (float) $this->get( '_chefpress_carbs', 0 ),
			'fat'      => (float) $this->get( '_chefpress_fat', 0 ),
		];
	}

	/**
	 * Get recipe tags.
	 *
	 * @return string[]
	 */
	public function get_tags(): array {
		$terms = get_the_terms( $this->post_id, 'chefpress_recipe_tag' );
		// wp_get_object_terms can return []; empty must fall back to meta (taxonomy sync may lag or fail).
		if ( ! is_wp_error( $terms ) && is_array( $terms ) && ! empty( $terms ) ) {
			return array_values(
				array_filter(
					array_map(
						static function ( \WP_Term $term ): string {
							return trim( (string) $term->name );
						},
						$terms
					)
				)
			);
		}

		$tags = $this->get( '_chefpress_tags', [] );
		return is_array( $tags ) ? array_values( array_filter( array_map( 'strval', $tags ) ) ) : [];
	}

	/**
	 * Get before-you-start content.
	 */
	public function get_before_start(): string {
		return (string) $this->get( '_chefpress_before_start' );
	}

	/**
	 * Get cooking steps.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_steps(): array {
		$steps = $this->get( '_chefpress_steps', [] );
		return is_array( $steps ) ? array_values( $steps ) : [];
	}

	/**
	 * Get ingredient groups (nested).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_ingredients(): array {
		$groups = $this->get( '_chefpress_groups', [] );
		return is_array( $groups ) ? array_values( $groups ) : [];
	}

	/**
	 * Get allergen data.
	 *
	 * @return array<string, mixed>
	 */
	public function get_allergens(): array {
		$terms = get_the_terms( $this->post_id, 'chefpress_allergen_tag' );
		$list  = ( ! is_wp_error( $terms ) && is_array( $terms ) && ! empty( $terms ) )
			? array_values(
				array_filter(
					array_map(
						static function ( \WP_Term $term ): string {
							return trim( (string) $term->name );
						},
						$terms
					)
				)
			)
			: $this->get( '_chefpress_allergen_list', [] );

		return [
			'main_allergen'        => (string) $this->get( '_chefpress_main_allergen' ),
			'allergen_description' => (string) $this->get( '_chefpress_allergen_description' ),
			'allergen_list'        => is_array( $list ) ? $list : [],
		];
	}

	/**
	 * Get full nutrition table data.
	 *
	 * @return array<string, mixed>
	 */
	public function get_nutrition_table(): array {
		$fields = [
			'energy_kj', 'energy_kcal', 'fats', 'saturated_fats',
			'carbs', 'sugars', 'fibers', 'proteins', 'salt',
			'per_serving_label', 'nutrition_note',
		];

		$result = [];
		foreach ( $fields as $field ) {
			$result[ $field ] = $this->get( '_chefpress_nutr_' . $field, '' );
		}

		// Also override with summary nutrition from hero fields.
		$result['per_serving_label'] = $this->get( '_chefpress_per_serving_label', '' );
		$result['nutrition_note']    = $this->get( '_chefpress_nutrition_note', '' );

		return $result;
	}

	/**
	 * Invalidate the object & wp_cache.
	 */
	public function invalidate_cache(): void {
		$this->meta_cache = null;
		wp_cache_delete( 'chefpress_recipe_' . $this->post_id, 'chefpress' );
	}

	/**
	 * Get post ID.
	 */
	public function get_post_id(): int {
		return $this->post_id;
	}
}
