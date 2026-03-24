<?php
declare( strict_types=1 );

namespace DevChefPress\Services;

use DevChefPress\Models\Recipe;

/**
 * Class FilterService
 *
 * Handles recipe filtering via AJAX and provides query building.
 */
class FilterService {

	/**
	 * Build a WP_Query for recipe filtering.
	 *
	 * @param array<string, mixed> $filters
	 * @return array<string, mixed>
	 */
	public static function filter_recipes( array $filters ): array {
		$week = sanitize_text_field( $filters['week'] ?? '' );
        $correct_week = 'week-' . $week;
		if ( ! $week ) {
			return [
				'html'          => '',
				'total_pages'   => 0,
				'current_page'  => 1,
				'total_count'   => 0,
				'recipes_count' => 0,
				'recipes'       => [],
			];
		}

		$category  = isset( $filters['category'] ) ? sanitize_text_field( $filters['category'] ) : '';
		$tags      = isset( $filters['tags'] ) ? (array) $filters['tags'] : [];
		$allergens = isset( $filters['allergens'] ) ? (array) $filters['allergens'] : [];
		$sort      = isset( $filters['sort'] ) ? sanitize_text_field( $filters['sort'] ) : '';
		$page      = absint( $filters['page'] ?? 1 );
		if ( $page < 1 ) {
			$page = 1;
		}

		// Build tax_query.
		$tax_query = [
			'relation' => 'AND',
		];

		// Week is required.
		$tax_query[] = [
			'taxonomy' => 'chefpress_week',
			'field'    => 'slug',
			'terms'    => $correct_week,
			'operator' => 'IN',
		];

		// Optional category.
		if ( ! empty( $category ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category,
				'operator' => 'IN',
			];
		}

		// Optional tags.
		if ( ! empty( $tags ) ) {
			$tag_terms = array_map( 'sanitize_text_field', (array) $tags );
			$tag_terms = array_filter( $tag_terms );
			if ( ! empty( $tag_terms ) ) {
				$tax_query[] = [
					'taxonomy' => 'chefpress_recipe_tag',
					'field'    => 'slug',
					'terms'    => $tag_terms,
					'operator' => 'IN',
				];
			}
		}

		// Optional allergens.
		if ( ! empty( $allergens ) ) {
			$allergen_terms = array_map( 'sanitize_text_field', (array) $allergens );
			$allergen_terms = array_filter( $allergen_terms );
			if ( ! empty( $allergen_terms ) ) {
				$tax_query[] = [
					'taxonomy' => 'chefpress_allergen_tag',
					'field'    => 'slug',
					'terms'    => $allergen_terms,
					'operator' => 'NOT IN',
				];
			}
		}

		// Build meta_query if needed for sorting.
		$meta_query = [];
		$orderby    = 'date';
		$order      = 'DESC';

		if ( ! empty( $sort ) ) {
			$sort_parts = explode( ':', $sort );
			$sort_key   = $sort_parts[0] ?? '';
			$sort_dir   = $sort_parts[1] ?? 'ASC';
			$sort_dir   = in_array( strtoupper( $sort_dir ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $sort_dir ) : 'ASC';

			$meta_key_map = [
				'calories' => '_chefpress_calories',
				'carbs'    => '_chefpress_carbs',
				'protein'  => '_chefpress_protein',
				'fat'      => '_chefpress_fat',
				'time'     => '_chefpress_cooking_time',
			];

			if ( isset( $meta_key_map[ $sort_key ] ) ) {
				$orderby = 'meta_value_num';
				$order   = $sort_dir;
				$meta_query[] = [
					'key' => $meta_key_map[ $sort_key ],
				];
			}
		}

		$query_args = [
			'post_type'      => 'product',
			'posts_per_page' => 9,
			'paged'          => $page,
			'tax_query'      => $tax_query,
			'orderby'        => $orderby,
			'order'          => $order,
		];

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $query_args );
		// Determine if we should return frontend-compatible data
		$is_frontend_mode = isset( $filters['filter_mode'] ) && $filters['filter_mode'] === 'frontend';
		return [
			'html'          => self::render_recipes( $query ),
			'total_pages'   => (int) $query->max_num_pages,
			'current_page'  => $page,
			'total_count'   => (int) $query->found_posts,
			'recipes_count' => (int) $query->found_posts,
			'recipes'       => $is_frontend_mode ? self::get_recipes_data( $query ) : [],
		];
	}

