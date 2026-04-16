<?php
declare( strict_types=1 );

namespace DevChefPress\Admin;

use DevChefPress\Hooks\Loader;
use DevChefPress\Services\PluginSettings;
use DevChefPress\Services\SubscriptionManager;
use DevChefPress\Models\UserSubscription;

/**
 * Subscriptions management page as a submenu under the ChefPress CPT.
 */
final class SubscriptionsAdminPage {

	private const SUBMENU_SLUG = 'chefpress-subscriptions';
	private const DETAILS_SLUG = 'chefpress-subscription-details';
	private const ACTION_MARK_REFUND_COMPLETED = 'chefpress_mark_refund_completed';

	public function __construct( Loader $loader ) {
		$loader->add_action( 'admin_menu', $this, 'register_menu', 20 );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_styles' );
		$loader->add_action( 'admin_post_' . self::ACTION_MARK_REFUND_COMPLETED, $this, 'handle_mark_refund_completed' );
	}

	/**
	 * Parent menu is the CPT list
	 */
	private static function parent_slug(): string {
		return 'edit.php?post_type=' . PluginSettings::CPT_SLUG;
	}

	public static function subscriptions_url(): string {
		return admin_url( self::parent_slug() . '&page=' . self::SUBMENU_SLUG );
	}

	public static function subscription_details_url( int $subscription_id ): string {
		return admin_url( 'admin.php?page=' . self::DETAILS_SLUG . '&id=' . $subscription_id );
	}

	/**
	 * Register submenu pages
	 */
	public function register_menu(): void {
		// Main subscriptions list
		add_submenu_page(
			self::parent_slug(),
			__( 'Subscriptions', 'dev-chefpress' ),
			__( 'Subscriptions', 'dev-chefpress' ),
			'manage_woocommerce',
			self::SUBMENU_SLUG,
			[ $this, 'render_list' ]
		);

		// Hidden details page
		add_submenu_page(
			'',
			__( 'Subscription Details', 'dev-chefpress' ),
			__( 'Subscription Details', 'dev-chefpress' ),
			'manage_woocommerce',
			self::DETAILS_SLUG,
			[ $this, 'render_details' ]
		);
	}

