<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @deprecated 1.7.0 Here to prevent PUM_Newsletter_  classes from being loaded anywhere except from core.
 *
 * @param $class
 */
function pum_newsletter_autoloader( $class ) {}

/**
 * @param $provider_id
 *
 * @return bool|PUM_Newsletter_Provider
 */
function pum_get_newsletter_provider( $provider_id ) {
	$providers = PUM_Newsletter_Providers::instance()->get_providers();

	return isset( $providers[ $provider_id ] ) ? $providers[ $provider_id ] : false;
}

/**
 * @param string $provider_id
 * @param string $context
 * @param array $values
 *
 * @return mixed|string
 */
function pum_get_newsletter_provider_message( $provider_id, $context, $values = array() ) {
	$provider = pum_get_newsletter_provider( $provider_id );
	$default = pum_get_newsletter_default_messages( $context );

	if ( ! $provider ) {
		return $default;
	}

	$message = $provider->get_message( $context, $values );

	return ! empty( $message ) ? $message : $default;
}

/**
 * Gets default messages.
 *
 * @param null $context
 *
 * @return array|mixed|string
 */
function pum_get_newsletter_default_messages( $context = null ) {
	$messages = array(
		'success'               => pum_get_option('default_success_message', __( 'You have been subscribed!', 'popup-maker' ) ),
		'double_opt_in_success' => pum_get_option('default_double_opt_in_success_message',__( 'Please check your email and confirm your subscription.', 'popup-maker' ) ),
		'error'                 => pum_get_option('default_error_message',__( 'Error occurred when subscribing. Please try again.', 'popup-maker' ) ),
		'already_subscribed'    => pum_get_option('default_already_subscribed_message',__( 'You are already a subscriber.', 'popup-maker' ) ),
		'empty_email'           => pum_get_option('default_empty_email_message',__( 'Please enter a valid email.', 'popup-maker' ) ),
		'invalid_email'         => pum_get_option('default_invalid_email_message',__( 'Email provided is not a valid email address.', 'popup-maker' ) ),
		'consent_required'      => pum_get_option('default_consent_required_message',__( 'Email provided is not a valid email address.', 'popup-maker' ) ),
	);

	if ( $context ) {
		return isset( $messages[ $context ] ) ? $messages[ $context ] : '';
	}

	return $messages;
}

/**
 * @return array
 */
function pum_get_newsletter_admin_localized_vars() {
	return array(
		'default_provider' => pum_get_option( 'newsletter_default_provider', pum_get_option( 'newsletter_default', '' ) ),
	);
}
