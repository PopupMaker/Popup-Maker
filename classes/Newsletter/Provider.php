<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

abstract class PUM_Newsletter_Provider {

	/**
	 * @var string Option name prefix.
	 */
	public $opt_prefix = '';

	/**
	 * @var string $id email provider name such as 'mailchimp'
	 */
	public $id = '';

	/**
	 * @var string email provider name for labeling such as 'MailChimp's
	 */
	public $name = '';

	/**
	 * The constructor method which sets up all filters and actions to prepare fields and messages
	 */
	public function __construct() {
		/** Register Provider Globally */
		PUM_Newsletter_Providers::instance()->add_provider( $this );

		/** Settings */
		add_filter( 'pum_settings_fields', array( $this, 'register_settings' ) );
		add_filter( 'pum_settings_tab_sections', array( $this, 'register_settings_tab_section' ) );
		/**
		 * Don't add the shortcodes or default options or process anything if the provider is disabled.
		 */
		if ( ! $this->enabled() ) {
			return;
		}

		/** Shortcodes Fields */
		add_filter( 'pum_sub_form_shortcode_sections', array( $this, 'shortcode_sections' ) );
		add_filter( "pum_sub_form_shortcode_fields", array( $this, 'shortcode_fields' ) );
		add_filter( 'pum_sub_form_shortcode_defaults', array( $this, 'shortcode_defaults' ) );

		/** Forms Processing & AJAX */
		add_filter( 'pum_sub_form_sanitization', array( $this, '_form_sanitization' ), 10 );
		add_filter( 'pum_sub_form_validation', array( $this, '_form_validation' ), 10, 2 );
		add_action( 'pum_sub_form_submission', array( $this, '_form_submission' ), 10, 3 );

		/** Form Rendering */
		add_action( 'pum_sub_form_fields', array( $this, 'render_fields' ) );
	}

	#region Abstract Methods

	/**
	 * Determines whether to load this providers fields in the shortcode editor among other things.
	 *
	 * @return bool
	 */
	abstract public function enabled();

	/**
	 * Contains each providers unique fields.
	 *
	 * @return array
	 */
	abstract public function fields();

	/**
	 * Contains defaults for the providers unique fields.
	 *
	 * @return array
	 */
	abstract public function defaults();

	/**
	 * Contains each providers unique global settings.
	 *
	 * @return array
	 */
	abstract public function register_settings();

	#endregion

	#region Overloadable Methods.

	/**
	 * Contains each providers unique global settings tab sections..
	 *
	 * @param array $sections
	 *
	 * @return array
	 */
	public function register_settings_tab_section( $sections = array() ){
		$sections['subscriptions'][ $this->id ] = $this->name;

		return $sections;
	}


	/**
	 * Creates the inputs for each of the needed fields for the email provider
	 *
	 * TODO Determine how this should really work for visible custom fields.
	 *
	 * @param $shortcode_atts
	 */
	public function render_fields( $shortcode_atts ) {
		foreach ( $this->fields() as $key => $field ) {
			echo '<input type="hidden" name="' . $key . '" value="' . $shortcode_atts[ $key ] . '" />';
		}
	}

	/**
	 * @param array $values
	 *
	 * @return array $values
	 */
	public function form_sanitization( $values = array() ) {
		return $values;
	}

	/**
	 * @param \WP_Error $errors
	 * @param array $values
	 *
	 * @return \WP_Error
	 */
	public function form_validation( \WP_Error $errors, $values = array() ) {
		return $errors;
	}

	/**
	 * Subscribes the user to the list
	 *
	 * @param $values
	 * @param array $json_response
	 * @param \WP_Error $errors
	 *
	 * @return void
	 */
	public function form_submission( $values, &$json_response, \WP_Error &$errors ) {

	}

	#endregion

	#region Internal Methods - These should not be overloaded

	/**
	 * @param array $values
	 *
	 * @return array $values
	 */
	public function _form_sanitization( $values = array() ) {
		if ( $this->id != $values['provider'] && ( $values['provider'] == 'none' && PUM_Options::get( 'newsletter_default_provider' ) !== $this->id ) ) {
			return $values;
		}

		return $this->form_sanitization( $values );
	}

	/**
	 * @param \WP_Error $errors
	 * @param array $values
	 *
	 * @return \WP_Error
	 */
	public function _form_validation( \WP_Error $errors, $values = array() ) {
		if ( $this->id != $values['provider'] && ( $values['provider'] == 'none' && PUM_Options::get( 'newsletter_default_provider' ) !== $this->id ) ) {
			return $errors;
		}

		return $this->form_validation( $errors, $values );
	}

