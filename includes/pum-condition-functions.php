<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets all condition conditions.
 *
 * @uses filter pum_condition_condition_options
 * @uses filter popmake_condition_condition_options @deprecated
 *
 * @return array $options
 */
function pum_condition_condition_options() {
	$options = apply_filters( 'pum_condition_condition_options', array(
		__( 'Disabled', 'popup-maker' ) => 'disabled',
		__( 'On Open', 'popup-maker' )  => 'open',
		__( 'On Close', 'popup-maker' ) => 'close',
		__( 'Manual', 'popup-maker' )   => 'manual',
	) );

	// Deprecated filter used by old extensions.
	$options = apply_filters( 'popmake_condition_condition_options', $options );

	return $options;
}

/**
 * Returns the condition fields used for condition options.
 *
 * @uses filter pum_condition_condition_fields
 *
 * @param array $stds
 *
 * @return array
 *
 */
function pum_get_condition_fields() {
	return apply_filters( 'pum_get_condition_fields', array(
		'name'    => array(
			'label'       => __( 'Condition Name', 'popup-maker' ),
			'placeholder' => __( 'Condition Name ex. popmaker-123', 'popup-maker' ),
			'desc'        => __( 'The name that will be used when checking for or saving this condition.', 'popup-maker' ),
			'std'         => '',
			'priority'    => 1,
		),
		'key'     => array(
			'label'    => __( 'Condition Key', 'popup-maker' ),
			'desc'     => __( 'Changing this will cause all existing conditions to be invalid.', 'popup-maker' ),
			'type'     => 'conditionkey',
			'std'      => '',
			'priority' => 2,
		),
		'session' => array(
			'label'    => __( 'Use Session Condition?', 'popup-maker' ),
			'desc'     => __( 'Session conditions expire when the user closes their browser.', 'popup-maker' ),
			'type'     => 'checkbox',
			'std'      => false,
			'priority' => 3,
		),
		'time'    => array(
			'label'       => __( 'Condition Time', 'popup-maker' ),
			'placeholder' => __( '364 days 23 hours 59 minutes 59 seconds', 'popup-maker' ),
			'desc'        => __( 'Enter a plain english time before condition expires.', 'popup-maker' ),
			'std'         => '1 month',
			'priority'    => 4,
		),
		'path'    => array(
			'label'    => __( 'Sitewide Condition', 'popup-maker' ),
			'desc'     => __( 'This will prevent the popup from triggering on all pages until the condition expires.', 'popup-maker' ),
			'type'     => 'checkbox',
			'std'      => true,
			'priority' => 5,
		),
	) );
}

/**
 * Returns an array of args for registering coo0kies.
 *
 * @uses filter pum_get_conditions
 *
 * @return array
 */
function pum_get_conditions() {
	return apply_filters( 'pum_get_conditions', array(
		'on_popup_open'  => array(
			'labels' => array(
				'name' => __( 'On Popup Open', 'popup-maker' ),
			),
			'fields' => pum_get_condition_fields(),
		),
		'on_popup_close' => array(
			'labels' => array(
				'name' => __( 'On Popup Close', 'popup-maker' ),
			),
			'fields' => pum_get_condition_fields(),
		),
		'manual'         => array(
			'labels' => array(
				'name' => __( 'Manual JavaScript', 'popup-maker' ),
			),
			'fields' => pum_get_condition_fields(),
		),
	) );
}

/**
 * Registers conditions on the WP `init` action.
 *
 * @uses function pum_get_conditions
 */
function pum_register_conditions() {
	$conditions = pum_get_conditions();
	PUM_Conditions::instance()->add_conditions( $conditions );
}

add_action( 'init', 'pum_register_conditions' );
