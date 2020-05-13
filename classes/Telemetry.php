<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly
	exit;
}

/**
 * Our telemetry class.
 *
 * Handles sending usage data back to our servers for those who have opted into our telemetry.
 *
 * @since 1.11.0
 */
class PUM_Telemetry {

	/**
	 * Simple wrapper for sending check_in data
	 *
	 * @param array $data Telemetry data to send.
	 * @sice 1.11.0
	 */
	public function send_data( $data = array() ) {
		$this->api_call( 'check_in', $data );
	}

	/**
	 * Makes HTTP request to our API endpoint
	 *
	 * @param string $action The specific endpoint in our API.
	 * @param array $data Any data to send in the body.
	 * @return array|bool False if WP Error. Otherwise, array response from wp_remote_post.
	 * @since 1.11.0
	 */
	public function api_call( $action = '', $data = array() ) {
		$response = wp_remote_post( 'https://api.wppopupmaker.com/wp-json/pmapi/v1/' . $action, array(
			'method'      => 'POST',
			'timeout'     => 20,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => false,
			'body'        => $data,
			'user-agent'  => 'POPMAKE/' . Popup_Maker::$VER . '; ' . get_site_url(),
		));

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			PUM_Utils_Logging::instance()->log( sprintf( 'Cannot send telemetry data. Error received was: %s', esc_html( $error_message ) ) );
			return false;
		}

		return $response;
	}
}
