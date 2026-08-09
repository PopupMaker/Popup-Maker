<?php
/**
 * Functions for Popups Template
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Render the popup ID
 *
 * @param null|int|string $popup_id Popup ID.
 */
function pum_popup_ID( $popup_id = null ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	echo absint( pum_get_popup_id( $popup_id ) );
}

/**
 * Render the popup title.
 *
 * @param null|int $popup_id Popup ID.
 */
function pum_popup_title( $popup_id = null ) {
	echo esc_html( pum_get_popup_title( $popup_id ) );
}

/**
 * Render the popup content.
 *
 * @param null|int $popup_id Popup ID.
 */
function pum_popup_content( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	$cached_content = \PopupMaker\plugin()->get_controller( 'Frontend\Popups' )->get_content_cache( $popup->ID );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo null !== $cached_content ? $cached_content : $popup->get_content();
}

/**
 * Render the chose popup elements classes.
 *
 * @param null   $popup_id Popup ID.
 * @param string $element Element to get classes for.
 */
function pum_popup_classes( $popup_id = null, $element = 'overlay' ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	echo esc_attr( implode( ' ', $popup->get_classes( $element ) ) );
}

/**
 * Render close button attributes.
 *
 * @param null|int $popup_id Popup ID.
 */
function pum_popup_close_button_attributes( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	$attributes = apply_filters(
		'pum_popup_close_button_attributes',
		[
			'type'       => 'button',
			'class'      => implode( ' ', $popup->get_classes( 'close' ) ),
			'aria-label' => __( 'Close', 'popup-maker' ),
		],
		$popup
	);

	if ( ! is_array( $attributes ) ) {
		return;
	}

	foreach ( $attributes as $name => $value ) {
		$name = preg_replace( '/[^a-zA-Z0-9:_-]/', '', (string) $name );

		if ( ! $name || null === $value || false === $value || ( true !== $value && ! is_scalar( $value ) ) ) {
			continue;
		}

		printf( ' %s="%s"', esc_attr( $name ), esc_attr( true === $value ? $name : $value ) );
	}
}

/**
 * Render the popups data attribute.
 *
 * @param null|int $popup_id Popup ID.
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
 *
 * @param null|int $popup_id Popup ID.
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
 * @param null|int $popup_id Popup ID.
 */
function pum_popup_close_text( $popup_id = null ) {
	$popup = pum_get_popup( $popup_id );

	if ( ! pum_is_popup( $popup ) ) {
		return;
	}

	$close_text = $popup->close_text();

	// If the close text is a font awesome icon (E.g. "fas fa-camera"), add the icon instead of the text.
	if ( preg_match( '/^fa[srldb]?\s.+/i', $close_text ) || preg_match( '/^fa-((solid)|(regular)|(light)|(thin)|(duotone))?\sfa[-]?.+/i', $close_text ) ) {
		echo '<i class="' . esc_attr( $close_text ) . '"></i>';
	} else {
		echo esc_html( $close_text );
	}
}