	/**
	 * Enqueue admin styles
	 */
	public function enqueue_styles( string $hook ): void {
		if ( strpos( $hook, self::SUBMENU_SLUG ) === false && strpos( $hook, self::DETAILS_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style(
			'chefpress-admin-subscriptions',
			DEVCHEFPRESS_URL . 'assets/css/admin-subscriptions.css',
			[],
			DEVCHEFPRESS_VERSION
		);
	}

	/**
	 * Render subscriptions list page
	 */
	public function render_list(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dev-chefpress' ) );
		}

		$page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$refund_filter = isset( $_GET['refund_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['refund_filter'] ) ) : '';
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
		$per_page = 20;

		// Get subscriptions with filters
		$result = self::get_subscriptions(
			$page,
			$per_page,
			$search,
			$status,
			$refund_filter,
			$date_from,
			$date_to
		);

		$subscriptions = $result['subscriptions'];
		$total = $result['total'];
		$total_pages = ceil( $total / $per_page );

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Subscriptions', 'dev-chefpress' ); ?></h1>

			<!-- Filters -->
			<div class="chefpress-admin-filters">
				<form method="get">
					<input type="hidden" name="post_type" value="<?php echo esc_attr( PluginSettings::CPT_SLUG ); ?>">
					<input type="hidden" name="page" value="<?php echo esc_attr( self::SUBMENU_SLUG ); ?>">

					<input type="text"
						name="s"
						placeholder="<?php echo esc_attr__( 'Search by email or name...', 'dev-chefpress' ); ?>"
						value="<?php echo esc_attr( $search ); ?>"
						class="chefpress-search-input">

					<select name="status" class="chefpress-filter-select">
						<option value=""><?php echo esc_html__( 'All Statuses', 'dev-chefpress' ); ?></option>
						<option value="active" <?php selected( $status, 'active' ); ?>>
							<?php echo esc_html__( 'Active', 'dev-chefpress' ); ?>
						</option>
						<option value="paused" <?php selected( $status, 'paused' ); ?>>
							<?php echo esc_html__( 'Paused', 'dev-chefpress' ); ?>
						</option>
						<option value="completed" <?php selected( $status, 'completed' ); ?>>
							<?php echo esc_html__( 'Completed', 'dev-chefpress' ); ?>
						</option>
						<option value="cancelled" <?php selected( $status, 'cancelled' ); ?>>
							<?php echo esc_html__( 'Cancelled', 'dev-chefpress' ); ?>
						</option>
					</select>

					<select name="refund_filter" class="chefpress-filter-select">
						<option value=""><?php echo esc_html__( 'All Refunds', 'dev-chefpress' ); ?></option>
						<option value="has_refund" <?php selected( $refund_filter, 'has_refund' ); ?>>
							<?php echo esc_html__( 'Has Pending Refund', 'dev-chefpress' ); ?>
						</option>
						<option value="no_refund" <?php selected( $refund_filter, 'no_refund' ); ?>>
							<?php echo esc_html__( 'No Pending Refund', 'dev-chefpress' ); ?>
						</option>
					</select>

					<input type="date"
						name="date_from"
						value="<?php echo esc_attr( $date_from ); ?>"
						class="chefpress-filter-date">

					<input type="date"
						name="date_to"
						value="<?php echo esc_attr( $date_to ); ?>"
						class="chefpress-filter-date">

					<button type="submit" class="button button-primary">
						<?php echo esc_html__( 'Filter', 'dev-chefpress' ); ?>
					</button>

					<?php if ( $search || $status || $refund_filter || $date_from || $date_to ) : ?>
						<a href="<?php echo esc_url( self::subscriptions_url() ); ?>" class="button">
							<?php echo esc_html__( 'Reset Filters', 'dev-chefpress' ); ?>
						</a>
					<?php endif; ?>
				</form>
			</div>

			<!-- Subscriptions Table -->
			<div class="chefpress-subscriptions-table-wrapper">
				<?php if ( empty( $subscriptions ) ) : ?>
					<p class="submittable"><?php echo esc_html__( 'No subscriptions found.', 'dev-chefpress' ); ?></p>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'ID', 'dev-chefpress' ); ?></th>
								<th><?php echo esc_html__( 'User', 'dev-chefpress' ); ?></th>
								<th><?php echo esc_html__( 'Plan Name', 'dev-chefpress' ); ?></th>
								<th><?php echo esc_html__( 'Current Price', 'dev-chefpress' ); ?></th>
								<th><?php echo esc_html__( 'Total Paid', 'dev-chefpress' ); ?></th>
								<th><?php echo esc_html__( 'Refund Pending', 'dev-chefpress' ); ?></th>
								<th><?php echo esc_html__( 'Status', 'dev-chefpress' ); ?></th>
								<th><?php echo esc_html__( 'Created', 'dev-chefpress' ); ?></th>
								<th><?php echo esc_html__( 'Actions', 'dev-chefpress' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $subscriptions as $sub_data ) : 
								$subscription = UserSubscription::get_by_id( intval( $sub_data->id ) );
								if ( ! $subscription ) {
									continue;
								}
							?>
								<tr>
									<td><strong><?php echo intval( $subscription->get_id() ); ?></strong></td>
									<td>
										<?php
											$user = get_user_by( 'id', intval( $subscription->get_user_id() ) );
											if ( $user ) {
												echo esc_html( $user->display_name ) . '<br>';
												echo '<small>' . esc_html( $user->user_email ) . '</small>';
											} else {
												echo esc_html__( 'User Deleted', 'dev-chefpress' );
											}
										?>
									</td>
									<td><?php echo esc_html( $subscription->get_plan_name() ); ?></td>
									<td>
										<strong>
											&dollar;<?php echo number_format( floatval( $subscription->get_current_price() ), 2 ); ?>
										</strong>
									</td>
									<td>
										&dollar;<?php echo number_format( floatval( $subscription->get_total_paid() ), 2 ); ?>
									</td>
									<td>
										<?php
											$refund = floatval( $subscription->get_total_refund_pending() ?? 0 );
											if ( $refund > 0 ) {
												echo '<span class="chefpress-badge-warning">';
												echo '&dollar;' . number_format( $refund, 2 );
												echo '</span>';
											} else {
												echo '—';
											}
										?>
									</td>
									<td>
										<span class="chefpress-badge chefpress-badge-<?php echo esc_attr( $subscription->get_status() ); ?>">
											<?php echo esc_html( ucfirst( $subscription->get_status() ) ); ?>
										</span>
									</td>
									<td>
										<small>
											<?php
												echo esc_html( wp_date(
													'M j, Y',
													strtotime( $subscription->get_created_at() )
												) );
											?>
										</small>
									</td>
									<td>
										<a href="<?php echo esc_url( self::subscription_details_url( intval( $subscription->get_id() ) ) ); ?>"
											class="button button-small">
											<?php echo esc_html__( 'View Details', 'dev-chefpress' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<!-- Pagination -->
					<?php if ( $total_pages > 1 ) : ?>
						<div class="tablenav">
							<div class="tablenav-pages">
								<span class="displaying-num">
									<?php
										// translators: %d is number of subscriptions
										printf(
											esc_html__( '%d subscriptions', 'dev-chefpress' ),
											intval( $total )
										);
									?>
								</span>
								<span class="pagination-links">
									<?php
										$current_url = self::subscriptions_url();
										if ( $search ) {
											$current_url .= '&s=' . urlencode( $search );
										}
										if ( $status ) {
											$current_url .= '&status=' . urlencode( $status );
										}
										if ( $refund_filter ) {
											$current_url .= '&refund_filter=' . urlencode( $refund_filter );
										}
										if ( $date_from ) {
											$current_url .= '&date_from=' . urlencode( $date_from );
										}
										if ( $date_to ) {
											$current_url .= '&date_to=' . urlencode( $date_to );
										}

										// First page
										if ( $page > 1 ) {
											echo '<a class="first-page button" href="' . esc_url( $current_url ) . '">';
											echo '&laquo;';
											echo '</a>';
										}

										// Previous page
										if ( $page > 1 ) {
											$prev_url = $current_url . '&paged=' . ( $page - 1 );
											echo '<a class="prev-page button" href="' . esc_url( $prev_url ) . '">';
											echo '&lsaquo;';
											echo '</a>';
										}

										// Page info
										echo '<span class="paging-input">';
										printf(
											esc_html__( '%1$d of %2$d', 'dev-chefpress' ),
											intval( $page ),
											intval( $total_pages )
										);
										echo '</span>';

										// Next page
										if ( $page < $total_pages ) {
											$next_url = $current_url . '&paged=' . ( $page + 1 );
											echo '<a class="next-page button" href="' . esc_url( $next_url ) . '">';
											echo '&rsaquo;';
											echo '</a>';
										}

										// Last page
										if ( $page < $total_pages ) {
											$last_url = $current_url . '&paged=' . $total_pages;
											echo '<a class="last-page button" href="' . esc_url( $last_url ) . '">';
											echo '&raquo;';
											echo '</a>';
										}
									?>
								</span>
							</div>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render subscription details page
	 */
	public function render_details(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dev-chefpress' ) );
		}

		$subscription_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		if ( ! $subscription_id ) {
			wp_die( esc_html__( 'Invalid subscription ID.', 'dev-chefpress' ) );
		}

		$subscription = UserSubscription::get_by_id( $subscription_id );
		if ( ! $subscription ) {
			wp_die( esc_html__( 'Subscription not found.', 'dev-chefpress' ) );
		}

		$user = get_user_by( 'id', intval( $subscription->get_user_id() ) );
		$parent_order_id = $subscription->get_parent_order_id();
		$parent_order = $parent_order_id ? wc_get_order( $parent_order_id ) : null;

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Subscription Details', 'dev-chefpress' ); ?></h1>

			<div class="chefpress-details-container">
				<?php $this->render_admin_notice(); ?>
				<!-- User Info -->
				<div class="chefpress-detail-section">
					<h2><?php echo esc_html__( 'User Information', 'dev-chefpress' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><?php echo esc_html__( 'Subscription ID', 'dev-chefpress' ); ?></th>
							<td><?php echo intval( $subscription->get_id() ); ?></td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'User', 'dev-chefpress' ); ?></th>
							<td>
								<?php
									if ( $user ) {
										echo esc_html( $user->display_name ) . ' (' . esc_html( $user->user_email ) . ')';
									}
								?>
							</td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Plan Name', 'dev-chefpress' ); ?></th>
							<td><?php echo esc_html( $subscription->get_plan_name() ); ?></td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Status', 'dev-chefpress' ); ?></th>
							<td>
								<span class="chefpress-badge chefpress-badge-<?php echo esc_attr( $subscription->get_status() ); ?>">
									<?php echo esc_html( ucfirst( $subscription->get_status() ) ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Created', 'dev-chefpress' ); ?></th>
							<td><?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $subscription->get_created_at() ) ) ); ?></td>
						</tr>
					</table>
				</div>

				<!-- Pricing Info -->
				<div class="chefpress-detail-section">
					<h2><?php echo esc_html__( 'Pricing', 'dev-chefpress' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><?php echo esc_html__( 'Original Price', 'dev-chefpress' ); ?></th>
							<td>&dollar;<?php echo number_format( floatval( $subscription->get_original_price() ), 2 ); ?></td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Current Price', 'dev-chefpress' ); ?></th>
							<td><strong>&dollar;<?php echo number_format( floatval( $subscription->get_current_price() ), 2 ); ?></strong></td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Total Paid', 'dev-chefpress' ); ?></th>
							<td>&dollar;<?php echo number_format( floatval( $subscription->get_total_paid() ), 2 ); ?></td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Refund Pending', 'dev-chefpress' ); ?></th>
							<td>
								<?php
									$refund = floatval( $subscription->get_total_refund_pending() ?? 0 );
									if ( $refund > 0 ) {
										echo '<span class="chefpress-badge-warning">&dollar;' . number_format( $refund, 2 ) . '</span>';
									} else {
										echo '—';
									}
								?>
							</td>
						</tr>
					</table>
				</div>

				<?php if ( $subscription->get_total_refund_pending() > 0 ) : ?>
				<div class="chefpress-detail-section">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'chefpress_mark_refund_completed_action', 'chefpress_mark_refund_completed_nonce' ); ?>
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_MARK_REFUND_COMPLETED ); ?>">
						<input type="hidden" name="subscription_id" value="<?php echo esc_attr( $subscription->get_id() ); ?>">
						<button type="submit" class="button button-primary">
							<?php echo esc_html__( 'Mark Refund as Completed', 'dev-chefpress' ); ?>
						</button>
					</form>
				</div>
			<?php endif; ?>

			<!-- Parent Order -->
				<?php if ( $parent_order ) : ?>
					<div class="chefpress-detail-section">
						<h2><?php echo esc_html__( 'Parent Order', 'dev-chefpress' ); ?></h2>
						<p>
							<a href="<?php echo esc_url( $parent_order->get_edit_order_url() ); ?>" class="button">
								<?php printf(
									esc_html__( 'View Order #%d', 'dev-chefpress' ),
									intval( $parent_order->get_id() )
								); ?>
							</a>
						</p>
					</div>
				<?php endif; ?>

				<!-- Delivery Details -->
				<?php
					$delivery = $subscription->get_delivery_details();
					if ( ! empty( $delivery ) ) :
				?>
					<div class="chefpress-detail-section">
						<h2><?php echo esc_html__( 'Delivery Details', 'dev-chefpress' ); ?></h2>
						<table class="form-table">
							<?php if ( isset( $delivery['startDate'] ) ) : ?>
								<tr>
									<th><?php echo esc_html__( 'Start Date', 'dev-chefpress' ); ?></th>
									<td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $delivery['startDate'] ) ) ); ?></td>
								</tr>
							<?php endif; ?>
							<?php if ( isset( $delivery['deliverySlot'] ) ) : ?>
								<tr>
									<th><?php echo esc_html__( 'Delivery Slot', 'dev-chefpress' ); ?></th>
									<td><?php echo esc_html( $delivery['deliverySlot'] ); ?></td>
								</tr>
							<?php endif; ?>
						</table>
					</div>
				<?php endif; ?>

				<!-- Subscription History -->
				<?php
					$history = SubscriptionManager::get_history( $subscription->get_id() );
					if ( ! empty( $history ) ) :
				?>
					<div class="chefpress-detail-section">
						<h2><?php echo esc_html__( 'Subscription History', 'dev-chefpress' ); ?></h2>
						<div class="chefpress-history-list">
							<?php foreach ( $history as $record ) : ?>
								<div class="chefpress-history-item">
									<div class="chefpress-history-header">
										<span class="chefpress-history-type">
											<?php echo esc_html( self::format_change_type( $record['change_type'] ?? '' ) ); ?>
										</span>
										<span class="chefpress-history-date">
											<?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $record['created_at'] ) ) ); ?>
										</span>
									</div>
									<div class="chefpress-history-details">
										<?php if ( isset( $record['old_price'] ) && isset( $record['new_price'] ) ) : ?>
											<div>
												<strong><?php echo esc_html__( 'Price Change:', 'dev-chefpress' ); ?></strong>
												&dollar;<?php echo number_format( (float) $record['old_price'], 2 ); ?>
												→
												&dollar;<?php echo number_format( (float) $record['new_price'], 2 ); ?>
												<?php
													$diff = (float) $record['difference'];
													if ( $diff > 0 ) {
														echo '<span class="chefpress-price-increase"> (+&dollar;' . number_format( $diff, 2 ) . ')</span>';
													} elseif ( $diff < 0 ) {
														echo '<span class="chefpress-price-decrease"> (-&dollar;' . number_format( abs( $diff ), 2 ) . ')</span>';
													}
												?>
											</div>
										<?php endif; ?>

										<?php if ( isset( $record['refund_amount'] ) && $record['refund_amount'] > 0 ) : ?>
											<div>
												<strong><?php echo esc_html__( 'Refund Amount:', 'dev-chefpress' ); ?></strong>
												&dollar;<?php echo number_format( (float) $record['refund_amount'], 2 ); ?>
												(<?php echo esc_html( $record['refund_status'] ?? 'pending' ); ?>)
											</div>
										<?php endif; ?>

										<?php if ( ! empty( $record['notes'] ) ) : ?>
											<div>
												<strong><?php echo esc_html__( 'Notes:', 'dev-chefpress' ); ?></strong>
												<?php echo wp_kses_post( $record['notes'] ); ?>
											</div>
										<?php endif; ?>

										<?php if ( isset( $record['order_id'] ) && $record['order_id'] > 0 ) : ?>
											<div>
												<strong><?php echo esc_html__( 'Related Order:', 'dev-chefpress' ); ?></strong>
												<a href="<?php echo esc_url( get_edit_post_link( $record['order_id'] ) ); ?>" target="_blank">
													#<?php echo intval( $record['order_id'] ); ?>
												</a>
											</div>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<p>
				<a href="<?php echo esc_url( self::subscriptions_url() ); ?>" class="button">
					<?php echo esc_html__( 'Back to Subscriptions', 'dev-chefpress' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Get subscriptions with pagination, search, and filters
	 */
	private static function get_subscriptions(
		int $page = 1,
		int $per_page = 20,
		string $search = '',
		string $status = '',
		string $refund_filter = '',
		string $date_from = '',
		string $date_to = ''
	): array {
		global $wpdb;

		$table = $wpdb->prefix . 'chefpress_user_subscriptions';
		$where = [ '1=1' ];
		$params = [];

		// Search by user email or name
		if ( $search ) {
			$where[] = $wpdb->prepare(
				"(
					SELECT user_email FROM {$wpdb->users} u 
					WHERE u.ID = {$table}.user_id AND u.user_email LIKE %s
				) OR (
					SELECT display_name FROM {$wpdb->users} u 
					WHERE u.ID = {$table}.user_id AND u.display_name LIKE %s
				)"
			);
			$search_param = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $search_param;
			$params[] = $search_param;
		}

		// Filter by status
		if ( $status ) {
			$where[] = $wpdb->prepare( 'status = %s', $status );
		}

		// Filter by refund status
		if ( $refund_filter === 'has_refund' ) {
			$where[] = 'total_refund_pending > 0';
		} elseif ( $refund_filter === 'no_refund' ) {
			$where[] = '(total_refund_pending IS NULL OR total_refund_pending = 0)';
		}

		// Filter by date range
		if ( $date_from ) {
			$where[] = $wpdb->prepare( 'DATE(created_at) >= %s', $date_from );
		}
		if ( $date_to ) {
			$where[] = $wpdb->prepare( 'DATE(created_at) <= %s', $date_to );
		}

		$where_clause = implode( ' AND ', $where );

		// Count total
		$total_query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
		if ( ! empty( $params ) ) {
			$total_query = $wpdb->prepare( $total_query, ...$params );
		}
		$total = (int) $wpdb->get_var( $total_query );

		// Get subscriptions
		$offset = ( $page - 1 ) * $per_page;
		$query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		
		// Add limit and offset params
		$query_params = $params;
		$query_params[] = $per_page;
		$query_params[] = $offset;

		$subscriptions = $wpdb->get_results(
			$wpdb->prepare( $query, ...$query_params )
		);

		return [
			'subscriptions' => $subscriptions,
			'total' => $total,
		];
	}

	/**
	 * Format change type for display
	 */
	public function handle_mark_refund_completed(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'dev-chefpress' ) );
		}

		check_admin_referer( 'chefpress_mark_refund_completed_action', 'chefpress_mark_refund_completed_nonce' );

		$subscription_id = isset( $_POST['subscription_id'] ) ? intval( wp_unslash( $_POST['subscription_id'] ) ) : 0;
		if ( $subscription_id <= 0 ) {
			wp_die( esc_html__( 'Invalid subscription ID.', 'dev-chefpress' ) );
		}

		$result = SubscriptionManager::complete_refund( $subscription_id );
		if ( is_wp_error( $result ) ) {
			$redirect_url = add_query_arg(
				array(
					'page' => self::DETAILS_SLUG,
					'id' => $subscription_id,
					'refund_completed' => '0',
					'error' => urlencode( $result->get_error_message() ),
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$redirect_url = add_query_arg(
			array(
				'page' => self::DETAILS_SLUG,
				'id' => $subscription_id,
				'refund_completed' => '1',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	private function render_admin_notice(): void {
		if ( isset( $_GET['refund_completed'] ) && '1' === $_GET['refund_completed'] ) : ?>
			<div class="notice notice-success inline">
				<p><?php echo esc_html__( 'Refund marked as completed successfully', 'dev-chefpress' ); ?></p>
			</div>
		<?php elseif ( isset( $_GET['refund_completed'] ) && '0' === $_GET['refund_completed'] ) : ?>
			<div class="notice notice-error inline">
				<p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['error'] ?? __( 'Failed to mark refund as completed.', 'dev-chefpress' ) ) ) ); ?></p>
			</div>
		<?php endif;
	}

	private static function format_change_type( string $type ): string {
		$types = [
			'edit_unpaid' => '✏️ Edited (Before Payment)',
			'price_increase' => '📈 Price Increased',
			'refund' => '💰 Refund Issued',
			'price_decrease' => '📉 Price Decreased',
		];

		return $types[ $type ] ?? ucwords( str_replace( '_', ' ', $type ) );
	}
}
