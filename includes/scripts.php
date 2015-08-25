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
	$js_dir = POPMAKE_URL . '/assets/scripts/';
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js';

	// Deprecated. No longer used. Here for older versions of some extensions.
	wp_register_script( 'jquery-cookie', $js_dir . 'jquery.cookie' . $suffix, array( 'jquery' ), '1.4.1', true );

	wp_register_script( 'popup-maker-site', $js_dir . 'popup-maker-site' . $suffix . '?defer', array(
		'jquery',
		'jquery-ui-core',
		'jquery-ui-position'
	), POPMAKE_VERSION, true );
	wp_localize_script( 'popup-maker-site', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
	wp_localize_script( 'popup-maker-site', 'popmake_default_theme', (string) get_option( 'popmake_default_theme' ) );

	if ( popmake_get_option( 'popmake_powered_by_opt_in', false ) ) {
		$size = popmake_get_option( 'popmake_powered_by_size', '' );
		wp_localize_script( 'popup-maker-site', 'popmake_powered_by', '<div class="powered-by-popmake ' . $size . '"><a href="https://wppopupmaker.com?utm_source=Powered+By&utm_medium=Powered+By&utm_campaign=Powered+By" target="_blank"><img src="' . POPMAKE_URL . '/assets/images/admin/powered-by-popup-maker.png" alt="' . __( 'Powered By Popup Maker', 'popup-maker' ) . '"/></a></div>' );
	}

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
	global $popmake_needed_google_fonts;
	$css_dir = POPMAKE_URL . '/assets/styles/';
	$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.css' : '.min.css';
	wp_register_style( 'popup-maker-site', $css_dir . 'popup-maker-site' . $suffix, false, POPMAKE_VERSION );
	if ( ! empty( $popmake_needed_google_fonts ) && ! popmake_get_option( 'disable_google_font_loading', false ) ) {
		$link = "//fonts.googleapis.com/css?family=";
		foreach ( $popmake_needed_google_fonts as $font_family => $variants ) {
			if ( $link != "//fonts.googleapis.com/css?family=" ) {
				$link .= "|";
			}
			$link .= $font_family;
			if ( ! empty( $variants ) ) {
				$link .= ":";
				$link .= implode( ',', $variants );
			}
		}
		wp_register_style( 'popup-maker-google-fonts', $link );
	}
}

add_action( 'wp_enqueue_scripts', 'popmake_load_site_styles' );

function popmake_render_popup_theme_styles() {
	if (
		( current_action() == 'wp_head' && popmake_get_option( 'disable_popup_theme_styles', false ) ) ||
		( current_action() == 'admin_head' && ! popmake_is_admin_popup_page() )
	) {
		return;
	}

	$styles = get_transient( 'popmake_theme_styles' );
	if ( ! $styles ) {

		$styles = '';

		foreach ( popmake_get_all_popup_themes() as $theme ) {
			$theme_styles = popmake_render_theme_styles( $theme->ID );

			if ( $theme_styles != '' ) {
				$styles .= "/* Popup Theme " . $theme->ID . ": " . $theme->post_title . " */\r\n";
				$styles .= $theme_styles;
			}
		}

		set_transient( 'popmake_theme_styles', $styles, 7 * DAY_IN_SECONDS );

	}

	echo '<style id="popup-maker-themes"  type="text/css">' . $styles . '</style>';
}

add_action( 'wp_head', 'popmake_render_popup_theme_styles' );
add_action( 'admin_head', 'popmake_render_popup_theme_styles' );

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
function popmake_load_admin_scripts() {
	$js_dir = POPMAKE_URL . '/assets/scripts/';
	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js';
	if ( popmake_is_admin_popup_page() || popmake_is_admin_popup_theme_page() ) {
		add_action( 'popmake_admin_footer', 'popmake_admin_popup_preview' );

	}
	if ( popmake_is_admin_page() ) {
		wp_enqueue_script( 'popup-maker-admin', $js_dir . 'popup-maker-admin' . $suffix, array(
			'jquery',
			'wp-color-picker',
			'jquery-ui-slider'
		), POPMAKE_VERSION );
		wp_localize_script( 'popup-maker-admin', 'popmake_admin_ajax_nonce', wp_create_nonce( POPMAKE_NONCE ) );
	}
	if ( popmake_is_admin_popup_page() ) {
		wp_enqueue_script( 'popup-maker-site', $js_dir . 'popup-maker-site' . $suffix . '?defer', array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-position'
		), POPMAKE_VERSION, true );
		wp_localize_script( 'popup-maker-site', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
		wp_localize_script( 'popup-maker-site', 'popmake_default_theme', (string) get_option( 'popmake_default_theme' ) );
	}
	if ( popmake_is_admin_popup_theme_page() ) {
		wp_localize_script( 'popup-maker-admin', 'popmake_google_fonts', popmake_get_google_webfonts_list() );
	}
}

add_action( 'admin_enqueue_scripts', 'popmake_load_admin_scripts', 100 );

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
function popmake_load_admin_styles() {
	$css_dir = POPMAKE_URL . '/assets/styles/';
	$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.css' : '.min.css';
	if ( popmake_is_admin_popup_page() || popmake_is_admin_popup_theme_page() ) {
		wp_enqueue_style( 'popup-maker-site', $css_dir . 'popup-maker-site' . $suffix, false, POPMAKE_VERSION );
	}
	if ( popmake_is_admin_page() ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'popup-maker-admin', $css_dir . 'popup-maker-admin' . $suffix, false, POPMAKE_VERSION );
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
 * @return Variable $url
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
	$scripts_needed = apply_filters( 'popmake_enqueue_scripts', array(
		'popup-maker' => 'popup-maker-site',
		'easy-modal-importer' => 'popup-maker-easy-modal-importer-site'
	), $popup_id );
	foreach ( $scripts_needed as $script ) {
		if ( wp_script_is( $script, 'registered' ) ) {
			wp_enqueue_script( $script );
		}
	}

	$styles_needed = apply_filters( 'popmake_enqueue_styles', array(
		'popup-maker' => 'popup-maker-site',
		'google-fonts' => 'popup-maker-google-fonts'
	), $popup_id );
	foreach ( $styles_needed as $style ) {
		if ( wp_style_is( $style, 'registered' ) ) {
			wp_enqueue_style( $style );
		}
	}
}

add_action( 'popmake_preload_popup', 'popmake_enqueue_scripts' );

