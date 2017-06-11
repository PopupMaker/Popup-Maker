<?php
/**
 * Scripts
 *
 * @package        POPMAKE
 * @subpackage    Functions
 * @copyright    Copyright (c) 2014, Wizard Internet Solutions
 * @license        http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load Scripts
 *
 * Loads the Popup Maker scripts.
 *
 * @since 1.0
 * @return void
 */
function popmake_load_site_scripts() {
	$js_dir = POPMAKE_URL . '/assets/js/';

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js';

	wp_register_script( 'mobile-detect', $js_dir . 'mobile-detect' . $suffix, null, '1.3.3', true );

	wp_register_script( 'popup-maker-site', $js_dir . 'site' . $suffix . '?defer', array(
		'jquery',
		'jquery-ui-core',
		'jquery-ui-position',
	), POPMAKE_VERSION, true );

	wp_localize_script( 'popup-maker-site', 'pum_vars', apply_filters( 'pum_vars', array(
		'ajaxurl'               => admin_url( 'admin-ajax.php' ),
		'restapi'               => function_exists( 'rest_url' ) ? esc_url_raw( rest_url( 'pum/v1' ) ) : false,
		'rest_nonce'            => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : null,
		'default_theme'         => (string) popmake_get_default_popup_theme(),
		'debug_mode'            => Popup_Maker::debug_mode(),
		'disable_open_tracking' => popmake_get_option( 'disable_popup_open_tracking' ),
	) ) );

	wp_localize_script( 'popup-maker-site', 'pum_debug_vars', apply_filters( 'pum_debug_vars', array(
		'debug_mode_enabled'             => _x( 'Popup Maker Debug Mode Enabled', 'debug console text', 'popup-maker' ),
		'debug_started_at'               => _x( 'Debug started at:', 'debug console text', 'popup-maker' ),
		'debug_more_info'                => sprintf( _x( 'For more information on how to use this information visit %s', 'debug console text', 'popup-maker' ), 'http://docs.wppopupmaker.com/?utm_medium=js-debug-info&utm_campaign=ContextualHelp&utm_source=browser-console&utm_content=more-info' ),
		'global_info'                    => _x( 'Global Information', 'debug console text', 'popup-maker' ),
		'localized_vars'                 => _x( 'Localized variables', 'debug console text', 'popup-maker' ),
		'popups_initializing'            => _x( 'Popups Initializing', 'debug console text', 'popup-maker' ),
		'popups_initialized'             => _x( 'Popups Initialized', 'debug console text', 'popup-maker' ),
		'single_popup_label'             => _x( 'Popup: #', 'debug console text', 'popup-maker' ),
		'theme_id'                       => _x( 'Theme ID: ', 'debug console text', 'popup-maker' ),
		'label_method_call'              => _x( 'Method Call:', 'debug console text', 'popup-maker' ),
		'label_method_args'              => _x( 'Method Arguments:', 'debug console text', 'popup-maker' ),
		'label_popup_settings'           => _x( 'Settings', 'debug console text', 'popup-maker' ),
		'label_triggers'                 => _x( 'Triggers', 'debug console text', 'popup-maker' ),
		'label_cookies'                  => _x( 'Cookies', 'debug console text', 'popup-maker' ),
		'label_delay'                    => _x( 'Delay:', 'debug console text', 'popup-maker' ),
		'label_conditions'               => _x( 'Conditions', 'debug console text', 'popup-maker' ),
		'label_cookie'                   => _x( 'Cookie:', 'debug console text', 'popup-maker' ),
		'label_settings'                 => _x( 'Settings:', 'debug console text', 'popup-maker' ),
		'label_selector'                 => _x( 'Selector:', 'debug console text', 'popup-maker' ),
		'label_mobile_disabled'          => _x( 'Mobile Disabled:', 'debug console text', 'popup-maker' ),
		'label_tablet_disabled'          => _x( 'Tablet Disabled:', 'debug console text', 'popup-maker' ),
		'label_display_settings'         => _x( 'Display Settings:', 'debug console text', 'popup-maker' ),
		'label_close_settings'           => _x( 'Close Settings:', 'debug console text', 'popup-maker' ),
		'label_event_before_open'        => _x( 'Event: Before Open', 'debug console text', 'popup-maker' ),
		'label_event_after_open'         => _x( 'Event: After Open', 'debug console text', 'popup-maker' ),
		'label_event_open_prevented'     => _x( 'Event: Open Prevented', 'debug console text', 'popup-maker' ),
		'label_event_setup_close'        => _x( 'Event: Setup Close', 'debug console text', 'popup-maker' ),
		'label_event_close_prevented'    => _x( 'Event: Close Prevented', 'debug console text', 'popup-maker' ),
		'label_event_before_close'       => _x( 'Event: Before Close', 'debug console text', 'popup-maker' ),
		'label_event_after_close'        => _x( 'Event: After Close', 'debug console text', 'popup-maker' ),
		'label_event_before_reposition'  => _x( 'Event: Before Reposition', 'debug console text', 'popup-maker' ),
		'label_event_after_reposition'   => _x( 'Event: After Reposition', 'debug console text', 'popup-maker' ),
		'label_event_checking_condition' => _x( 'Event: Checking Condition', 'debug console text', 'popup-maker' ),
		'triggers'                       => pum_get_trigger_labels(),
		'cookies'                        => pum_get_cookie_labels(),
	) ) );

	// TODO Remove all trace usages of these in JS so they can be removed.
	// @deprecated 1.4 Use pum_vars instead.
	wp_localize_script( 'popup-maker-site', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
	// @deprecated 1.4 Use pum_vars instead.
	wp_localize_script( 'popup-maker-site', 'popmake_default_theme', (string) popmake_get_default_popup_theme() );

	if ( popmake_get_option( 'enable_easy_modal_compatibility_mode', false ) ) {
		wp_register_script( 'popup-maker-easy-modal-importer-site', $js_dir . 'popup-maker-easy-modal-importer-site' . $suffix . '?defer', array( 'popup-maker-site' ), POPMAKE_VERSION, true );
	}
}

add_action( 'wp_enqueue_scripts', 'popmake_load_site_scripts' );


/**
 * Load Styles
 *
 * Loads the Popup Maker stylesheet.
 *
 * @since 1.0
 * @return void
 */
function popmake_load_site_styles() {
	$css_dir = POPMAKE_URL . '/assets/css/';

	$dep_css_dir = $css_dir;
	// If not v1.4 compatible load backward version until migration complete.
	if ( ! pum_is_v1_4_compatible() ) {
		$dep_css_dir = POPMAKE_URL . '/deprecated/assets/css/';
	}

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.css' : '.min.css';
	wp_register_style( 'popup-maker-site', $dep_css_dir . 'site' . $suffix, false, POPMAKE_VERSION );

	if ( ! popmake_get_option( 'disable_popup_theme_styles', false ) ) {
		wp_enqueue_style( 'popup-maker-site' );
	}
}

add_action( 'wp_enqueue_scripts', 'popmake_load_site_styles' );

function popmake_get_popup_theme_styles() {

	$styles = get_transient( 'popmake_theme_styles' );
	if ( ! $styles || defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {

		$styles = '';

		$google_fonts = array();

		foreach ( popmake_get_all_popup_themes() as $theme ) {
			$theme_styles = pum_is_v1_4_compatible() ? pum_render_theme_styles( $theme->ID ) : popmake_render_theme_styles( $theme->ID );

			$google_fonts = array_merge( $google_fonts, popmake_get_popup_theme_google_fonts( $theme->ID ) );

			if ( $theme_styles != '' ) {
				$styles .= "/* Popup Theme " . $theme->ID . ": " . $theme->post_title . " */\r\n";
				$styles .= $theme_styles . "\r\n";
			}
		}

		if ( ! empty( $google_fonts ) && ! popmake_get_option( 'disable_google_font_loading', false ) ) {
			$link = "//fonts.googleapis.com/css?family=";
			foreach ( $google_fonts as $font_family => $variants ) {
				if ( $link != "//fonts.googleapis.com/css?family=" ) {
					$link .= "|";
				}
				$link .= $font_family;
				if ( is_array( $variants ) ) {
					if ( implode( ',', $variants ) != '' ) {
						$link .= ":";
						$link .= trim( implode( ',', $variants ), ':' );
					}
				}
			}

			$styles = "/* Popup Google Fonts */\r\n@import url('$link');\r\n\r\n" . $styles;
		}

		$styles = apply_filters( 'popmake_theme_styles', $styles );

		set_transient( 'popmake_theme_styles', $styles );

	}

	return $styles;
}

function popmake_render_popup_theme_styles() {
	if ( ( current_action() == 'wp_head' && popmake_get_option( 'disable_popup_theme_styles', false ) ) || ( current_action() == 'admin_head' && ! popmake_is_admin_popup_page() ) ) {
		return;
	}

	global $pum_extra_styles;

	$styles = popmake_get_popup_theme_styles(); ?>
	<style id="pum-styles" type="text/css" media="all">
	<?php echo $styles; ?>

	<?php echo $pum_extra_styles; ?>

	<?php do_action( 'pum_styles'); ?>
	</style><?php
}

add_action( 'wp_head', 'popmake_render_popup_theme_styles', 99999 );
add_action( 'admin_head', 'popmake_render_popup_theme_styles', 99999 );

function pum_should_load_admin_scripts() {
	global $pagenow;

	return ( is_admin() && ( popmake_is_admin_page() || in_array( $pagenow, array(
				'post.php',
				'edit.php',
				'post-new.php',
			) ) ) ) || ( defined( "PUM_FORCE_ADMIN_SCRIPTS_LOAD" ) && PUM_FORCE_ADMIN_SCRIPTS_LOAD );
}


/**
 * Load Admin Scripts
 *
 * Enqueues the required admin scripts.
 *
 * @since 1.0
 *
 * @param string $hook Page hook
 *
 * @return void
 */
function popmake_load_admin_scripts( $hook ) {
	$js_dir = POPMAKE_URL . '/assets/js/';

	$dep_js_dir = $js_dir;
	// If not v1.4 compatible load backward version until migration complete.
	if ( ! pum_is_v1_4_compatible() ) {
		$dep_js_dir = POPMAKE_URL . '/deprecated/assets/js/';
	}

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js';
	if ( popmake_is_admin_popup_page() || popmake_is_admin_popup_theme_page() ) {
		//add_action( 'popmake_admin_footer', 'popmake_admin_popup_preview' );

	}

	if ( pum_should_load_admin_scripts() ) {

		wp_enqueue_script( 'popup-maker-admin', $dep_js_dir . 'admin' . $suffix, array(
			'jquery',
			'wp-color-picker',
			'jquery-ui-slider',
			'wp-util',
		), POPMAKE_VERSION );
		wp_localize_script( 'popup-maker-admin', 'popmake_admin_ajax_nonce', wp_create_nonce( POPMAKE_NONCE ) );
		wp_localize_script( 'popup-maker-admin', 'pum_admin', apply_filters( 'pum_admin_var', array(
			'post_id'  => ! empty( $_GET['post'] ) ? intval( $_GET['post'] ) : null,
			'defaults' => array(
				'triggers' => PUM_Triggers::instance()->get_defaults(),
				'cookies'  => PUM_Cookies::instance()->get_defaults(),
			),
			'I10n'     => array(
				'add'                         => __( 'Add', 'popup-maker' ),
				'save'                        => __( 'Save', 'popup-maker' ),
				'update'                      => __( 'Update', 'popup-maker' ),
				'insert'                      => __( 'Insert', 'popup-maker' ),
				'cancel'                      => __( 'Cancel', 'popup-maker' ),
				'shortcode_ui_button_tooltip' => __( 'Popup Maker Shortcodes', 'popup-maker' ),
				'confirm_delete_trigger'      => __( "Are you sure you want to delete this trigger?", 'popup-maker' ),
				'confirm_delete_cookie'       => __( "Are you sure you want to delete this cookie?", 'popup-maker' ),
				'labels'                      => array(
					'triggers' => PUM_Triggers::instance()->get_labels(),
					'cookies'  => PUM_Cookies::instance()->get_labels(),
				),
				'no_cookie'                   => __( 'None', 'popup-maker' ),
				'confirm_count_reset'         => __( 'Are you sure you want to reset the open count?', 'popup-maker' ),
                'error_loading_shortcode_preview' => __( 'There was an error in generating the preview', 'popup-maker' ),
			),
		) ) );
	}
	if ( popmake_is_admin_popup_page() ) {
		popmake_load_site_scripts();
		add_action( 'admin_footer', 'pum_admin_popup_editor_media_templates' );

	}
	if ( popmake_is_admin_popup_theme_page() ) {
		wp_localize_script( 'popup-maker-admin', 'popmake_google_fonts', popmake_get_google_webfonts_list() );
	}

	if ( isset( $_GET['page'] ) && $_GET['page'] == 'pum-support' ) {
		wp_enqueue_script( 'iframe-resizer', $dep_js_dir . 'iframeResizer' . $suffix, array(
			'jquery',
		) );

	}
}

add_action( 'admin_enqueue_scripts', 'popmake_load_admin_scripts' );


/**
 * Load Admin Styles
 *
 * Enqueues the required admin styles.
 *
 * @since 1.0
 *
 * @param string $hook Page hook
 *
 * @return void
 */
function popmake_load_admin_styles( $hook ) {
	$css_dir = POPMAKE_URL . '/assets/css/';

	$dep_css_dir = $css_dir;
	// If not v1.4 compatible load backward version until migration complete.
	if ( ! pum_is_v1_4_compatible() ) {
		$dep_css_dir = POPMAKE_URL . '/deprecated/assets/css/';
	}

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.css' : '.min.css';
	if ( popmake_is_admin_popup_page() || popmake_is_admin_popup_theme_page() ) {
		wp_enqueue_style( 'popup-maker-site', $css_dir . 'site' . $suffix, false, POPMAKE_VERSION );
	}
	if ( pum_should_load_admin_scripts() ) {

		//wp_enqueue_style( 'pumselect2', $css_dir . 'select2' . $suffix, array(), '4.0.1' );

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'popup-maker-admin', $dep_css_dir . 'admin' . $suffix, null, POPMAKE_VERSION );

	}
}

add_action( 'admin_enqueue_scripts', 'popmake_load_admin_styles', 100 );

/**
 * Load Admin Styles
 *
 * Defers loading of scripts with ?defer parameter in url.
 *
 * @since 1.0
 *
 * @param string $url URL being cleaned
 *
 * @return string $url
 */
function popmake_defer_js_url( $url ) {
	if ( false === strpos( $url, '.js?defer' ) ) {
		// not our file
		return $url;
	}

	return "$url' defer='defer";
}

add_filter( 'clean_url', 'popmake_defer_js_url', 11, 1 );


function popmake_script_loading_enabled() {
	global $wp_query;
	if ( ! empty( $wp_query->post ) && has_shortcode( $wp_query->post->post_content, 'popup' ) || ( defined( "POPMAKE_FORCE_SCRIPTS" ) && POPMAKE_FORCE_SCRIPTS ) ) {
		popmake_enqueue_scripts();
	}
}

add_action( 'wp_head', 'popmake_script_loading_enabled' );


function popmake_enqueue_scripts( $popup_id = null ) {

	global $pum_extra_styles;

	$popup = new PUM_Popup( $popup_id );
	if ( $popup->mobile_disabled() || $popup->tablet_disabled() ) {
		wp_enqueue_script( 'mobile-detect' );
	}

	$scripts_needed = apply_filters( 'popmake_enqueue_scripts', array(
		'popup-maker'         => 'popup-maker-site',
		'easy-modal-importer' => 'popup-maker-easy-modal-importer-site',
	), $popup_id );

	foreach ( $scripts_needed as $script ) {
		if ( wp_script_is( $script, 'registered' ) ) {
			wp_enqueue_script( $script );
		}
	}

	$styles_needed = apply_filters( 'popmake_enqueue_styles', array(
		'popup-maker'  => 'popup-maker-site',
	), $popup_id );

	foreach ( $styles_needed as $style ) {
		if ( wp_style_is( $style, 'registered' ) ) {
			wp_enqueue_style( $style );
		}
	}


}

add_action( 'popmake_preload_popup', 'popmake_enqueue_scripts' );

function pum_admin_popup_editor_media_templates() {

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
					<li data-preset='<?php echo $preset; ?>'><span><?php echo $label; ?></span></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</script>

	<?php

}