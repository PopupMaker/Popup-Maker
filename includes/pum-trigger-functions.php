<?php

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
function pum_trigger_cookie_fields( $custom_fields = array() ) {
	return apply_filters( 'pum_trigger_cookie_fields', array_merge( array(
		'trigger' => array(
			'label'       => __( 'Cookie Trigger', 'popup-maker' ),
			'desc'        => __( 'When do you want to create the cookie.', 'popup-maker' ),
			'type'        => 'select',
			'std'         => 'close',
			'priority'    => 1,
			'options'     => pum_trigger_cookie_options(),
		),
		'session' => array(
			'label'       => __( 'Use Session Cookie?', 'popup-maker' ),
			'desc'        => __( 'Session cookies expire when the user closes their browser.', 'popup-maker' ),
			'type'        => 'checkbox',
			'std'         => false,
			'priority'    => 2,
		),
		'time'    => array(
			'label'       => __( 'Cookie Time', 'popup-maker' ),
			'placeholder' => __( '364 days 23 hours 59 minutes 59 seconds', 'popup-maker' ),
			'desc'        => __( 'Enter a plain english time before cookie expires.', 'popup-maker' ),
			'std'         => '1 month',
			'priority'    => 3,
		),
		'path'    => array(
			'label'       => __( 'Sitewide Cookie', 'popup-maker' ),
			'desc'        => __( '	This will prevent the popup from auto opening on any page until the cookie expires.', 'popup-maker' ),
			'type'        => 'checkbox',
			'std'         => true,
			'priority'    => 4,
		),
		'key'     => array(
			'label'       => __( 'Cookie Key', 'popup-maker' ),
			'desc'        => __( 'Resetting this will cause all existing cookies to be invalid.', 'popup-maker' ),
			'std'         => '',
			'priority'    => 5,
		),
	), $custom_fields ) );
}

function pum_get_triggers() {
	return apply_filters( 'pum_triggers', array(
		'auto_open' => array(
			'id' => 'auto_open',
			'labels' => array(
				'modal_title' => __( 'Auto Open Settings', 'popup-maker' ),
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
		'click_open' => array(
			'id' => 'click_open',
			'labels' => array(
				'modal_title' => __( 'Click Trigger Settings', 'popup-maker' ),
			),
			'sections' => array(
				'general' => array(
					'title' => __( 'General', 'popup-maker' ),
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
			)
		)
	) );
}

function pum_register_triggers() {
	$triggers = pum_get_triggers();

	PUM_Triggers::instance()->add_triggers( $triggers );
}
add_action( 'init', 'pum_register_triggers' );
