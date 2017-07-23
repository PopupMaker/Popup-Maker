<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_admin_styles' ), 100 );
	}


	/**
	 * Load Admin Scripts
	 */
	public static function register_admin_scripts() {

		if ( self::should_load() ) {

			wp_enqueue_script( 'popup-maker-admin', self::$js_url . 'admin' . self::$suffix . '.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-slider', 'wp-util' ), Popup_Maker::$VER, true );

			$admin_vars = apply_filters( 'pum_admin_vars', apply_filters( 'pum_admin_var', array(
				'post_id'  => ! empty( $_GET['post'] ) ? intval( $_GET['post'] ) : null,
				'defaults' => array(
					'triggers' => PUM_Triggers::instance()->get_defaults(),
					'cookies'  => PUM_Cookies::instance()->get_defaults(),
				),
				'I10n'     => array(
					'add'                             => __( 'Add', 'popup-maker' ),
					'save'                            => __( 'Save', 'popup-maker' ),
					'update'                          => __( 'Update', 'popup-maker' ),
					'insert'                          => __( 'Insert', 'popup-maker' ),
					'cancel'                          => __( 'Cancel', 'popup-maker' ),
					'shortcode_ui_button_tooltip'     => __( 'Popup Maker Shortcodes', 'popup-maker' ),
					'confirm_delete_trigger'          => __( "Are you sure you want to delete this trigger?", 'popup-maker' ),
					'confirm_delete_cookie'           => __( "Are you sure you want to delete this cookie?", 'popup-maker' ),
					'labels'                          => array(
						'triggers' => PUM_Triggers::instance()->get_labels(),
						'cookies'  => PUM_Cookies::instance()->get_labels(),
					),
					'no_cookie'                       => __( 'None', 'popup-maker' ),
					'confirm_count_reset'             => __( 'Are you sure you want to reset the open count?', 'popup-maker' ),
					'error_loading_shortcode_preview' => __( 'There was an error in generating the preview', 'popup-maker' ),
				),
			) ) );

			wp_localize_script( 'popup-maker-admin', 'pum_admin_vars', $admin_vars );
			// @deprecated.
			wp_localize_script( 'popup-maker-admin', 'pum_admin', $admin_vars );
		}

		// TODO Clean up this next section, haven't touched it at all. Is any of it needed or can we move/remove it.
		if ( popmake_is_admin_popup_page() ) {
			PUM_Admin_Templates::init();
			add_action( 'admin_footer', array( __CLASS__, 'admin_popup_editor_media_templates' ) );
		}
		if ( popmake_is_admin_popup_theme_page() ) {
			wp_localize_script( 'popup-maker-admin', 'popmake_google_fonts', popmake_get_google_webfonts_list() );
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'pum-support' ) {
			wp_enqueue_script( 'iframe-resizer', self::$js_url . 'iframeResizer' . self::$suffix, array(
				'jquery',
			) );
		}

	}

	/**
	 * Load Admin Styles
	 */
	public static function register_admin_styles() {
		if ( popmake_is_admin_popup_page() || popmake_is_admin_popup_theme_page() ) {
			// Load front end styles.
			// TODO Refactor this or remove it, the above 2 functions are also in need of work.
			PUM_Site_Assets::register_styles();
		}

		if ( self::should_load() ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'popup-maker-admin', self::$css_url . 'admin' . self::$suffix . '.css', null, Popup_Maker::$VER );
		}
	}

	/**
	 * @return bool
	 */
	public static function should_load() {
		global $pagenow;

		if ( defined( "PUM_FORCE_ADMIN_SCRIPTS_LOAD" ) && PUM_FORCE_ADMIN_SCRIPTS_LOAD ) {
			return true;
		}

		if ( ! is_admin() ) {
			return false;
		}

		$pages = array(
			'post.php',
			'edit.php',
			'post-new.php',
		);


		return popmake_is_admin_page() || in_array( $pagenow, $pages );
	}

	/**
	 *
	 */
	public static function admin_popup_editor_media_templates() {

		$presets = apply_filters( 'pum_click_selector_presets', array(
			'a[href="exact_url"]'    => __( 'Link: Exact Match', 'popup-maker' ),
			'a[href*="contains"]'    => __( 'Link: Containing', 'popup-maker' ),
			'a[href^="begins_with"]' => __( 'Link: Begins With', 'popup-maker' ),
			'a[href$="ends_with"]'   => __( 'Link: Ends With', 'popup-maker' ),
		) ); ?>

		<script type="text/html" id="tmpl-pum-click-selector-presets">
			<div class="pum-click-selector-presets">
				<span class="dashicons dashicons-arrow-left" title="<?php _e( 'Insert Preset', 'popup-maker' ); ?>"></span>
				<ul>
					<?php foreach ( $presets as $preset => $label ) : ?>
						<li data-preset='<?php echo $preset; ?>'>
							<span><?php echo $label; ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</script>

		<?php

	}

}