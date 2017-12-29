<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Newsletters {

	/**
	 * @var WP_Error
	 */
	public static $errors;

	public static function init() {
		add_action( 'wp_ajax_pum_sub_form', array( __CLASS__, 'ajax_request' ) );
		add_action( 'wp_ajax_nopriv_pum_sub_form', array( __CLASS__, 'ajax_request' ) );
		do_action( 'pum_newsletter_init' );
	}

	/**
	 * Submits the form using ajax
	 */
	public static function ajax_request() {
		self::$errors = new WP_Error;

		$values = isset( $_REQUEST['values'] ) ? $_REQUEST['values'] : array();

		$values = wp_parse_args( $values, array(
			'provider' => pum_get_option( 'newsletter_default_provider', 'none' ),
		) );

		$values['provider'] = sanitize_text_field( $values['provider'] );

		do_action( 'pum_sub_form_ajax_override', $values );

		// Allow sanitization & manipulation of form values prior to usage.
		$values = apply_filters( 'pum_sub_form_sanitization', $values );

		if ( ! isset( $values["email"] ) || empty( $values["email"] ) ) {
			self::$errors->add( 'empty_email', '', 'email' );
		} elseif ( ! is_email( $values["email"] ) ) {
			self::$errors->add( 'invalid_email', '', 'email' );
		}

		// Allow validation of the data.
		self::$errors = apply_filters( 'pum_sub_form_validation', self::$errors, $values );

		if ( self::$errors->get_error_code() ) {
			self::send_errors( self::$errors );
		}

		$response = array();

		// Process the submission and pass the $response array as a reference variable.
		do_action_ref_array( 'pum_sub_form_submission', array( $values, &$response, &self::$errors ) );

		if ( ! self::$errors->get_error_code() ) {

			$response["message"] = pum_get_newsletter_provider_message( $values['provider'], 'success', $values );
			self::send_success( $response );

		} elseif ( self::$errors->get_error_code() == 'already_subscribed' ) {

			$response["message"] = pum_get_newsletter_provider_message( $values['provider'], 'already_subscribed', $values );
			self::send_success( $response );

		} else {

			switch ( self::$errors->get_error_code() ) {

				case 'api_errors':
					$response['message'] = pum_get_newsletter_provider_message( $values['provider'], 'error', $values );
					break;

			}

			self::send_errors( self::$errors, $response );
		}
		// Don't let it keep going.
		die();
	}

	/**
	 * Process and send error messages.
	 *
	 * Optionally pass extra data to send back to front end.
	 *
	 * @param $errors WP_Error
	 * @param array $extra_response_args
	 */
	public static function send_errors( WP_Error $errors, $extra_response_args = array() ) {
		if ( ! $errors || ! is_wp_error( $errors ) ) {
			$errors = self::$errors;
		}

		$response = array_merge( $extra_response_args, array(
			'errors' => self::prepare_errors( $errors ),
		) );

		wp_send_json_error( $response );

		die();
	}

	/**
	 * Send a success response with passed data.
	 *
	 * @param array|mixed $response
	 */
	public static function send_success( $response = array() ) {
		wp_send_json_success( array_filter( $response ) );
		die;
	}

	/**
	 * Prepare errors for response.
	 *
	 * @param WP_Error $_errors
	 *
	 * @return array
	 */
	public static function prepare_errors( WP_Error $_errors ) {
		if ( ! $_errors || ! is_wp_error( $_errors ) ) {
			$_errors = self::$errors;
		}

		$errors = array();

		foreach ( $_errors->get_error_codes() as $code ) {
			$errors[] = array(
				'code'    => $code,
				'field'   => $_errors->get_error_data( $code ),
				'message' => $_errors->get_error_message( $code ),
			);
		}

		return $errors;
	}

}
