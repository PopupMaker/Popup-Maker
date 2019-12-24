<?php

/**
 * Class PUM_Site
 */
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
		if ( empty( $_REQUEST['pum_action'] ) ) {
			return;
		}

		$valid_actions = apply_filters( 'pum_valid_request_actions', array(
			'popup_sysinfo',
			'save_enabled_betas',
			'download_batch_export',
		) );

		$action = sanitize_text_field( $_REQUEST['pum_action'] );

		if ( ! in_array( $action, $valid_actions ) || ! has_action( 'pum_' . $action ) ) {
			return;
		}

		do_action( 'pum_' . $action, $_REQUEST );
	}
}