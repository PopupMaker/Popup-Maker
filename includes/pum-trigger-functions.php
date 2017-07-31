<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Returns the cookie fields used for trigger options.
 *
 * @return array
 */
function pum_trigger_cookie_fields() {
	return PUM_Triggers::cookie_fields();
}

/**
 * Returns the cookie field used for trigger options.
 *
 * @return array
 */
function pum_trigger_cookie_field() {
	return PUM_Triggers::cookie_field();
}

/**
 * Returns an array of section labels for all triggers.
 *
 * Use the filter pum_get_trigger_section_labels to add or modify labels.
 *
 * @return array
 */
function pum_get_trigger_section_labels() {
	return PUM_Triggers::instance()->tabs();
}

/**
 * Returns an array of trigger labels.
 *
 * Use the filter pum_get_trigger_labels to add or modify labels.
 *
 * @return array
 */
function pum_get_trigger_labels() {

	/**
	 * Filter the array of trigger labels.
	 *
	 * @param array $to_do The list of trigger labels.
	 */
	return apply_filters( 'pum_get_trigger_labels', array(
		'click_open' => array(
			'name'            => __( 'Click Open', 'popup-maker' ),
			'modal_title'     => __( 'Click Trigger Settings', 'popup-maker' ),
			'settings_column' => sprintf( '<strong>%1$s</strong>: %2$s', __( 'Extra Selectors', 'popup-maker' ), '{{data.extra_selectors}}' ),
		),
		'auto_open'  => array(
			'name'            => __( 'Auto Open', 'popup-maker' ),
			'modal_title'     => __( 'Auto Open Settings', 'popup-maker' ),
			'settings_column' => sprintf( '<strong>%1$s</strong>: %2$s', __( 'Delay', 'popup-maker' ), '{{data.delay}}' ),
		),
	) );
}
