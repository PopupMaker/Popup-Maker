<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
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

	if ( ! $provider ) {
		return '';
	}

	return $provider->get_message( $context, $values );
}

function pum_get_newsletter_admin_localized_vars() {
	return array(
		'default_provider' => PUM_Options::get( 'newsletter_default', '' ),
	);
}