	/**
	 * Subscribes the user to the list
	 *
	 * @param $values
	 * @param array $json_response
	 * @param \WP_Error $errors
	 *
	 * @return void
	 */
	public function _form_submission( $values, &$json_response, \WP_Error &$errors ) {
		if ( $this->id != $values['provider'] && ( $values['provider'] == 'none' && PUM_Options::get( 'newsletter_default_provider' ) !== $this->id ) ) {
			return;
		}

		$this->form_submission( $values, $json_response, $errors );
	}


	/**
	 * Adds a tab for each provider. These will be hidden except for the chosen provider.
	 *
	 * @internal Only used by the constructor.
	 *
	 * @param $sections
	 *
	 * @return array
	 */
	public function shortcode_sections( $sections ) {
		return array_merge( $sections, array(
			'provider_' . $this->id => $this->name,
		) );
	}

	/**
	 * Registers the fields for this providers shortcode tab.
	 *
	 * @internal Only used by the constructor.
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function shortcode_fields( $fields ) {
		return array_merge( $fields, array(
			'provider_' . $this->id => $this->fields(),
		) );
	}

	/**
	 * Registers the defaults for this provider.
	 *
	 * @internal Only used by the constructor.
	 *
	 * @param $defaults
	 *
	 * @return array
	 */
	public function shortcode_defaults( $defaults ) {
		return array_merge( $defaults, $this->defaults() );
	}

	/**
	 * Gets default messages.
	 *
	 * @internal
	 *
	 * @param null $context
	 *
	 * @return array|mixed|string
	 */
	public function default_messages( $context = null ) {
		$messages = array(
			'success'               => pum_get_option('default_success_message', __( 'You have been subscribed!', 'popup-maker' ) ),
			'double_opt_in_success' => pum_get_option('default_double_opt_in_success',__( 'Please check your email and confirm your subscription.', 'popup-maker' ) ),
			'error'                 => pum_get_option('default_error_message',__( 'Error occurred when subscribing. Please try again.', 'popup-maker' ) ),
			'already_subscribed'    => pum_get_option('default_already_subscribed_message',__( 'You are already a subscriber.', 'popup-maker' ) ),
			'empty_email'           => pum_get_option('default_empty_email_message',__( 'Please enter a valid email.', 'popup-maker' ) ),
			'invalid_email'         => pum_get_option('default_invalid_email_message',__( 'Email provided is not a valid email address.', 'popup-maker' ) ),
		);

		if ( $context ) {
			return isset( $messages[ $context ] ) ? $messages[ $context ] : '';
		}

		return $messages;
	}

	#endregion

	#region Globally Used Functions.

	/**
	 * Get default or customized messages.
	 *
	 * @param string $context
	 * @param array $values
	 *
	 * @return string
	 */
	public function get_message( $context, $values = array() ) {
		$message = PUM_Options::get( "{$this->opt_prefix}{$context}_message", '' );

		if ( empty( $message ) ) {
			$message = $this->default_messages( $context );
		}

		if ( strpos( $message, '{' ) ) {
			$message = $this->dynamic_message( $message, $values );
		}

		return apply_filters( "pum_newsletter_{$context}_message", $message, $this );
	}


	/**
	 * @param string $message
	 * @param array $values
	 *
	 * @return mixed|string
	 */
	protected function dynamic_message( $message = '', $values = array() ) {

		preg_match_all( '/{(.*?)}/', $message, $found );

		if ( count( $found[1] ) ) {

			foreach ( $found[1] as $key => $match ) {

				$message = $this->message_text_replace( $message, $match, $values );

			}
		}

		return $message;

	}

	/**
	 * @param string $message
	 * @param string $match
	 * @param array $values
	 *
	 * @return mixed|string
	 */
	protected function message_text_replace( $message = '', $match = '', $values = array() ) {

		if ( empty( $match ) ) {
			return $message;
		}

		if ( strpos( $match, '||' ) !== false ) {
			$matches = explode( '||', $match );
		} else {
			$matches = array( $match );
		}

		$replace = '';

		foreach ( $matches as $string ) {


			if ( ! array_key_exists( $string, $values ) ) {

				// If its not a valid code it is likely a fallback.
				$replace = $string;

			} else {

				// This is a form field value, replace accordingly.

				switch ( $string ) {

					default:
						$replace = $values[ $string ];
						break;

				}

			}

			// If we found a replacement stop the loop.
			if ( ! empty( $replace ) ) {
				break;
			}

		}

		return str_replace( '{' . $match . '}', $replace, $message );
	}


	#endregion

	#region Magic Methods

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	function __get( $name ) {
		if ( method_exists( $this, 'get_' . $name ) ) {
			$method = 'get_' . $name;

			return $this->$method();
		}

		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}
	}

	#endregion
}
