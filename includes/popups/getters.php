<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Return the popup id.
 *
 * @param int $popup_id
 *
 * @return int
 */
function pum_get_popup_id( $popup_id = 0 ) {
	if ( ! empty( $popup_id ) && is_numeric( $popup_id ) ) {
		$_popup_id = $popup_id;
	} elseif ( is_object( PUM_Site_Popups::$current ) && is_numeric( PUM_Site_Popups::$current->ID ) ) {
		$_popup_id = PUM_Site_Popups::$current->ID;
	} else {
		$_popup_id = 0;
	}

	return (int) apply_filters( 'pum_get_popup_id', (int) $_popup_id, $popup_id );
}

/**
 * Get the forum title
 *
 * @param int $forum_id
 *
 * @return string
 */
function forumwp_get_forum_title( $forum_id = 0 ) {
	$forum = forumwp_get_forum( $forum_id );

	if ( ! forumwp_is_forum_object( $forum ) ) {
		return '';
	}

	return $forum->get_title();
}

/**
 * @param int $popup_id
 *
 * @return string
 */
function pum_get_popup_title( $popup_id = 0 ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup_object( $popup ) ) {
		return "";
	}

	return $popup->get_title();
}
