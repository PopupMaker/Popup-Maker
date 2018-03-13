<?php

/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Post_Types
 */
class PUM_Popups {

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {
		add_filter( 'pum_popup_content', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
		add_filter( 'pum_popup_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
		add_filter( 'pum_popup_content', 'wptexturize', 10 );
		add_filter( 'pum_popup_content', 'convert_smilies', 10 );
		add_filter( 'pum_popup_content', 'convert_chars', 10 );
		add_filter( 'pum_popup_content', 'wpautop', 10 );
		add_filter( 'pum_popup_content', 'shortcode_unautop', 10 );
		add_filter( 'pum_popup_content', 'prepend_attachment', 10 );
		add_filter( 'pum_popup_content', 'force_balance_tags', 10 );
		add_filter( 'pum_popup_content', 'do_shortcode', 11 );
		add_filter( 'pum_popup_content', 'capital_P_dangit', 11 );
	}


	public static function get_all() {
		static $query;

		if ( ! isset( $query ) ) {
			$query = self::query();
		}

		return $query;
	}

	public static function query( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'post_type'      => 'popup',
			'posts_per_page' => - 1,
		) );

		return new WP_Query( $args );
	}

}
