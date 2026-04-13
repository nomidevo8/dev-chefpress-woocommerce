<?php
declare( strict_types=1 );

namespace DevChefPress\Frontend;

use DevChefPress\Services\SubscriptionService;
use DevChefPress\Hooks\Loader;

/**
 * Class SubscriptionsPage
 *
 * Handles rendering of the My Subscriptions page.
 */
class SubscriptionsPage {

	private Loader $loader;

	public function __construct( Loader $loader ) {
		$this->loader = $loader;
		$this->register_hooks();
	}

	private function register_hooks(): void {
		// Enqueue subscriptions CSS when needed
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_assets' );

		// Register shortcode for displaying subscriptions
		add_shortcode( 'chefpress_my_subscriptions', [ $this, 'render_subscriptions_page' ] );
	}

	/**
	 * Enqueue CSS for subscriptions page.
	 */
	public function enqueue_assets(): void {
		// Only enqueue on pages that have the subscriptions shortcode
		if ( ! $this->is_subscriptions_page() ) {
			return;
		}

		// Get theme colors
		$theme_colors = \DevChefPress\Services\PluginSettings::get_theme_colors();
		$inline_css   = ':root {' .
			'--cp_product_color-brand: ' . esc_html( $theme_colors['brand'] ) . ';' .
			'--cp_product_color-brand-light: ' . esc_html( $theme_colors['brand_light'] ) . ';' .
			'--cp_product_color-text-main: ' . esc_html( $theme_colors['text_main'] ) . ';' .
			'--cp_product_color-text-muted: ' . esc_html( $theme_colors['text_muted'] ) . ';' .
			'--cp_product_color-bg-light: ' . esc_html( $theme_colors['bg_light'] ) . ';' .
			'--cp_product_color-border: ' . esc_html( $theme_colors['border'] ) . ';' .
			'--cp_product_color-white: ' . esc_html( $theme_colors['white'] ) . ';' .
			'}';

		wp_enqueue_style(
			'dev-chefpress-my-subscriptions',
			DEVCHEFPRESS_RESOURCES_URL . 'css/my-subscriptions.css',
			[],
			DEVCHEFPRESS_VERSION
		);

		wp_add_inline_style( 'dev-chefpress-my-subscriptions', $inline_css );

		// Enqueue Lucide icons if not already enqueued
		wp_enqueue_script( 'lucide-icons', 'https://unpkg.com/lucide@latest', [], null, true );
	}

	/**
	 * Check if current page is subscriptions page.
	 *
	 * @return bool
	 */
	private function is_subscriptions_page(): bool {
		if ( ! is_singular() ) {
			return false;
		}

		global $post;
		return $post && has_shortcode( $post->post_content ?? '', 'chefpress_my_subscriptions' );
	}

