<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Render the popup ID
 *
 * @param null|int|string $popup_id
 */
function pum_popup_ID( $popup_id = null ) {
	echo pum_get_popup_id( $popup_id );
}

/**
 * Render the popup title.
 *
 * @param null|int $popup_id
 */
function pum_popup_title( $popup_id = null ) {
	echo pum_get_popup_title( $popup_id );
}

/**
 * Render the popup content.
 *
 * @param null|int $popup_id
 */
function pum_popup_content( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	$cached_content = PUM_Site_Popups::get_cache_content( $popup->ID );

	echo false !== $cached_content ? $cached_content : $popup->get_content();
}

/**
 * Render the chose popup elements classes.
 *
 * @param null   $popup_id
 * @param string $element
 */
function pum_popup_classes( $popup_id = null, $element = 'overlay' ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	echo esc_attr( implode( ' ', $popup->get_classes( $element ) ) );
}

/**
 * Render the popups data attribute.
 *
 * @param null|int $popup_id
 */
function pum_popup_data_attr( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	echo 'data-popmake="' . esc_attr( wp_json_encode( $popup->get_data_attr() ) ) . '"';
}

/**
 * Render the popup's content tabindex attribute to make focusable 
 * if needed.
 */
function pum_popup_content_tabindex_attr( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	// Greater or equal to 0 makes it focusable.
	echo 'tabindex="0"';
}

/**
 * Render the popup close button text.
 *
 * @param null|int $popup_id
 */
function pum_popup_close_text( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	$close_text = $popup->close_text();

	// If the close text is a font awesome icon (E.g. "fas fa-camera"), add the icon instead of the text.
	if ( preg_match( "/^fa[srldb]?\s.+/i", $close_text ) ) {
		echo '<i class="' . esc_attr( $close_text ) . '"></i>';
	} else {
		echo esc_html( $close_text );
	}
}
