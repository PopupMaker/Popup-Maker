<?php

/**
 * @param null|int $popup_id
 */
function pum_popup_ID( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	echo $popup->ID;
}

/**
 * @param null|int $popup_id
 */
function pum_popup_title( $popup_id = null ) {
	echo pum_get_popup_title( $popup_id );
}

/**
 * @param null|int $popup_id
 */
function pum_popup_content( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	echo $popup->get_content();
}

/**
 * @param null|int $popup_id
 */
function pum_popup_theme_id( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	echo $popup->get_theme_id();
}

/**
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
 * @param null|int $popup_id
 */
function pum_popup_close_text( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	echo esc_html( $popup->close_text() );
}


/**
 * Conditional Template Tags.
 */

/**
 * Returns true if the close button should be shown.
 *
 * @param null|int $popup_id
 *
 * @return bool
 */
function pum_show_close_button( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return true;
	}

	return $popup->show_close_button();
}