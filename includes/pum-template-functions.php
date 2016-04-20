<?php

function pum_popup_ID( $popup_id = null ) {
	echo pum_popup( $popup_id )->ID;
}

function pum_popup_title( $popup_id = null ) {
	echo pum_get_popup_title( $popup_id );
}

function pum_popup_content( $popup_id = null ) {
	echo pum_popup( $popup_id )->get_content();
}

function pum_popup_theme_id( $popup_id = null ) {
	echo intval( pum_popup( $popup_id )->get_theme_id() );
}

function pum_popup_classes( $popup_id = null, $element = 'overlay' ) {
	esc_attr_e( implode( ' ', pum_popup( $popup_id )->get_classes( $element ) ) );
}

function pum_popup_data_attr( $popup_id = null ) {
	echo 'data-popmake="' . esc_attr( json_encode( pum_popup( $popup_id )->get_data_attr() ) ) . '"';
}


function pum_popup_close_text( $popup_id = null ) {
	esc_html_e( pum_popup( $popup_id )->close_text() );
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
	return boolval( pum_popup( $popup_id )->show_close_button() );
}