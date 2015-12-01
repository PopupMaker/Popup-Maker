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
 * @param array $stds
 *
 * @return array
 *
 */
function pum_get_cookie_fields() {
	return apply_filters( 'pum_get_cookie_fields', array(
		'name' => array(
			'label'       => __( 'Cookie Name', 'popup-maker' ),
			'placeholder'        => __( 'Cookie Name ex. popmaker-123', 'popup-maker' ),
			'desc'        => __( 'The name that will be used when checking for or saving this cookie.', 'popup-maker' ),
			'std'         => 'popmake-123',
			'priority'    => 1,
		),
		'key'     => array(
			'label'       => __( 'Cookie Key', 'popup-maker' ),
			'desc'        => __( 'Changing this will cause all existing cookies to be invalid.', 'popup-maker' ),
			'type'        => 'cookiekey',
			'std'         => '',
			'priority'    => 2,
		),
		'session' => array(
			'label'       => __( 'Use Session Cookie?', 'popup-maker' ),
			'desc'        => __( 'Session cookies expire when the user closes their browser.', 'popup-maker' ),
			'type'        => 'checkbox',
			'std'         => false,
			'priority'    => 3,
		),
		'time'    => array(
			'label'       => __( 'Cookie Time', 'popup-maker' ),
			'placeholder' => __( '364 days 23 hours 59 minutes 59 seconds', 'popup-maker' ),
			'desc'        => __( 'Enter a plain english time before cookie expires.', 'popup-maker' ),
			'std'         => '1 month',
			'priority'    => 4,
		),
		'path'    => array(
			'label'       => __( 'Sitewide Cookie', 'popup-maker' ),
			'desc'        => __( 'This will prevent the popup from triggering on all pages until the cookie expires.', 'popup-maker' ),
			'type'        => 'checkbox',
			'std'         => true,
			'priority'    => 5,
		),
	) );
}

/**
 * Returns an array of args for registering coo0kies.
 *
 * @uses filter pum_get_cookies
 *
 * @return array
 */
function pum_get_cookies() {
	return apply_filters( 'pum_get_cookies', array(
			'on_popup_open' => array(
				'labels' => array(
					'name' => __( 'On Popup Open', 'popup-maker' ),
				),
				'fields' => pum_get_cookie_fields(),
			),
			'on_popup_close' => array(
				'labels' => array(
					'name' => __( 'On Popup Close', 'popup-maker' ),
				),
				'fields' => pum_get_cookie_fields(),
			),
			'manual' => array(
				'labels' => array(
					'name' => __( 'On Popup Close', 'popup-maker' ),
				),
				'fields' => pum_get_cookie_fields(),
			),
	) );
}

/**
 * Registers cookies on the WP `init` action.
 *
 * @uses function pum_get_cookies
 */
function pum_register_cookies() {
global $post;

	$cookies = pum_get_cookies();
	PUM_Cookies::instance()->add_cookies( $cookies );
}
add_action( 'wp', 'pum_register_cookies', 1000 );
