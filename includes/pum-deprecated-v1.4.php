<?php
// Exit if accessed directly

/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @see popmake_popup_meta_box_save
 *
 * @param $post_id
 * @param $post
 */
function pum_deprecated_save_popup_action( $post_id, $post ) {
	if ( has_action( 'popmake_save_popup' ) ) {
		PUM_Logging::instance()->log_deprecated_notice( 'popmake_save_popup', '1.4', 'pum_save_popup' );
		/**
		 * Calls old save action.
		 *
		 * @deprecated 1.4
		 *
		 * @param int   $post_id $post Post ID.
		 * @param array $post    Sanitized $_POST variable.
		 */
		do_action( 'popmake_save_popup', $post_id, $post );
	}
}
add_action( 'pum_save_popup', 'pum_deprecated_save_popup_action', 10, 2 );


/**
 * Applies the deprecated popmake_popup_is_loadable filter.
 *
 * @see PUM_Popup->is_loadable()
 *
 * @param $loadable
 * @param $popup_id
 *
 * @return bool $loadable
 */
function pum_deprecated_popup_is_loadable_filter( $loadable, $popup_id ) {
	if ( has_filter( 'popmake_popup_is_loadable' ) ) {
		PUM_Logging::instance()->log_deprecated_notice( 'popmake_popup_is_loadable', '1.4', 'pum_popup_is_loadable' );
		/**
		 * Calls old filter.
		 *
		 * @deprecated 1.4
		 *
		 * @param bool  $loadable True if popup should load.
		 * @param array $popup_id Post ID.
		 */
		return apply_filters( 'popmake_popup_is_loadable', $loadable, $popup_id, array(), false );
	}

	return $loadable;
}
add_filter( 'pum_popup_is_loadable', 'pum_deprecated_popup_is_loadable_filter', 10, 2 );
