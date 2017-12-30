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

		add_filter( 'pum_sub_form_sanitization', array( __CLASS__, 'sanitization' ), 0 );
		add_filter( 'pum_sub_form_validation', array( __CLASS__, 'validation' ), 0, 2 );
		add_action( 'pum_sub_form_success', array( __CLASS__, 'record_submission' ), 0 );

		do_action( 'pum_newsletter_init' );
	}

	/**
	 * Submits the form using ajax
	 */
	public static function ajax_request() {
		self::$errors = new WP_Error;

		$values = isset( $_REQUEST['values'] ) ? $_REQUEST['values'] : array();


		do_action( 'pum_sub_form_ajax_override', $values );

		// Allow sanitization & manipulation of form values prior to usage.
		$values = apply_filters( 'pum_sub_form_sanitization', $values );

		// Allow validation of the data.
		self::$errors = apply_filters( 'pum_sub_form_validation', self::$errors, $values );

		if ( self::$errors->get_error_code() ) {
			self::send_errors( self::$errors );
		}

		$response = array();

		// Process the submission and pass the $response array as a reference variable so data can be added..
		do_action_ref_array( 'pum_sub_form_submission', array( $values, &$response, &self::$errors ) );

		$success = ! self::$errors->get_error_code() ? 'success' : self::$errors->get_error_code() == 'already_subscribed' ? 'already_subscribed' : false;

		if ( ! $success ) {
			do_action( 'pum_sub_form_errors', $values, self::$errors );

			switch ( self::$errors->get_error_code() ) {
				case 'api_errors':
					$response['message'] = pum_get_newsletter_provider_message( $values['provider'], 'error', $values );
					break;

			}
			self::send_errors( self::$errors, $response );
		} else {
			do_action( 'pum_sub_form_success', $values );

			$response["message"] = pum_get_newsletter_provider_message( $values['provider'], $success, $values );
			self::send_success( $response );
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

	/**
	 * Records the submission into a database table.
	 *
	 * @param array $values
	 */
	public static function record_submission( $values = array() ) {
		$data = wp_parse_args( $values, array(
			'uuid'       => self::uuid(),
			'user_id'    => get_current_user_id(),
			'popup_id'   => 0,
			'email_hash' => '',
			'email'      => '',
			'name'       => '',
			'fname'      => '',
			'lname'      => '',
		) );

		$data['values'] = maybe_serialize( $values );

		PUM_Subscribers::instance()->insert( $data );
	}

	/**
	 * Return the current or new uuid.
	 *
	 * @return mixed|string
	 */
	public static function uuid() {
		static $uuid;

		if ( ! isset( $uuid ) ) {
			$uuid = PUM_GA::get_uuid();
		}

		return $uuid;
	}

	/**
	 * Provides basic field sanitization.
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	public static function sanitization( $values = array() ) {
		$values = wp_parse_args( $values, array(
			'provider' => pum_get_option( 'newsletter_default_provider', 'none' ),
		) );

		$values['provider'] = sanitize_text_field( $values['provider'] );

		$name = isset( $values["name"] ) ? sanitize_text_field( $values["name"] ) : '';

		//Creates last name
		$name = explode( " ", $name );
		if ( ! isset( $name[1] ) ) {
			$name[1] = '';
		}

		$values['fname'] = $name[0];
		$values['lname'] = $name[1];

		$values['email']      = sanitize_email( $values["email"] );
		$values['email_hash'] = md5( $values['email'] );

		return $values;
	}

	/**
	 * Provides basic field validation.
	 *
	 * @param WP_Error $errors
	 * @param array $values
	 *
	 * @return WP_Error
	 */
	public static function validation( $errors, $values = array() ) {
		if ( ! isset( $values["email"] ) || empty( $values["email"] ) ) {
			$errors->add( 'empty_email', pum_get_newsletter_provider_message( $values['provider'], 'empty_email', $values ), 'email' );
		} elseif ( ! is_email( $values["email"] ) ) {
			$errors->add( 'invalid_email', pum_get_newsletter_provider_message( $values['provider'], 'invalid_email', $values ), 'email' );
		}

		return $errors;
	}


}
