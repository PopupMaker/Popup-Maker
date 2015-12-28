<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets all cookie triggers.
 *
 * @uses filter pum_cookie_trigger_options
 * @uses filter popmake_cookie_trigger_options @deprecated
 *
 * @return array $options
 */
function pum_trigger_cookie_options() {
	$options = apply_filters( 'pum_trigger_cookie_options', array(
		__( 'Disabled', 'popup-maker' ) => 'disabled',
		__( 'On Open', 'popup-maker' )  => 'open',
		__( 'On Close', 'popup-maker' ) => 'close',
		__( 'Manual', 'popup-maker' )   => 'manual',
	) );

	// Deprecated filter used by old extensions.
	$options = apply_filters( 'popmake_cookie_trigger_options', $options );

	return $options;
}

/**
 * Returns the cookie fields used for trigger options.
 *
 * @uses filter pum_trigger_cookie_fields
 *
 * @param array $custom_fields
 *
 * @return array
 */
function pum_trigger_cookie_fields() {
	return apply_filters( 'pum_trigger_cookie_fields', array(
		'name' => array(
			'label'       => __( 'Cookie Name', 'popup-maker' ),
			'desc'        => __( 'When do you want to create the cookie.', 'popup-maker' ),
			'type'        => 'select',
			'multiple'    => true,
			'chosen'    => true,
			'priority'    => 1,
			'options'     => array(
				__( 'Add New Cookie', 'popup-maker' ) => 'add_new'
			)
		),
	) );
}

/**
 * Returns an array of args for registering triggers.
 *
 * @uses filter pum_get_triggers
 *
 * @return array
 */
function pum_get_triggers() {
	return apply_filters( 'pum_get_triggers', array(
		'click_open' => array(
			'id' => 'click_open',
			'labels' => array(
				'name' => __( 'Click Open', 'popup-maker' ),
				'modal_title' => __( 'Click Trigger Settings', 'popup-maker' ),
				'settings_column' => sprintf(
					'<strong>%1$s</strong>: %2$s',
					__( 'Extra Selectors', 'popup-maker' ),
					'<%= extra_selectors %>'
				),
			),
			'<strong>Extra Selectors</strong>: <%= extra_selectors %>',
			'sections' => array(
				'general' => array(
					'title' => __( 'General', 'popup-maker' ),
				),
				'cookie' => array(
						'title' => __( 'Cookie', 'popup-maker' ),
				),
			),
			'fields' => array(
				'general' => array(
					'extra_selectors'          => array(
						'label'       => __( 'Extra CSS Selectors', 'popup-maker' ),
						'desc'        => __( 'This allows custom css classes, ids or selector strings to trigger the popup when clicked. Separate multiple selectors using commas.', 'popup-maker' ),
						'placeholder' => __( '.my-class, #button2', 'popup-maker' ),
						'priority'    => 1,
					),
				),
				'cookie' => pum_trigger_cookie_fields(),
			),
		),
		'auto_open' => array(
			'id' => 'auto_open',
			'labels' => array(
				'name' => __( 'Auto Open', 'popup-maker' ),
				'modal_title' => __( 'Auto Open Settings', 'popup-maker' ),
				'settings_column' => sprintf(
					'<strong>%1$s</strong>: %2$s',
					__( 'Delay', 'popup-maker' ),
					'<%= delay %>'
				),
			),
			'sections' => array(
				'general' => array(
					'title' => __( 'General', 'popup-maker' ),
				),
				'cookie' => array(
					'title' => __( 'Cookie', 'popup-maker' ),
				),
			),
			'fields' => array(
				'delay'          => array(
					'type'        => 'rangeslider',
					'label'       => __( 'Delay', 'popup-maker' ),
					'desc'        => __( 'The delay before the popup will open in milliseconds.', 'popup-maker' ),
					'std'         => 500,
					'min'         => 0,
					'max'         => 10000,
					'step'        => 500,
					'priority'    => 1,
				),
				'cookie' => pum_trigger_cookie_fields(),
			)
		),
	) );
}

/**
 * Registers triggers on the WP `init` action.
 *
 * @uses function pum_get_triggers
 */
function pum_register_triggers() {
	$triggers = pum_get_triggers();
	PUM_Triggers::instance()->add_triggers( $triggers );
}
add_action( 'init', 'pum_register_triggers' );
