<?php

function pum_popup_title( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	echo $popup->get_title();
}

function pum_popup_content( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	echo $popup->get_content();
}

function pum_popup_theme_id( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	echo $popup->get_theme_id();
}

function pum_popup_classes( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	esc_attr_e( implode( ' ', $popup->get_classes() ) );
}

function pum_popup_data_attr( $popup_id = null ) {
	$popup = new PUM_Popup( $popup_id );
	esc_attr_e( implode( ' ', $popup->get_data_attr() ) );
	echo 'data-popmake="' . esc_attr( json_encode( $popup->get_data_attr() ) ) . '"';
}