	/**
	 * Extract recipe data from WP_Query for frontend mode.
	 *
	 * @param \WP_Query $query
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_recipes_data( \WP_Query $query ): array {
		$recipes = [];

		if ( ! $query->have_posts() ) {
			return $recipes;
		}
         
		while ( $query->have_posts() ) {
			$query->the_post();
			$product_id = get_the_ID();
			$recipe = new Recipe( $product_id );
			$hero = $recipe->get_hero();
			$nutrition = $recipe->get_nutrition();
            
			$recipes[] = [
				'id'            => $product_id,
				'title'         => get_the_title(),
				'url'           => get_the_permalink(),
				'image'         => get_the_post_thumbnail_url( $product_id, 'medium' ) ?: '',
				'subtitle'      => $hero['subtitle'] ?: '',
				'cookingTime'   => $hero['cooking_time'] ?: '',
				'calories'      => $nutrition['calories'] ?: 0,
				'protein'       => $nutrition['protein'] ?: 0,
				'carbs'         => $nutrition['carbs'] ?: 0,
				'fat'           => $nutrition['fat'] ?: 0,
				'categories'    => self::get_recipe_categories( $product_id ),
				'tags'          => self::get_recipe_tags( $product_id ),
				'allergens'     => self::get_recipe_allergens( $product_id ),
			];
		}

		wp_reset_postdata();

		return $recipes;
	}

	/**
	 * Get recipe categories.
	 *
	 * @param int $product_id
	 * @return string[]
	 */
	private static function get_recipe_categories( int $product_id ): array {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( ! is_array( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}
		return array_map(
			static fn( $term ) => $term->slug,
			$terms
		);
	}

	/**
	 * Get recipe tags.
	 *
	 * @param int $product_id
	 * @return string[]
	 */
	private static function get_recipe_tags( int $product_id ): array {
		$terms = get_the_terms( $product_id, 'chefpress_recipe_tag' );
		if ( ! is_array( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}
		return array_map(
			static fn( $term ) => $term->slug,
			$terms
		);
	}

	/**
	 * Get recipe allergens.
	 *
	 * @param int $product_id
	 * @return string[]
	 */
	private static function get_recipe_allergens( int $product_id ): array {
		$terms = get_the_terms( $product_id, 'chefpress_allergen_tag' );
		if ( ! is_array( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}
		return array_map(
			static fn( $term ) => $term->slug,
			$terms
		);
	}

	/**
	 * Render recipes HTML from WP_Query.
	 *
	 * @param \WP_Query $query
	 * @return string
	 */
	private static function render_recipes( \WP_Query $query ): string {
		$html = '';

		if ( ! $query->have_posts() ) {
			$html = '<p class="cp-no-results">' . esc_html__( 'No recipes found. Try adjusting your filters.', 'dev-chefpress' ) . '</p>';
			return $html;
		}

		$html .= '<div class="cp-recipes-grid">';

		while ( $query->have_posts() ) {
			$query->the_post();
			$product_id = get_the_ID();
			$recipe = new Recipe( $product_id );
			$title = get_the_title();
			$hero = $recipe->get_hero();
			$nutrition = $recipe->get_nutrition();

			$html .= '<article class="cp-recipe-card">';
			$html .= '  <div class="cp-recipe-card__image">';
			if ( has_post_thumbnail() ) {
				$html .= get_the_post_thumbnail( $product_id, 'medium', [ 'alt' => esc_attr( $title ) ] );
			}
			$html .= '  </div>';
			$html .= '  <div class="cp-recipe-card__body">';
			$html .= '    <h3 class="cp-recipe-card__title">' . esc_html( $title ) . '</h3>';

			if ( ! empty( $hero['subtitle'] ) ) {
				$html .= '    <p class="cp-recipe-card__subtitle">' . esc_html( $hero['subtitle'] ) . '</p>';
			}

			if ( ! empty( $hero['cooking_time'] ) ) {
				$html .= '    <p class="cp-recipe-card__meta">⏱ ' . esc_html( $hero['cooking_time'] ) . '</p>';
			}

			$html .= '    <div class="cp-recipe-card__nutrition">';
			if ( $nutrition['calories'] > 0 ) {
				$html .= '      <span class="cp-nutrition-badge">' . absint( $nutrition['calories'] ) . ' kcal</span>';
			}
			if ( $nutrition['protein'] > 0 ) {
				$html .= '      <span class="cp-nutrition-badge">' . esc_html( number_format( $nutrition['protein'], 1 ) ) . 'g protein</span>';
			}
			if ( $nutrition['carbs'] > 0 ) {
				$html .= '      <span class="cp-nutrition-badge">' . esc_html( number_format( $nutrition['carbs'], 1 ) ) . 'g carbs</span>';
			}
			$html .= '    </div>';

			$html .= '    <a href="' . esc_url( get_the_permalink() ) . '" class="cp-btn cp-btn--primary cp-btn--sm">';
			$html .= esc_html__( 'View Recipe', 'dev-chefpress' );
			$html .= '    </a>';
			$html .= '  </div>';
			$html .= '</article>';
		}

		$html .= '</div>';

		wp_reset_postdata();

		return $html;
	}

	/**
	 * AJAX handler for recipe filtering.
	 */
	public static function handle_ajax_filter(): void {
		// Extract flat POST data
		$filters = [
			'week'        => sanitize_text_field( $_POST['week'] ?? '' ),
			'category'    => sanitize_text_field( $_POST['category'] ?? '' ),
			'tags'        => isset( $_POST['tags'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['tags'] ) ) : [],
			'allergens'   => isset( $_POST['allergens'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['allergens'] ) ) : [],
			'sort'        => sanitize_text_field( $_POST['sort'] ?? '' ),
			'page'        => absint( $_POST['page'] ?? 1 ),
			'filter_mode' => sanitize_text_field( $_POST['filter_mode'] ?? 'auto' ),
		];

		$response = self::filter_recipes( $filters );
		wp_send_json( $response );
	}
}
