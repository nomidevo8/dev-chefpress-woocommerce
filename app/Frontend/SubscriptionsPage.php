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


		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Subscription Management Dashboard</title>
			<script src="https://cdn.tailwindcss.com"></script>
			<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
			<style>
				:root {
					--brand: #83C11F !important;
					--brand-dark: #6ea517 !important;
					--brand-light: #eef7d9 !important;

					--text-main: #2B303B !important;
					--text-muted: #6b7280 !important;

					--bg-light: #f7f8fa !important;
					--border: #e5e7eb !important;
					--white: #ffffff !important;

					--font-sans: 'Inter', system-ui, -apple-system, sans-serif !important;
				}
				body {
					font-family: var(--font-sans) !important;
					background-color: var(--bg-light) !important;
					color: var(--text-main) !important;
					margin: 0 !important;
					padding: 0 !important;
				}

				.brand-text { color: var(--brand) !important; }
				.brand-bg { background-color: var(--brand) !important; }
				.brand-light-bg { background-color: var(--brand-light) !important; }
				
				.modal-overlay {
					background-color: rgba(0, 0, 0, 0.6) !important;
					backdrop-filter: blur(4px) !important;
				}

				.custom-shadow {
					box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
				}

				.table-container {
					background: white !important;
					border-radius: 12px !important;
					border: 1px solid var(--border) !important;
					overflow: hidden !important;
				}

				/* Custom Scrollbar */
				.modal-content::-webkit-scrollbar {
					width: 6px !important;
				}
				.modal-content::-webkit-scrollbar-track {
					background: transparent !important;
				}
				.modal-content::-webkit-scrollbar-thumb {
					background: #e5e7eb !important;
					border-radius: 10px !important;
				}

				@keyframes fadeIn {
					from { opacity: 0 !important; transform: scale(0.95) translateY(10px) !important; }
					to { opacity: 1 !important; transform: scale(1) translateY(0) !important; }
				}
				.animate-modal {
					animation: fadeIn 0.3s ease-out forwards !important;
				}
			</style>
		</head>
		<body class="p-4 md:p-10">

			<div class="max-w-6xl mx-auto">
				<header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
					<div>
						<h1 class="text-2xl font-bold text-gray-900">My Subscriptions</h1>
						<p class="text-gray-500 text-sm">Manage your active meal plans and schedules</p>
					</div>
				</header>

				<!-- List Table View -->
				<div class="table-container shadow-sm overflow-x-auto">
					<table class="w-full text-left border-collapse">
						<thead>
							<tr class="bg-gray-50 border-b border-gray-100">
								<th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-400">Plan Name</th>
								<th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-400">Status</th>
								<th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-400">Start Date</th>
								<th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-400 text-right">Actions</th>
							</tr>
						</thead>
						<tbody id="subscription-table-body">
							<!-- Rows will be injected by JS -->
						</tbody>
					</table>
				</div>
			</div>

			<!-- Modal Container -->
			<div id="modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-0 sm:p-4">
				<!-- Overlay -->
				<div onclick="toggleModal(false)" class="modal-overlay absolute inset-0"></div>

				<!-- Modal Box -->
				<div class="relative bg-white w-full max-w-[800px] h-full sm:h-auto sm:max-h-[90vh] sm:rounded-[12px] custom-shadow flex flex-col overflow-hidden animate-modal">
					
					<!-- Header -->
					<header class="sticky top-0 z-10 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
						<div class="flex items-center gap-3">
							<h2 class="text-xl font-bold text-gray-800">Subscription Details</h2>
							<span id="modal-status" class="brand-light-bg brand-text px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Active</span>
						</div>
						<button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 transition-colors">
							<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
							</svg>
						</button>
					</header>

					<!-- Content -->
					<div class="modal-content flex-1 overflow-y-auto p-6 space-y-8">
						
						<!-- Section 1: Plan Info -->
						<section>
							<div class="flex items-center gap-2 mb-4 brand-text">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
								</svg>
								<h3 class="text-xs font-bold uppercase tracking-widest">Plan Information</h3>
							</div>
							<div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Plan Name</p>
									<p id="modal-plan-name" class="text-sm font-semibold text-gray-700">-</p>
								</div>
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Diet Type</p>
									<p id="modal-diet-type" class="text-sm font-semibold text-gray-700">-</p>
								</div>
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Duration</p>
									<p id="modal-duration" class="text-sm font-semibold text-gray-700">-</p>
								</div>
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Start Date</p>
									<p id="modal-start-date" class="text-sm font-semibold text-gray-700">-</p>
								</div>
								<div class="col-span-2">
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Delivery Slot</p>
									<p id="modal-delivery-slot" class="text-sm font-semibold text-gray-700">-</p>
								</div>
							</div>
						</section>

						<!-- Section 2: Personal Info -->
						<section>
							<div class="flex items-center gap-2 mb-4 brand-text">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
								</svg>
								<h3 class="text-xs font-bold uppercase tracking-widest">Personal Details</h3>
							</div>
							<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-6">
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Goal</p>
									<p id="modal-goal" class="text-sm font-semibold text-gray-700">-</p>
								</div>
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Age</p>
									<p id="modal-age" class="text-sm font-semibold text-gray-700">-</p>
								</div>
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Weight</p>
									<p id="modal-weight" class="text-sm font-semibold text-gray-700">-</p>
								</div>
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Height</p>
									<p id="modal-height" class="text-sm font-semibold text-gray-700">-</p>
								</div>
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Gender</p>
									<p id="modal-gender" class="text-sm font-semibold text-gray-700">-</p>
								</div>
								<div>
									<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Activity</p>
									<p id="modal-activity" class="text-sm font-semibold text-gray-700">-</p>
								</div>
							</div>
						</section>

						<!-- Section 3: Delivery Address -->
						<section>
							<div class="flex items-center gap-2 mb-4 brand-text">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
								</svg>
								<h3 class="text-xs font-bold uppercase tracking-widest">Delivery Address</h3>
							</div>
							<div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
								<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
									<div>
										<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Address Type</p>
										<p id="modal-address-type" class="text-sm font-semibold text-gray-700">-</p>
									</div>
									<div>
										<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Building / Floor / Flat</p>
										<p id="modal-address-full" class="text-sm font-semibold text-gray-700">-</p>
									</div>
									<div class="sm:col-span-2">
										<p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Additional Details</p>
										<p id="modal-address-details" class="text-sm font-semibold text-gray-700">-</p>
									</div>
								</div>
							</div>
						</section>

						<!-- Section 4: Meal Plan Preview -->
						<section>
							<div class="flex items-center gap-2 mb-4 brand-text">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
								</svg>
								<h3 class="text-xs font-bold uppercase tracking-widest">Meal Plan Preview</h3>
							</div>
							<div id="modal-meal-plan" class="space-y-3">
								<!-- Meal plan days will be injected here -->
							</div>
						</section>

						<!-- Section 5: Pricing -->
						<section>
							<div class="flex items-center gap-2 mb-4 brand-text">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
								</svg>
								<h3 class="text-xs font-bold uppercase tracking-widest">Pricing Summary</h3>
							</div>
							<div class="bg-emerald-50/30 border border-emerald-100 rounded-2xl p-6">
								<div class="space-y-3">
									<div class="flex justify-between text-sm text-gray-500">
										<span>Subtotal</span>
										<span id="modal-subtotal" class="font-semibold text-gray-700">-</span>
									</div>
									<div class="flex justify-between text-sm text-gray-500">
										<span>Plan Discount</span>
										<span id="modal-plan-discount" class="font-semibold brand-text">-</span>
									</div>
									<div class="flex justify-between text-sm text-gray-500">
										<span>Promo Discount</span>
										<span id="modal-promo-discount" class="font-semibold brand-text">-</span>
									</div>
									<div class="pt-4 mt-4 border-t border-emerald-100 flex justify-between items-center">
										<span class="text-lg font-bold text-gray-800">Total Amount</span>
										<span id="modal-total" class="text-2xl font-bold brand-text">-</span>
									</div>
								</div>
							</div>
						</section>

					</div>

					<!-- Footer Actions -->
					<footer class="sticky bottom-0 z-10 bg-white border-t border-gray-100 p-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
						<button class="w-full sm:w-auto px-8 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition-colors">
							Edit Subscription
						</button>
						<button class="w-full sm:w-auto px-8 py-3 rounded-xl brand-bg text-white font-bold text-sm shadow-lg shadow-emerald-100 hover:opacity-90 transition-all">
							Book Current Week
						</button>
					</footer>

				</div>
			</div>

			<script>
				const SAMPLE_DATA = [
					{
						id: 'SUB-001',
						status: 'Active',
						planName: 'Weight Loss Pro',
						dietType: 'Keto Friendly',
						duration: '4 Weeks',
						startDate: 'Apr 15, 2024',
						deliverySlot: '07:00 AM - 09:00 AM',
						personalInfo: { goal: 'Weight Loss', age: 28, weight: '75 kg', height: '175 cm', gender: 'Male', activity: 'Moderate' },
						address: { type: 'Home', full: 'Emerald Tower, 12th Floor, Flat 1204', details: 'Near the main gate, call on arrival' },
						mealPlan: [
							{ day: 'Monday', meals: [{type: 'LUNCH', name: 'Grilled Salmon with Asparagus'}, {type: 'DINNER', name: 'Keto Beef Stir-fry'}, {type: 'SNACK', name: 'Mixed Nuts'}] },
							{ day: 'Tuesday', meals: [{type: 'LUNCH', name: 'Chicken Avocado Salad'}, {type: 'DINNER', name: 'Zucchini Noodles'}] }
						],
						pricing: { subtotal: '$299.00', planDiscount: '-$30.00', promoDiscount: '-$15.00', total: '$254.00' }
					},
					{
						id: 'SUB-002',
						status: 'Paused',
						planName: 'Muscle Gain Elite',
						dietType: 'High Protein',
						duration: '8 Weeks',
						startDate: 'Mar 01, 2024',
						deliverySlot: '08:00 AM - 10:00 AM',
						personalInfo: { goal: 'Muscle Gain', age: 32, weight: '82 kg', height: '180 cm', gender: 'Male', activity: 'High' },
						address: { type: 'Office', full: 'Tech Hub, 4th Floor, Suite 402', details: 'Leave at reception' },
						mealPlan: [
							{ day: 'Monday', meals: [{type: 'LUNCH', name: 'Steak and Sweet Potato'}, {type: 'DINNER', name: 'Turkey Meatballs'}] }
						],
						pricing: { subtotal: '$549.00', planDiscount: '-$50.00', promoDiscount: '$0.00', total: '$499.00' }
					}
				];

				function renderTable() {
					const tbody = document.getElementById('subscription-table-body');
					tbody.innerHTML = SAMPLE_DATA.map(sub => `
						<tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors group">
							<td class="px-6 py-4">
								<p class="font-bold text-gray-800">${sub.planName}</p>
								<p class="text-xs text-gray-400">${sub.dietType}</p>
							</td>
							<td class="px-6 py-4">
								<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
									sub.status === 'Active' ? 'brand-light-bg brand-text' : 'bg-amber-50 text-amber-600'
								}">
									${sub.status}
								</span>
							</td>
							<td class="px-6 py-4 text-sm text-gray-600">${sub.startDate}</td>
							<td class="px-6 py-4 text-right">
								<div class="flex justify-end gap-2">
									<button onclick="viewDetails('${sub.id}')" class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="View Details">
										<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
										</svg>
									</button>
									<button class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Edit">
										<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
										</svg>
									</button>
									<button class="p-2 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-all" title="Book Week Recipes">
										<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
										</svg>
									</button>
								</div>
							</td>
						</tr>
					`).join('');
				}

				function viewDetails(id) {
					const sub = SAMPLE_DATA.find(s => s.id === id);
					if (!sub) return;

					// Fill Modal
					document.getElementById('modal-status').innerText = sub.status;
					document.getElementById('modal-plan-name').innerText = sub.planName;
					document.getElementById('modal-diet-type').innerText = sub.dietType;
					document.getElementById('modal-duration').innerText = sub.duration;
					document.getElementById('modal-start-date').innerText = sub.startDate;
					document.getElementById('modal-delivery-slot').innerText = sub.deliverySlot;
					
					document.getElementById('modal-goal').innerText = sub.personalInfo.goal;
					document.getElementById('modal-age').innerText = sub.personalInfo.age;
					document.getElementById('modal-weight').innerText = sub.personalInfo.weight;
					document.getElementById('modal-height').innerText = sub.personalInfo.height;
					document.getElementById('modal-gender').innerText = sub.personalInfo.gender;
					document.getElementById('modal-activity').innerText = sub.personalInfo.activity;

					document.getElementById('modal-address-type').innerText = sub.address.type;
					document.getElementById('modal-address-full').innerText = sub.address.full;
					document.getElementById('modal-address-details').innerText = sub.address.details;

					document.getElementById('modal-subtotal').innerText = sub.pricing.subtotal;
					document.getElementById('modal-plan-discount').innerText = sub.pricing.planDiscount;
					document.getElementById('modal-promo-discount').innerText = sub.pricing.promoDiscount;
					document.getElementById('modal-total').innerText = sub.pricing.total;

					const mealPlanContainer = document.getElementById('modal-meal-plan');
					mealPlanContainer.innerHTML = sub.mealPlan.map(day => `
						<div class="border border-gray-100 rounded-lg overflow-hidden">
							<div class="bg-white p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition-colors" onclick="this.nextElementSibling.classList.toggle('hidden')">
								<span class="font-bold text-gray-700">${day.day}</span>
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
								</svg>
							</div>
							<div class="bg-gray-50/50 p-4 border-t border-gray-100 space-y-4">
								${day.meals.map(meal => `
									<div class="flex items-start gap-4">
										<span class="brand-light-bg brand-text text-[10px] font-bold px-2 py-0.5 rounded">${meal.type}</span>
										<p class="text-sm text-gray-600">${meal.name}</p>
									</div>
								`).join('')}
							</div>
						</div>
					`).join('');

					toggleModal(true);
				}

				function toggleModal(show) {
					const modal = document.getElementById('modal');
					if (show) {
						modal.classList.remove('hidden');
						document.body.style.overflow = 'hidden';
					} else {
						modal.classList.add('hidden');
						document.body.style.overflow = 'auto';
					}
				}

				// Close on ESC
				window.addEventListener('keydown', (e) => {
					if (e.key === 'Escape') toggleModal(false);
				});

				// Initial Render
				renderTable();
			</script>
		</body>
		</html>

		<?php
		return ob_get_clean();
	}
}