	/**
	 * Render subscriptions page shortcode.
	 *
	 * @return string HTML output
	 */
	public function render_subscriptions_page(): string {
		if ( ! is_user_logged_in() ) {
			return '<div style="text-align: center; padding: 40px; background: #f9fafb; border-radius: 12px; margin: 20px 0;">' .
				'<p style="font-size: 1.125rem; color: #6b7280; margin: 0;">Please <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">login</a> to view your subscriptions.</p>' .
				'</div>';
		}

		$subscriptions = SubscriptionService::get_user_subscriptions();

		ob_start();
		?>
		<div class="dev_chefpress_my_subscriptions_container">
			<div class="dev_chefpress_subscriptions_wrapper">
				<!-- Header -->
				<div class="dev_chefpress_subscriptions_header">
					<h1 class="dev_chefpress_subscriptions_title">My Subscriptions</h1>
					<p class="dev_chefpress_subscriptions_subtitle">Manage your meal plans and deliveries</p>
				</div>

				<?php if ( empty( $subscriptions ) ) : ?>
					<!-- Empty State -->
					<div class="dev_chefpress_subscriptions_empty">
						<div class="dev_chefpress_subscriptions_empty_icon">📦</div>
						<h2 class="dev_chefpress_subscriptions_empty_title">No Subscriptions Yet</h2>
						<p class="dev_chefpress_subscriptions_empty_message">
							You don't have any meal plan subscriptions yet. Start your journey to healthier eating today!
						</p>
						<a href="<?php echo esc_url( get_site_url() . '/our-plans' ); ?>" class="dev_chefpress_subscriptions_empty_button">
							Create Your First Subscription
						</a>
					</div>
				<?php else : ?>
					<!-- Subscriptions Grid -->
					<div class="dev_chefpress_subscriptions_grid">
						<?php foreach ( $subscriptions as $subscription ) : ?>
							<div class="dev_chefpress_subscription_card">
								<div class="dev_chefpress_subscription_card_header">
									<h3 class="dev_chefpress_subscription_plan_name">
										<?php echo esc_html( $subscription['plan_name'] ); ?>
									</h3>
									<span class="dev_chefpress_subscription_status_badge <?php echo esc_attr( SubscriptionService::get_status_class( $subscription['status'] ) ); ?>">
										<svg class="dev_chefpress_subscription_icon" data-lucide="<?php echo esc_attr( SubscriptionService::get_status_icon( $subscription['status'] ) ); ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></svg>
										<?php echo esc_html( SubscriptionService::get_status_text( $subscription['status'] ) ); ?>
									</span>
								</div>

								<div class="dev_chefpress_subscription_info">
									<div class="dev_chefpress_subscription_info_item">
										<span class="dev_chefpress_subscription_info_label">Start Date</span>
										<span class="dev_chefpress_subscription_info_value">
											<?php echo esc_html( SubscriptionService::format_date( $subscription['start_date'] ) ); ?>
										</span>
									</div>
									<div class="dev_chefpress_subscription_info_item">
										<span class="dev_chefpress_subscription_info_label">
											<?php echo 'expired' === $subscription['status'] ? 'End Date' : 'Next Delivery'; ?>
										</span>
										<span class="dev_chefpress_subscription_info_value">
											<?php echo esc_html( SubscriptionService::format_date( $subscription['next_delivery'] ) ); ?>
										</span>
									</div>
									<div class="dev_chefpress_subscription_info_item">
										<span class="dev_chefpress_subscription_info_label">
											<?php echo 'expired' === $subscription['status'] ? 'Duration' : 'Frequency'; ?>
										</span>
										<span class="dev_chefpress_subscription_info_value">
											<?php echo esc_html( $subscription['frequency'] ); ?>
										</span>
									</div>
									<div class="dev_chefpress_subscription_info_item">
										<span class="dev_chefpress_subscription_info_label">Price</span>
										<span class="dev_chefpress_subscription_info_value">
											<?php echo esc_html( $subscription['price'] ); ?>
										</span>
									</div>
								</div>

								<div class="dev_chefpress_subscription_actions">
									<button class="dev_chefpress_subscription_action_primary" onclick="alert('Action: <?php echo esc_attr( SubscriptionService::get_primary_action_text( $subscription['status'] ) ); ?>')">
										<svg class="dev_chefpress_subscription_icon" data-lucide="<?php echo esc_attr( SubscriptionService::get_primary_action_icon( $subscription['status'] ) ); ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></svg>
										<?php echo esc_html( SubscriptionService::get_primary_action_text( $subscription['status'] ) ); ?>
									</button>
									<div class="dev_chefpress_subscription_actions_row">
										<button class="dev_chefpress_subscription_action_secondary" onclick="alert('View Details for: <?php echo esc_attr( $subscription['plan_name'] ); ?>')">
											<svg class="dev_chefpress_subscription_icon" data-lucide="eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
											Details
										</button>
										<button class="dev_chefpress_subscription_action_secondary" onclick="alert('Edit Subscription: <?php echo esc_attr( $subscription['plan_name'] ); ?>')">
											<svg class="dev_chefpress_subscription_icon" data-lucide="edit-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
											Edit
										</button>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
