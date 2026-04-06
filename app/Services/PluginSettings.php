<?php
declare( strict_types=1 );

namespace DevChefPress\Services;

/**
 * Global plugin options: nutrition schema, preset ingredients, allergens, recipe labels.
 */
final class PluginSettings {

	public const OPTION_KEY = 'chefpress_plugin_settings';

	public const CPT_SLUG = 'chefpress';

	/**
	 * Default nutrition rows (matches legacy hard-coded table).
	 *
	 * @return list<array{key: string, label: string, unit: string}>
	 */
	public static function default_nutrition_fields(): array {
		return [
			[ 'key' => 'energy_kj', 'label' => 'Energy (kJ)', 'unit' => 'kJ' ],
			[ 'key' => 'energy_kcal', 'label' => 'Energy (kcal)', 'unit' => 'kcal' ],
			[ 'key' => 'fats', 'label' => 'Fats', 'unit' => 'g' ],
			[ 'key' => 'saturated_fats', 'label' => 'of which Saturated', 'unit' => 'g' ],
			[ 'key' => 'carbs', 'label' => 'Carbohydrates', 'unit' => 'g' ],
			[ 'key' => 'sugars', 'label' => 'of which Sugars', 'unit' => 'g' ],
			[ 'key' => 'fibers', 'label' => 'Fibre', 'unit' => 'g' ],
			[ 'key' => 'proteins', 'label' => 'Protein', 'unit' => 'g' ],
			[ 'key' => 'salt', 'label' => 'Salt', 'unit' => 'g' ],
		];
	}

	/**
	 * Default meal prices (per unit).
	 *
	 * @return array<string, float>
	 */
	public static function default_meal_prices(): array {
		return [
			'Breakfast' => 5.00,
			'Lunch'     => 12.00,
			'Dinner'    => 15.00,
			'Snacks'    => 4.00,
		];
	}

