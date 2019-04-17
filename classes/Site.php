<?php

class PUM_Site {

	public static function init() {
		PUM_Site_Assets::init();
		PUM_Site_Popups::init();
		PUM_Analytics::init();

		add_action( 'init', array( __CLASS__, 'actions' ) );
		/**
		 * @since 1.4 hooks & filters
		 */
		add_filter( 'pum_popup_content', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
		add_filter( 'pum_popup_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
		add_filter( 'pum_popup_content', 'wptexturize', 10 );
		add_filter( 'pum_popup_content', 'convert_smilies', 10 );
		add_filter( 'pum_popup_content', 'convert_chars', 10 );
		add_filter( 'pum_popup_content', 'wpautop', 10 );
		add_filter( 'pum_popup_content', 'shortcode_unautop', 10 );
		add_filter( 'pum_popup_content', 'prepend_attachment', 10 );
		add_filter( 'pum_popup_content', 'force_balance_tags', 10 );
		if ( pum_get_option( 'disable_shortcode_compatibility_mode' ) ) {
			add_filter( 'pum_popup_content', 'do_shortcode', 11 );
		} else {
			add_filter( 'pum_popup_content', array( 'PUM_Helpers', 'do_shortcode' ), 11 );
		}
		add_filter( 'pum_popup_content', 'capital_P_dangit', 11 );
	}

	/**
	 * Hooks Popup Maker actions, when present in the $_GET superglobal. Every popmake_action
	 * present in $_GET is called using WordPress's do_action function. These
	 * functions are called on init.
	 */
	public static function actions() {
		if ( isset( $_GET['popmake_action'] ) ) {
			do_action( 'popmake_' . $_GET['popmake_action'], $_GET );
		} else if ( isset( $_POST['popmake_action'] ) ) {
			do_action( 'popmake_' . $_POST['popmake_action'], $_POST );
		} else if ( isset( $_GET['pum_action'] ) ) {
			do_action( 'pum_' . $_GET['pum_action'], $_GET );
		} else if ( isset( $_POST['pum_action'] ) ) {
			do_action( 'pum_' . $_POST['pum_action'], $_POST );
		}
	}
}