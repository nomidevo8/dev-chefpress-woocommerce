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
		$meta_key   = '';
		$meta_type  = '';

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
				$orderby   = 'meta_value_num';
				$order     = $sort_dir;
				$meta_key  = $meta_key_map[ $sort_key ];
				$meta_type = 'NUMERIC';
				$meta_query[] = [
					'key'     => $meta_key,
					'compare' => 'EXISTS',
				];
			}
		}

		$query_args = [
			'post_type'      => 'product',
			'posts_per_page' => 49,
			'paged'          => $page,
			'tax_query'      => $tax_query,
			'orderby'        => $orderby,
			'order'          => $order,
		];

		if ( $meta_key !== '' ) {
			$query_args['meta_key']  = $meta_key;
			$query_args['meta_type'] = $meta_type;
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $query_args );
		
		// Always return frontend-compatible JSON data (no HTML from backend)
		$recipes = self::get_recipes_data( $query );

		return [
			'html'          => '',
			'total_pages'   => (int) $query->max_num_pages,
			'current_page'  => $page,
			'total_count'   => (int) $query->found_posts,
			'recipes_count' => (int) $query->found_posts,
			'recipes'       => $recipes,
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
				'calories'      => $nutrition['calories'] ?: '',
				'protein'       => $nutrition['protein'] ?: '',
				'carbs'         => $nutrition['carbs'] ?: '',
				'fat'           => $nutrition['fat'] ?: '',
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
			static function( $term ) {
				return ucwords( str_replace( '-', ' ', $term->name ) );
			},
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
			static function( $term ) {
				return ucwords( str_replace( '-', ' ', $term->name ) );
			},
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
			static function( $term ) {
				return ucwords( str_replace( '-', ' ', $term->name ) );
			},
			$terms
		);
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
		];

		$response = self::filter_recipes( $filters );
		wp_send_json( $response );
	}

	/**
	 * Get recipe HTML for modal display.
	 *
	 * @param int $product_id
	 * @return string
	 */
	public static function get_recipe_modal_html( int $product_id ): string {
		if ( ! $product_id || get_post_type( $product_id ) !== 'product' ) {
			return '';
		}

		// Start output buffering to capture the template
		ob_start();

		// Include the single-recipe template
		$template_path = plugin_dir_path( __FILE__ ) . '../../templates/single-recipe.php';
		if ( file_exists( $template_path ) ) {
			// Set the global post to the recipe
			$GLOBALS['post'] = get_post( $product_id );
			setup_postdata( $GLOBALS['post'] );

			include $template_path;

			wp_reset_postdata();
		}

		$html = ob_get_clean();
		return $html;
	}

	/**
	 * AJAX handler for getting recipe modal details.
	 */
	public static function handle_ajax_get_recipe_details(): void {
		$recipe_id = absint( $_POST['recipe_id'] ?? 0 );

		if ( ! $recipe_id ) {
			wp_die( 'Recipe not found.' );
		}

		$html = self::get_recipe_modal_html( $recipe_id );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}
}
