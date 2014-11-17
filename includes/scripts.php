<?php
/**
 * Scripts
 *
 * @package		POPMAKE
 * @subpackage	Functions
 * @copyright	Copyright (c) 2014, Wizard Internet Solutions
 * @license		http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Load Scripts
 *
 * Loads the Popup Maker scripts.
 *
 * @since 1.0
 * @return void
 */
function popmake_load_site_scripts() {
	global $popmake_options;
	$js_dir = POPMAKE_URL . '/assets/scripts/';
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js';
	wp_register_script('TweenMax', $js_dir . '/gsap/TweenMax.min.js', false, '1.14.2', true);
	wp_register_script('jquery-gsap', $js_dir . '/gsap/jquery.gsap.min.js', array('jquery', 'TweenMax'), '0.1.9', true);
	wp_register_script('jquery-cookie', $js_dir . 'jquery.cookie' . $suffix, array('jquery'), '1.4.1', true);
	wp_enqueue_script('popup-maker-site', $js_dir . 'popup-maker-site' . $suffix . '?defer', array('jquery', 'jquery-ui-core', 'jquery-ui-position', 'jquery-gsap'), '1.0', true);
	wp_localize_script('popup-maker-site', 'ajaxurl', admin_url('admin-ajax.php') );
	wp_localize_script('popup-maker-site', 'popmake_default_theme', popmake_get_default_popup_theme() );
	wp_localize_script('popup-maker-site', 'popmake_themes', array('l10n_print_after' => 'popmake_themes = ' . json_encode( popmake_get_popup_themes_data() ) . ';'));
	if(empty($popmake_options['popmake_powered_by_opt_out']) || !$popmake_options['popmake_powered_by_opt_out']) {
		$size = $popmake_options['popmake_powered_by_size'];
		wp_localize_script('popup-maker-site', 'popmake_powered_by', '<div class="powered-by-popmake '. $size .'"><a href="https://wppopupmaker.com" target="_blank"><img src="' . POPMAKE_URL . '/assets/images/admin/powered-by-popup-maker.png" alt="'. __( 'Powered By Popup Maker', 'popup-maker' ) .'"/></a></div>' );
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
	global $popmake_options, $popmake_needed_google_fonts;
	$css_dir = POPMAKE_URL . '/assets/styles/';
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.css' : '.min.css';
	wp_enqueue_style('popup-maker-site', $css_dir . 'popup-maker-site' . $suffix, false, '1.0');
	if(!empty($popmake_needed_google_fonts) && !isset($popmake_options['disable_google_font_loading'])) {
		$link = "//fonts.googleapis.com/css?family=";
		foreach($popmake_needed_google_fonts as $font_family => $variants) {
			if($link != "//fonts.googleapis.com/css?family=") {
				$link .= "|";
			}
			$link .= $font_family;
			if(!empty($variants)) {
				$link .= ":";
				$link .= implode(',', $variants);
			}
		}
		wp_enqueue_style('popup-maker-google-fonts', $link);
	}
}
add_action( 'wp_enqueue_scripts', 'popmake_load_site_styles' );

/**
 * Load Admin Scripts
 *
 * Enqueues the required admin scripts.
 *
 * @since 1.0
 * @param string $hook Page hook
 * @return void
 */
function popmake_load_admin_scripts( $hook ) {
	$js_dir  = POPMAKE_URL . '/assets/scripts/';
	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js';
	if(popmake_is_admin_page()) {
		wp_enqueue_script('popup-maker-admin', $js_dir . 'popup-maker-admin' . $suffix,  array('jquery', 'wp-color-picker', 'jquery-ui-slider'), '1.0');
		wp_localize_script('popup-maker-admin', 'popmake_admin_ajax_nonce', wp_create_nonce( POPMAKE_NONCE ));
	}
	if(popmake_is_admin_popup_theme_page()) {
		wp_localize_script('popup-maker-admin', 'popmake_google_fonts', popmake_get_google_webfonts_list());
	}
}
add_action( 'admin_enqueue_scripts', 'popmake_load_admin_scripts', 100 );

/**
 * Load Admin Styles
 *
 * Enqueues the required admin styles.
 *
 * @since 1.0
 * @param string $hook Page hook
 * @return void
 */
function popmake_load_admin_styles( $hook ) {
	$css_dir = POPMAKE_URL . '/assets/styles/';
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.css' : '.min.css';
	if(popmake_is_admin_page()) {
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_style('popup-maker-admin', $css_dir . 'popup-maker-admin' . $suffix, false, '1.0');
	}
}
add_action( 'admin_enqueue_scripts', 'popmake_load_admin_styles', 100 );

/**
 * Load Admin Styles
 *
 * Defers loading of scripts with ?defer parameter in url.
 *
 * @since 1.0
 * @param string $url URL being cleaned
 * @return Variable $url
 */
function popmake_defer_js_url( $url )
{
	if ( FALSE === strpos( $url, '.js?defer' ) ) {
		// not our file
		return $url;
	}
	return "$url' defer='defer";
}
add_filter( 'clean_url', 'popmake_defer_js_url', 11, 1 );