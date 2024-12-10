<?php
/**
 * Admin class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Admin {

	public static function init() {
		PUM_Admin_BlockEditor::init();
		PUM_Admin_Pages::init();
		PUM_Admin_Ajax::init();
		PUM_Admin_Assets::init();
		PUM_Admin_Notices::init();
		PUM_Admin_Popups::init();
		PUM_Admin_Themes::init();
		PUM_Admin_Subscribers::init();
		PUM_Admin_Settings::init();
		PUM_Admin_Tools::init();
		PUM_Admin_Shortcode_UI::init();
		PUM_Upsell::init();
		PUM_Admin_Onboarding::init();

		add_filter( 'user_has_cap', [ __CLASS__, 'prevent_default_theme_deletion' ], 10, 3 );
		add_action( 'admin_init', [ __CLASS__, 'after_install' ] );
		add_action( 'admin_head', [ __CLASS__, 'clean_ui' ] );
	}

	/**
	 * Prevent user from deleting the current default popup_theme
	 *
	 * @param $allcaps
	 * @param $caps
	 * @param $args
	 *
	 * @return mixed
	 */
	public static function prevent_default_theme_deletion( $allcaps, $caps, $args ) {
		global $wpdb;
		if ( isset( $args[0] ) && isset( $args[2] ) && pum_get_option( 'default_theme' ) === $args[2] && 'delete_post' === $args[0] ) {
			$allcaps[ $caps[0] ] = false;
		}

		return $allcaps;
	}

	/**
	 * Post-installation
	 *
	 * Runs just after plugin installation and exposes the
	 * popmake_after_install hook.
	 *
	 * @since 1.0
	 * @return void
	 */
	public static function after_install() {

		if ( ! is_admin() ) {
			return;
		}

		$already_installed = get_option( '_pum_installed' );

		// Exit if not in admin or the transient doesn't exist
		if ( false === $already_installed ) {
			do_action( 'pum_after_install' );

			update_option( '_pum_installed', true );
		}
	}


	/**
	 * Cleans the UI area within our admin pages
	 *
	 * @since 1.12
	 *
	 * @return void
	 */
	public static function clean_ui() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return;
		}

		// Elementor shows an upsell notice for their popup builder targeting only our admin area. This removes that.
		if ( class_exists( 'Elementor\Core\Admin\Admin' ) && pum_is_admin_page() ) {
			$instance = \Elementor\Plugin::instance();
			if ( isset( $instance->admin ) && is_a( $instance->admin, '\Elementor\Core\Admin\Admin' ) && method_exists( $instance->admin, 'get_component' ) ) {
				$notices = $instance->admin->get_component( 'admin-notices' );
				if ( false !== $notices && is_a( $notices, '\Elementor\Core\Admin\Admin_Notices' ) ) {
					remove_action( 'admin_notices', [ $notices, 'admin_notices' ], 20 );
				}
			}
		}
	}
}
