<?php

declare( strict_types=1 );

namespace DevChefPress\Elementor\Widgets;

use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile / Account Elementor widget.
 */
class ProfileAccount extends Widget_Base {

	public function get_name(): string {
		return 'chefpress_profile_account';
	}

	public function get_title(): string {
		return __( 'ChefPress Profile Account', 'dev-chefpress' );
	}

	public function get_icon(): string {
		return 'eicon-user-circle-o';
	}

	public function get_categories(): array {
		return [ 'general' ];
	}

	public function get_style_depends(): array {
		return [ 'chefpress-elementor-profile-account' ];
	}

	public function get_script_depends(): array {
		return [ 'chefpress-elementor-profile-account' ];
	}

	protected function render(): void {
		$is_logged_in = is_user_logged_in();
		$current_user = wp_get_current_user();
		$account_url = wc_get_page_permalink( 'myaccount' );
		if ( ! $account_url ) {
			$account_url = site_url( '/my-account' );
		}
		$orders_url = wc_get_account_endpoint_url( 'orders' );
		$logout_url = wp_logout_url( $account_url );
		$panel_id = 'cp-profile-account-panel-' . wp_rand( 1000, 9999 );

		echo '<div class="cp-profile-account-widget">';
		echo '<button type="button" class="cp-profile-account-toggle" aria-expanded="false" aria-controls="' . esc_attr( $panel_id ) . '" aria-label="' . esc_attr__( 'Account menu', 'dev-chefpress' ) . '">';
		echo '<span class="cp-profile-account-icon" aria-hidden="true">👤</span>';
		echo '</button>';
		echo '<div class="cp-profile-account-panel" id="' . esc_attr( $panel_id ) . '" role="menu">';

		if ( $is_logged_in ) {
			$display_name = $current_user->display_name ?: $current_user->user_login;
			echo '<div class="cp-profile-account-summary">';
			echo '<span class="cp-profile-account-name">' . esc_html( $display_name ) . '</span>';
			echo '</div>';
			echo '<a class="cp-profile-account-link" href="' . esc_url( $account_url ) . '">' . esc_html__( 'My Account', 'dev-chefpress' ) . '</a>';
			echo '<a class="cp-profile-account-link" href="' . esc_url( $orders_url ) . '">' . esc_html__( 'Orders', 'dev-chefpress' ) . '</a>';
			echo '<a class="cp-profile-account-link cp-profile-account-link-logout" href="' . esc_url( $logout_url ) . '">' . esc_html__( 'Logout', 'dev-chefpress' ) . '</a>';
		} else {
			echo '<a class="cp-profile-account-link" href="' . esc_url( $account_url ) . '">' . esc_html__( 'Login', 'dev-chefpress' ) . '</a>';
			echo '<a class="cp-profile-account-link" href="' . esc_url( $account_url ) . '">' . esc_html__( 'Register', 'dev-chefpress' ) . '</a>';
		}

		echo '</div>';
		echo '</div>';
	}
}
