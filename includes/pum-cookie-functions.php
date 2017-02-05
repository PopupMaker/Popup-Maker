<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		'name'    => array(
			'label'       => __( 'Cookie Name', 'popup-maker' ),
			'placeholder' => __( 'Cookie Name ex. popmaker-123', 'popup-maker' ),
			'desc'        => __( 'The name that will be used when checking for or saving this cookie.', 'popup-maker' ),
			'std'         => '',
			'priority'    => 1,
		),
		'key'     => array(
			'label'    => __( 'Cookie Key', 'popup-maker' ),
			'desc'     => __( 'Changing this will cause all existing cookies to be invalid.', 'popup-maker' ),
			'type'     => 'cookiekey',
			'std'      => '',
			'priority' => 2,
		),
		'session' => array(
			'label'    => __( 'Use Session Cookie?', 'popup-maker' ),
			'desc'     => __( 'Session cookies expire when the user closes their browser.', 'popup-maker' ),
			'type'     => 'checkbox',
			'std'      => false,
			'priority' => 3,
		),
		'time'    => array(
			'label'       => __( 'Cookie Time', 'popup-maker' ),
			'placeholder' => __( '364 days 23 hours 59 minutes 59 seconds', 'popup-maker' ),
			'desc'        => __( 'Enter a plain english time before cookie expires.', 'popup-maker' ),
			'std'         => '1 month',
			'priority'    => 4,
		),
		'path'    => array(
			'label'    => __( 'Sitewide Cookie', 'popup-maker' ),
			'desc'     => __( 'This will prevent the popup from triggering on all pages until the cookie expires.', 'popup-maker' ),
			'type'     => 'checkbox',
			'std'      => true,
			'priority' => 5,
		),
	) );
}

/**
 * Returns an array of cookie labels.
 *
 * Use the filter pum_get_cookie_labels to add or modify labels.
 *
 * @return array
 */
function pum_get_cookie_labels() {

	/**
	 * Filter the array of cookie labels.
	 *
	 * @param array $to_do The list of cookie labels.
	 */
	return apply_filters( 'pum_get_cookie_labels', array(
		'on_popup_open'  => array(
			'name'        => __( 'On Popup Open', 'popup-maker' ),
			'modal_title' => __( 'On Popup Open Settings', 'popup-maker' ),
		),
		'on_popup_close' => array(
			'name'        => __( 'On Popup Close', 'popup-maker' ),
			'modal_title' => __( 'On Popup Close Settings', 'popup-maker' ),
		),
		'manual'         => array(
			'name'        => __( 'Manual JavaScript', 'popup-maker' ),
			'modal_title' => __( 'Click Trigger Settings', 'popup-maker' ),
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
		'on_popup_open'  => array(
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
		'manual'         => array(
			'labels' => array(
				'name' => __( 'Manual JavaScript', 'popup-maker' ),
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
	$cookies = pum_get_cookies();
	PUM_Cookies::instance()->add_cookies( $cookies );
}

add_action( 'init', 'pum_register_cookies', 11 );
