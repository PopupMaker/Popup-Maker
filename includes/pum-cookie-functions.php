<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets all cookie cookies.
 *
 * @uses filter pum_cookie_cookie_options
 * @uses filter popmake_cookie_cookie_options @deprecated
 *
 * @return array $options
 */
function pum_cookie_cookie_options() {
	$options = apply_filters( 'pum_cookie_cookie_options', array(
		__( 'Disabled', 'popup-maker' ) => 'disabled',
		__( 'On Open', 'popup-maker' )  => 'open',
		__( 'On Close', 'popup-maker' ) => 'close',
		__( 'Manual', 'popup-maker' )   => 'manual',
	) );

	// Deprecated filter used by old extensions.
	$options = apply_filters( 'popmake_cookie_cookie_options', $options );

	return $options;
}

/**
 * Returns the cookie fields used for cookie options.
 *
 * @uses filter pum_cookie_cookie_fields
 *
 * @param array $custom_fields
 *
 * @return array
 */
function pum_cookie_cookie_fields( $custom_fields = array() ) {
	return apply_filters( 'pum_cookie_cookie_fields', array_merge( array(
		'cookie' => array(
			'label'       => __( 'Cookie Cookie', 'popup-maker' ),
			'desc'        => __( 'When do you want to create the cookie.', 'popup-maker' ),
			'type'        => 'select',
			'std'         => 'close',
			'priority'    => 1,
			'options'     => pum_cookie_cookie_options(),
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

/**
 * Returns an array of args for registering cookies.
 *
 * @uses filter pum_get_cookies
 *
 * @return array
 */
function pum_get_cookies() {
	return apply_filters( 'pum_get_cookies', array(
		'click_open' => array(
			'id' => 'click_open',
			'labels' => array(
				'name' => __( 'Click Open', 'popup-maker' ),
				'modal_title' => __( 'Click Cookie Settings', 'popup-maker' ),
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
			),
			'fields' => array(
				'general' => array(
					'extra_selectors'          => array(
						'label'       => __( 'Extra CSS Selectors', 'popup-maker' ),
						'desc'        => __( 'This allows custom css classes, ids or selector strings to cookie the popup when clicked. Separate multiple selectors using commas.', 'popup-maker' ),
						'placeholder' => __( '.my-class, #button2', 'popup-maker' ),
						'priority'    => 1,
					),
				),
			),
		),
		'auto_open' => array(
			'id' => 'auto_open',
			'labels' => array(
				'name' => __( 'Auto Open', 'popup-maker' ),
				'modal_title' => __( 'Auto Open Settings', 'popup-maker' ),
				'settings_column' => sprintf(
					'<strong>%1$s</strong>: %2$s <strong>%3$s</strong>: %4$s',
					__( 'Delay', 'popup-maker' ),
					'<%= delay %>',
					__( 'Cookie', 'popup-maker' ),
					sprintf( '%s%s%s',
						'<%= I10n.labels.cookie_cookies[cookie.cookie] %><% if (cookie.cookie !== "disabled") { %> / <% if (typeof cookie.session === "undefined") { %><%= cookie.time %><% } else { %>',
						__( 'Sessions', 'popup-maker' ),
						'<% } %><% } %>'
					)
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
				'cookie' => pum_cookie_cookie_fields(),
			)
		),
	) );
}

/**
 * Registers cookies on the WP `init` action.
 *
 * @uses function pum_get_cookies
 */
function pum_register_cookies() {
	$cookies = pum_get_cookies();
	PUM_Cookies::instance()->add_cookies( $cookies );
}
add_action( 'init', 'pum_register_cookies' );