	/**
	 * Default plan discount rates (as decimals: 0.10 = 10%).
	 *
	 * @return array<string, float>
	 */
	public static function default_plan_discounts(): array {
		return [
			'1 Week'    => 0.00,
			'1 Month'   => 0.10,
			'3 Months'  => 0.20,
			'6 Months'  => 0.25,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return [
			'nutrition_fields'      => self::default_nutrition_fields(),
			'preset_ingredients'    => [],
			'preset_allergens'      => [],
			'preset_recipe_labels'  => [],
			'theme_colors'          => [
				'brand'          => '#ff6b4a',
				'brand_light'    => '#fff0ed',
				'text_main'      => '#1a1a1a',
				'text_muted'     => '#666666',
				'bg_light'       => '#f9f9f9',
				'border'         => '#e5e5e5',
				'white'          => '#ffffff',
			],
			'weekly_start_date'     => \DateTime::createFromFormat( 'Y-m-d', date( 'Y-m-d' ) )->modify('monday this week')->format( 'Y-m-d' ),
			'meal_prices'           => self::default_meal_prices(),
			'plan_discounts'        => self::default_plan_discounts(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$defaults = self::defaults();
		$saved    = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		$nutrition = $saved['nutrition_fields'] ?? null;
		if ( ! is_array( $nutrition ) || [] === $nutrition ) {
			$nutrition = $defaults['nutrition_fields'];
		}

		return [
			'nutrition_fields'     => self::sanitize_nutrition_fields( $nutrition ),
			'preset_ingredients'   => self::normalize_string_list( $saved['preset_ingredients'] ?? [] ),
			'preset_allergens'     => self::normalize_string_list( $saved['preset_allergens'] ?? [] ),
			'preset_recipe_labels' => self::normalize_string_list( $saved['preset_recipe_labels'] ?? [] ),
			'theme_colors'         => self::sanitize_theme_colors( $saved['theme_colors'] ?? [] ),
			'weekly_start_date'    => self::sanitize_date( $saved['weekly_start_date'] ?? $defaults['weekly_start_date'] ),
			'meal_prices'          => self::sanitize_meal_prices( $saved['meal_prices'] ?? [] ),
			'plan_discounts'       => self::sanitize_plan_discounts( $saved['plan_discounts'] ?? [] ),
		];
	}

	/**
	 * @return list<array{key: string, label: string, unit: string}>
	 */
	public static function get_nutrition_fields(): array {
		return self::all()['nutrition_fields'];
	}

	/**
	 * @return string[]
	 */
	public static function get_preset_ingredients(): array {
		return self::all()['preset_ingredients'];
	}

	/**
	 * @return string[]
	 */
	public static function get_preset_allergens(): array {
		return self::all()['preset_allergens'];
	}

	/**
	 * @return string[]
	 */
	public static function get_preset_recipe_labels(): array {
		return self::all()['preset_recipe_labels'];
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_theme_colors(): array {
		return self::all()['theme_colors'];
	}

	/**
	 * @return array<string, float>
	 */
	public static function get_meal_prices(): array {
		return self::all()['meal_prices'];
	}

	/**
	 * @return array<string, float>
	 */
	public static function get_plan_discounts(): array {
		return self::all()['plan_discounts'];
	}

	/**
	 * @param array<int, mixed> $raw
	 * @return list<array{key: string, label: string, unit: string}>
	 */
	public static function sanitize_nutrition_fields( array $raw ): array {
		$out  = [];
		$seen = [];
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$key = isset( $row['key'] ) ? sanitize_key( (string) $row['key'] ) : '';
			if ( '' === $key ) {
				$label_try = isset( $row['label'] ) ? sanitize_title( (string) $row['label'] ) : '';
				$key       = '' !== $label_try ? $label_try : 'nutrient_' . wp_generate_password( 6, false, false );
			}
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$out[]        = [
				'key'   => $key,
				'label' => sanitize_text_field( (string) ( $row['label'] ?? $key ) ),
				'unit'  => sanitize_text_field( (string) ( $row['unit'] ?? '' ) ),
			];
		}
		return array_values( $out );
	}

	/**
	 * @param mixed $raw
	 * @return string[]
	 */
	public static function normalize_string_list( $raw ): array {
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
			static function ( string $s ): bool {
				return '' !== $s;
			}
		);
		$seen   = [];
		$result = [];
		foreach ( $terms as $t ) {
			$k = strtolower( $t );
			if ( isset( $seen[ $k ] ) ) {
				continue;
			}
			$seen[ $k ] = true;
			$result[]   = $t;
		}
		return array_values( $result );
	}

	/**Sanitize and validate date to YYYY-MM-DD format.
	 *
	 * @param mixed $raw
	 * @return string Date in YYYY-MM-DD format
	 */
	public static function sanitize_date( $raw ): string {
		$date_str = sanitize_text_field( (string) $raw );
		
		// Try to parse the date
		$date = \DateTime::createFromFormat( 'Y-m-d', $date_str );
		
		if ( false === $date ) {
			// Fall back to today's Monday
			$date = \DateTime::createFromFormat( 'Y-m-d', date( 'Y-m-d' ) );
			$date->modify( 'monday this week' );
		}
		
		return $date->format( 'Y-m-d' );
	}

	/**
	 * 
	 * Normalize theme color map.
	 *
	 * @param mixed $raw
	 * @return array<string, string>
	 */
	public static function sanitize_theme_colors( $raw ): array {
		$defaults = self::defaults()['theme_colors'];
		if ( ! is_array( $raw ) ) {
			return $defaults;
		}
		$out = [];
		foreach ( $defaults as $key => $default_value ) {
			$value = isset( $raw[ $key ] ) ? sanitize_hex_color( (string) $raw[ $key ] ) : '';
			$out[ $key ] = $value ? $value : $default_value;
		}
		return $out;
	}

	/**
	 * Sanitize meal prices (as floats).
	 *
	 * @param mixed $raw
	 * @return array<string, float>
	 */
	public static function sanitize_meal_prices( $raw ): array {
		$defaults = self::default_meal_prices();
		if ( ! is_array( $raw ) ) {
			return $defaults;
		}
		$out = [];
		foreach ( $defaults as $meal => $default_price ) {
			$price = isset( $raw[ $meal ] ) ? floatval( (string) $raw[ $meal ] ) : $default_price;
			$out[ $meal ] = $price >= 0 ? round( $price, 2 ) : $default_price;
		}
		return $out;
	}

	/**
	 * Sanitize plan discounts (as floats between 0 and 1).
	 *
	 * @param mixed $raw
	 * @return array<string, float>
	 */
	public static function sanitize_plan_discounts( $raw ): array {
		$defaults = self::default_plan_discounts();
		if ( ! is_array( $raw ) ) {
			return $defaults;
		}
		$out = [];
		foreach ( $defaults as $plan => $default_discount ) {
			$discount = isset( $raw[ $plan ] ) ? floatval( (string) $raw[ $plan ] ) : $default_discount;
			// Ensure discount is between 0 and 1
			if ( $discount < 0 || $discount > 1 ) {
				$discount = $default_discount;
			}
			$out[ $plan ] = round( $discount, 4 );
		}
		return $out;
	}


	/**
	 * Persist settings from POSTed arrays (already unslashed by WP for options - caller passes $_POST slice).
	 *
	 * @param array<string, mixed> $post
	 */
	public static function save_from_post( array $post ): void {
		$nutrition = [];
		if ( isset( $post['nutrition'] ) && is_array( $post['nutrition'] ) ) {
			$nutrition = self::sanitize_nutrition_fields( $post['nutrition'] );
		}

		$data = [
			'nutrition_fields'     => $nutrition,
			'preset_ingredients'   => self::normalize_string_list( $post['preset_ingredients'] ?? [] ),
			'preset_allergens'     => self::normalize_string_list( $post['preset_allergens'] ?? [] ),
			'preset_recipe_labels' => self::normalize_string_list( $post['preset_recipe_labels'] ?? [] ),
			'theme_colors'         => self::sanitize_theme_colors( $post['theme_colors'] ?? [] ),
			'weekly_start_date'    => self::sanitize_date( $post['weekly_start_date'] ?? '' ),
			'meal_prices'          => self::sanitize_meal_prices( $post['meal_prices'] ?? [] ),
			'plan_discounts'       => self::sanitize_plan_discounts( $post['plan_discounts'] ?? [] ),
		];

		update_option( self::OPTION_KEY, $data, false );
	}

	/**
	 * For wp_localize_script on product screen.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_presets_for_js(): array {
		$a = self::all();
		return [
			'ingredients'   => $a['preset_ingredients'],
			'allergens'     => $a['preset_allergens'],
			'recipeLabels'  => $a['preset_recipe_labels'],
			'mealPrices'    => $a['meal_prices'],
			'planDiscounts' => $a['plan_discounts'],
		];
	}
}
