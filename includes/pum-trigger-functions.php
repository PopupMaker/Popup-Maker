<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Returns the cookie fields used for trigger options.
 *
 * @uses filter pum_trigger_cookie_fields
 *
 * @return array
 */
function pum_trigger_cookie_fields() {

	/**
	 * Filter the array of default trigger cookie fields.
	 *
	 * @param array $fields The list of trigger cookie fields.
	 */
	return apply_filters( 'pum_trigger_cookie_fields', array(
		'name' => pum_trigger_cookie_field(),
	) );
}

/**
 * Returns the cookie field used for trigger options.
 *
 * @uses filter pum_trigger_cookie_field
 *
 * @return array
 */
function pum_trigger_cookie_field() {

	/**
	 * Filter the array of default trigger cookie field.
	 *
	 * @param array $fields The list of trigger cookie field.
	 */
	return apply_filters( 'pum_trigger_cookie_field', array(
		'label'    => __( 'Cookie Name', 'popup-maker' ),
		'desc'     => __( 'When do you want to create the cookie.', 'popup-maker' ),
		'type'     => 'select',
		'multiple' => true,
		'select2'  => true,
		'priority' => 1,
		'options'  => array(
			__( 'Add New Cookie', 'popup-maker' ) => 'add_new',
		),
	) );
}

/**
 * Returns an array of section labels for all triggers.
 *
 * Use the filter pum_get_trigger_section_labels to add or modify labels.
 *
 * @return array
 */
function pum_get_trigger_section_labels() {

	/**
	 * Filter the array of trigger section labels.
	 *
	 * @param array $to_do The list of trigger section labels.
	 */
	return apply_filters( 'pum_get_trigger_section_labels', array(
		'general' => array(
			'title' => __( 'General', 'popup-maker' ),
		),
		'cookie'  => array(
			'title' => __( 'Cookie', 'popup-maker' ),
		),
	) );
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

/**
 * Returns an array of args for registering triggers.
 *
 * @see pum_trigger_cookie_fields
 *
 * @return array
 */
function pum_get_triggers() {

	/**
	 * Filter the array of pum triggers to register.
	 *
	 * Use this filter to add additional triggers to the Popup Maker forms.
	 *
	 * @param array $to_do The list of pum triggers to register.
	 */
	return apply_filters( 'pum_get_triggers', array(
		'click_open' => array(
			'fields' => array(
				'general' => array(
					'extra_selectors' => array(
						'label'       => __( 'Extra CSS Selectors', 'popup-maker' ),
						'desc'        => __( 'This allows custom css classes, ids or selector strings to trigger the popup when clicked. Separate multiple selectors using commas.', 'popup-maker' ),
						'placeholder' => __( '.my-class, #button2', 'popup-maker' ),
						'doclink'     => 'http://docs.wppopupmaker.com/article/147-getting-css-selectors?page-popup-editor=&utm_medium=inline-doclink&utm_campaign=ContextualHelp&utm_content=extra-selectors',
					),
					'do_default'      => array(
						'type'  => 'checkbox',
						'label' => __( 'Do not prevent the default click functionality.', 'popup-maker' ),
						'desc'  => __( 'This prevents us from disabling the browsers default action when a trigger is clicked. It can be used to allow a link to a file to both trigger a popup and still download the file.', 'popup-maker' ),
					),
					//'cookie' => pum_trigger_cookie_field(),
				),
				'cookie'  => pum_trigger_cookie_fields(),
			),
		),
		'auto_open'  => array(
			'fields' => array(
				'general' => array(
					'delay' => array(
						'type'  => 'rangeslider',
						'label' => __( 'Delay', 'popup-maker' ),
						'desc'  => __( 'The delay before the popup will open in milliseconds.', 'popup-maker' ),
						'std'   => 500,
						'min'   => 0,
						'max'   => 10000,
						'step'  => 500,
					),
					//'cookie' => pum_trigger_cookie_field(),
				),
				'cookie'  => pum_trigger_cookie_fields(),
			),
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

add_action( 'init', 'pum_register_triggers', 11 );
