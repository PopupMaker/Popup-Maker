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
		self::register_request_actions();
		self::register_lazy_save_hooks();

		if ( is_admin() ) {
			self::register_upsell_preview_hooks();

			if ( ! wp_doing_ajax() ) {
				PUM_Admin_Shortcode_UI::init();
			}

			PUM_Admin_Pages::init();
			PUM_Admin_Ajax::init();
			PUM_Admin_Assets::init();
			PUM_Admin_Notices::init();
			PUM_Admin_Onboarding::init();
		}

		add_filter( 'user_has_cap', [ __CLASS__, 'prevent_default_theme_deletion' ], 10, 3 );
		add_action( 'admin_init', [ __CLASS__, 'after_install' ] );
		add_action( 'admin_head', [ __CLASS__, 'clean_ui' ] );
	}

	/**
	 * Register request actions before the init dispatcher runs.
	 *
	 * Keeping these lightweight class-string callbacks here avoids loading the
	 * Tools screen while ensuring PUM_Site::actions() can dispatch them on init.
	 *
	 * @return void
	 */
	private static function register_request_actions() {
		add_action( 'pum_save_enabled_betas', [ 'PUM_Admin_Tools', 'save_enabled_betas' ] );
		add_action( 'pum_empty_error_log', [ 'PUM_Admin_Tools', 'error_log_empty' ] );
	}

	/**
	 * Register premium preview hooks without loading the screen UI implementation.
	 *
	 * Trigger and condition registries may be materialized before admin_menu, so
	 * these class-string callbacks must exist during the initial admin bootstrap.
	 *
	 * @return void
	 */
	private static function register_upsell_preview_hooks() {
		if ( defined( 'POPUP_MAKER_DISABLE_UPSELLS' ) && POPUP_MAKER_DISABLE_UPSELLS ) {
			return;
		}

		if ( \PopupMaker\plugin()->is_pro_active() ) {
			return;
		}

		add_filter( 'pum_registered_triggers', [ 'PUM_Upsell', 'register_preview_triggers' ] );
		add_filter( 'pum_registered_conditions', [ 'PUM_Upsell', 'register_preview_conditions' ] );
		add_filter( 'popup_maker/cta_types_as_array', [ 'PUM_Upsell', 'register_preview_cta_types' ] );
		add_action( 'pum_popup_analytics_metabox_after', [ 'PUM_Upsell', 'render_analytics_teaser' ] );
		add_filter( 'pum_admin_vars', [ 'PUM_Upsell', 'localize_premium_preview_data' ] );
	}

	/**
	 * Initialize admin components used by the current request.
	 *
	 * @param array<string,string> $page_slugs Resolved admin page slugs.
	 *
	 * @return void
	 */
	public static function init_request_components( $page_slugs ) {
		$post_type  = pum_typenow();
		$page       = self::requested_page();
		$page_slugs = is_array( $page_slugs ) ? $page_slugs : [];

		if ( 'popup' === $post_type ) {
			PUM_Admin_Popups::init();
		}

		if ( 'popup_theme' === $post_type ) {
			PUM_Admin_Themes::init();
		}

		if ( isset( $page_slugs['subscribers'] ) && $page_slugs['subscribers'] === $page ) {
			PUM_Admin_Subscribers::init();
		}

		if ( isset( $page_slugs['settings'] ) && $page_slugs['settings'] === $page ) {
			PUM_Admin_Settings::init();
		}

		if ( isset( $page_slugs['tools'] ) && $page_slugs['tools'] === $page ) {
			PUM_Admin_Tools::init();
		}

		if ( isset( $page_slugs['extensions'] ) && $page_slugs['extensions'] === $page ) {
			PUM_Admin_Extend::init();
		}

		if ( in_array( $post_type, [ 'popup', 'popup_theme' ], true ) || in_array( $page, $page_slugs, true ) || 'popup-maker-call-to-actions' === $page ) {
			PUM_Upsell::init();
		}
	}

	/**
	 * Register save callbacks without loading their editor implementations.
	 *
	 * @return void
	 */
	private static function register_lazy_save_hooks() {
		add_action( 'save_post', [ 'PUM_Admin_Popups', 'save' ], 10, 2 );
		add_filter( 'wp_insert_post_data', [ 'PUM_Admin_Popups', 'set_slug' ], 99, 2 );
		add_action( 'save_post', [ 'PUM_Admin_Themes', 'save' ], 10, 2 );
	}

	/**
	 * Get the requested plugin page slug.
	 *
	 * @return string
	 */
	private static function requested_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
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
