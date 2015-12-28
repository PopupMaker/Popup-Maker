<?php

function pum_popup_ID( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	echo $popup->ID;
}

function pum_popup_title( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	return $popup->get_title();
	echo pum_get_popup_title( $popup_id );
}

function pum_popup_content( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	echo $popup->get_content();
}

function pum_popup_theme_id( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	echo intval( $popup->get_theme_id() );
}

function pum_popup_classes( $popup_id = null, $element = 'overlay' ) {
	$popup = new PUM_Popup( $popup_id );
	esc_attr_e( implode( ' ', $popup->get_classes( $element ) ) );
}

function pum_popup_data_attr( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	echo 'data-popmake="' . esc_attr( json_encode( $popup->get_data_attr() ) ) . '"';
}


function pum_popup_close_text( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	esc_html_e( $popup->close_text() );
}


/**
 * Conditional Template Tags.
 */

/**
 * Returns true if the close button should be shown.
 *
 * @param null $popup_id
 *
 * @return bool
 */
function pum_show_close_button( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	return boolval( $popup->show_close_button() );
}