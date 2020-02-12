<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

/**
 * Class PUM_Admin_Assets
 *
 * @since 1.7.0
 */
class PUM_Admin_Assets {

	/**
	 * @var
	 */
	public static $suffix;

	/**
	 * @var
	 */
	public static $js_url;

	/**
	 * @var
	 */
	public static $css_url;

	/**
	 * @var bool Use minified libraries if SCRIPT_DEBUG is turned off.
	 */
	public static $debug;

	/**
	 * Initialize
	 */
	public static function init() {
		self::$debug   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		self::$suffix  = self::$debug ? '' : '.min';
		self::$js_url  = Popup_Maker::$URL . 'assets/js/';
		self::$css_url = Popup_Maker::$URL . 'assets/css/';

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_admin_scripts' ) );
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'maybe_localize_and_templates' ), - 1 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_admin_styles' ), 100 );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'fix_broken_extension_scripts' ), 100 );
	}

	public static function fix_broken_extension_scripts() {

		if ( wp_script_is( 'pum-mci-admin' ) && class_exists( 'PUM_MCI' ) && version_compare( PUM_MCI::$VER, '1.3.0', '<' ) && ! pum_is_settings_page() ) {
			wp_dequeue_script( 'pum-mci-admin' );
		}
	}

	/**
	 * Load Admin Scripts
	 */
	public static function register_admin_scripts() {

		$admin_vars = apply_filters( 'pum_admin_vars', apply_filters( 'pum_admin_var', array(
			'post_id'          => ! empty( $_GET['post'] ) ? intval( $_GET['post'] ) : null,
			'default_provider' => pum_get_option( 'newsletter_default_provider', 'none' ),
			'homeurl'          => home_url(),
			'I10n'             => array(
				'preview_popup'                   => __( 'Preview', 'popup-maker' ),
				'add'                             => __( 'Add', 'popup-maker' ),
				'save'                            => __( 'Save', 'popup-maker' ),
				'update'                          => __( 'Update', 'popup-maker' ),
				'insert'                          => __( 'Insert', 'popup-maker' ),
				'cancel'                          => __( 'Cancel', 'popup-maker' ),
				'confirm_delete_trigger'          => __( "Are you sure you want to delete this trigger?", 'popup-maker' ),
				'confirm_delete_cookie'           => __( "Are you sure you want to delete this cookie?", 'popup-maker' ),
				'no_cookie'                       => __( 'None', 'popup-maker' ),
				'confirm_count_reset'             => __( 'Are you sure you want to reset the open count?', 'popup-maker' ),
				'shortcode_ui_button_tooltip'     => __( 'Popup Maker Shortcodes', 'popup-maker' ),
				'error_loading_shortcode_preview' => __( 'There was an error in generating the preview', 'popup-maker' ),
			),
		) ) );

		wp_register_script( 'pum-admin-general', self::$js_url . 'admin-general' . self::$suffix . '.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-slider', 'wp-util' ), Popup_Maker::$VER, true );
		wp_localize_script( 'pum-admin-general', 'pum_admin_vars', $admin_vars );

		wp_register_script( 'pum-admin-batch', self::$js_url . 'admin-batch' . self::$suffix . '.js', array( 'pum-admin-general' ), Popup_Maker::$VER, true );
		wp_register_script( 'pum-admin-marketing', self::$js_url . 'admin-marketing' . self::$suffix . '.js', null, Popup_Maker::$VER, true );
		wp_register_script( 'pum-admin-popup-editor', self::$js_url . 'admin-popup-editor' . self::$suffix . '.js', array( 'pum-admin-general' ), Popup_Maker::$VER, true );
		wp_register_script( 'pum-admin-theme-editor', self::$js_url . 'admin-theme-editor' . self::$suffix . '.js', array( 'pum-admin-general' ), Popup_Maker::$VER, true );
		wp_register_script( 'pum-admin-settings-page', self::$js_url . 'admin-settings-page' . self::$suffix . '.js', array( 'pum-admin-general' ), Popup_Maker::$VER, true );
		wp_register_script( 'pum-admin-shortcode-ui', self::$js_url . 'admin-shortcode-ui' . self::$suffix . '.js', array( 'pum-admin-general' ), Popup_Maker::$VER, true );
		wp_register_script( 'iframe-resizer', self::$js_url . 'vendor/iframeResizer.min.js', array( 'jquery' ) );

		// @deprecated handle. Currently loads empty file and admin-general as dependency.
		wp_register_script( 'popup-maker-admin', self::$js_url . 'pum-admin-deprecated' . self::$suffix . '.js', array( 'pum-admin-general' ), Popup_Maker::$VER, true );
		wp_localize_script( 'pum-admin-general', 'pum_admin', $admin_vars );

		wp_enqueue_script( 'pum-admin-marketing' );

		if ( PUM_Utils_Upgrades::instance()->has_uncomplete_upgrades() ) {
			wp_enqueue_script( 'pum-admin-batch' );
		}

		if ( pum_is_popup_editor() ) {
			wp_enqueue_script( 'pum-admin-popup-editor' );
		}

		if ( pum_is_popup_theme_editor() ) {
			wp_enqueue_script( 'pum-admin-theme-editor' );
			wp_localize_script( 'pum-admin-theme-editor', 'pum_google_fonts', PUM_Integration_GoogleFonts::fetch_fonts() );
		}

		if ( pum_is_settings_page() ) {
			wp_enqueue_script( 'pum-admin-settings-page' );
		}

		if ( pum_is_support_page() ) {
			wp_enqueue_script( 'iframe-resizer' );
		}
	}

	/**
	 *
	 */
	public static function maybe_localize_and_templates() {
		if ( wp_script_is( 'pum-admin-general' ) || wp_script_is( 'popup-maker-admin' ) ) {
			// Register Templates.
			PUM_Admin_Templates::init();
		}

		if ( wp_script_is( 'pum-admin-batch' ) ) {
			wp_localize_script( 'pum-admin-batch', 'pum_batch_vars', array(
				'complete'              => __( 'Your all set, the upgrades completed successfully!', 'popup-maker' ),
				'unsupported_browser'   => __( 'We are sorry but your browser is not compatible with this kind of file upload. Please upgrade your browser.', 'popup-maker' ),
				'import_field_required' => 'This field must be mapped for the import to proceed.',
			) );
		}
	}

	/**
	 * Load Admin Styles
	 */
	public static function register_admin_styles() {
		$suffix = ( is_rtl() ? '-rtl' : '' ) . self::$suffix;

		wp_register_style( 'pum-admin-general', self::$css_url . 'pum-admin-general' . $suffix . '.css', array( 'dashicons', 'wp-color-picker' ), Popup_Maker::$VER );
		wp_register_style( 'pum-admin-batch', self::$css_url . 'pum-admin-batch' . $suffix . '.css', array( 'pum-admin-general' ), Popup_Maker::$VER );
		wp_register_style( 'pum-admin-popup-editor', self::$css_url . 'pum-admin-popup-editor' . $suffix . '.css', array( 'pum-admin-general' ), Popup_Maker::$VER );
		wp_register_style( 'pum-admin-theme-editor', self::$css_url . 'pum-admin-theme-editor' . $suffix . '.css', array( 'pum-admin-general' ), Popup_Maker::$VER );
		wp_register_style( 'pum-admin-extensions-page', self::$css_url . 'pum-admin-extensions-page' . $suffix . '.css', array( 'pum-admin-general' ), Popup_Maker::$VER );
		wp_register_style( 'pum-admin-settings-page', self::$css_url . 'pum-admin-settings-page' . $suffix . '.css', array( 'pum-admin-general' ), Popup_Maker::$VER );
		wp_register_style( 'pum-admin-support-page', self::$css_url . 'pum-admin-support-page' . $suffix . '.css', array( 'pum-admin-general' ), Popup_Maker::$VER );
		wp_register_style( 'pum-admin-shortcode-ui', self::$css_url . 'pum-admin-shortcode-ui' . $suffix . '.css', array( 'pum-admin-general' ), Popup_Maker::$VER );

		// @deprecated handle. Currently loads empty file and admin-general as dependency.
		wp_register_style( 'popup-maker-admin', self::$css_url . 'pum-admin-deprecated' . $suffix . '.css', array( 'pum-admin-general' ), Popup_Maker::$VER );

		if ( PUM_Utils_Upgrades::instance()->has_uncomplete_upgrades() ) {
			wp_enqueue_style( 'pum-admin-batch' );
		}

		if ( pum_is_popup_editor() ) {
			wp_enqueue_style( 'pum-admin-popup-editor' );
		}

		if ( pum_is_popup_theme_editor() ) {
			PUM_Site_Assets::register_styles();
			wp_enqueue_style( 'pum-admin-theme-editor' );
		}

		if ( pum_is_extensions_page() ) {
			wp_enqueue_style( 'pum-admin-extensions-page' );
		}

		if ( pum_is_settings_page() ) {
			wp_enqueue_style( 'pum-admin-settings-page' );
		}

		if ( pum_is_support_page() ) {
			wp_enqueue_style( 'pum-admin-support-page' );
		}
	}

	/**
	 * @return bool
	 */
	public static function should_load() {

		if ( defined( "PUM_FORCE_ADMIN_SCRIPTS_LOAD" ) && PUM_FORCE_ADMIN_SCRIPTS_LOAD ) {
			return true;
		}

		if ( ! is_admin() ) {
			return false;
		}

		return pum_is_admin_page();
	}

}
