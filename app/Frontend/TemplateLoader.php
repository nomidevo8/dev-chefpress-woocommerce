<?php
declare( strict_types=1 );

namespace DevChefPress\Frontend;

/**
 * Class TemplateLoader
 *
 * Loads frontend templates with theme override support.
 */
class TemplateLoader {

	/**
	 * Render a template file, allowing theme overrides.
	 *
	 * @param string               $template   Template name (without .php).
	 * @param array<string, mixed> $variables  Variables to extract into template scope.
	 */
	public function render( string $template, array $variables = [] ): void {
		$file = $this->locate( $template );

		if ( ! $file ) {
			return;
		}

		// Make variables available to template.
		extract( $variables, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		include $file;
	}

	/**
	 * Locate a template: check theme first, then plugin.
	 */
	private function locate( string $template ): ?string {
		$template_name = $template . '.php';

		// 1. Theme override: {theme}/chefpress/{template}.php
		$theme_file = locate_template( 'chefpress/' . $template_name );
		if ( $theme_file ) {
			return $theme_file;
		}

		// 2. Plugin templates directory.
		$plugin_file = DEVCHEFPRESS_TEMPLATES_PATH . $template_name;
		if ( file_exists( $plugin_file ) ) {
			return $plugin_file;
		}

		return null;
	}

	/**
	 * Render a template part.
	 *
	 * @param string               $part       Part name (without .php).
	 * @param array<string, mixed> $variables
	 */
	public function render_part( string $part, array $variables = [] ): void {
		$this->render( 'parts/' . $part, $variables );
	}
}
