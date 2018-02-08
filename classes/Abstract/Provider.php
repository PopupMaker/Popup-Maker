<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Abstract_Provider
 */
abstract class PUM_Abstract_Provider implements PUM_Interface_Provider {

	/**
	 * Option name prefix.
	 *
	 * @var string
	 */
	public $opt_prefix = '';

	/**
	 * Email provider name such as 'mailchimp'
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Email provider name for labeling such as 'MailChimp's
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Version  of the email provider implementation. Used for compatibility.
	 *
	 * @var int
	 */
	public $version = 1;

	/**
	 * Latest current version.
	 *
	 * @var int
	 */
	public $current_version = 2;

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
		add_filter( 'pum_sub_form_shortcode_tabs', array( $this, 'shortcode_tabs' ) );
		add_filter( 'pum_sub_form_shortcode_subtabs', array( $this, 'shortcode_subtabs' ) );
		add_filter( 'pum_sub_form_shortcode_fields', array( $this, 'shortcode_fields' ) );
		add_filter( 'pum_sub_form_shortcode_defaults', array( $this, 'shortcode_defaults' ) );

		/** Forms Processing & AJAX */
		add_filter( 'pum_sub_form_sanitization', array( $this, 'process_form_sanitization' ), 10 );
		add_filter( 'pum_sub_form_validation', array( $this, 'process_form_validation' ), 10, 2 );
		add_action( 'pum_sub_form_submission', array( $this, 'process_form_submission' ), 10, 3 );

		/** Form Rendering */
		add_action( 'pum_sub_form_fields', array( $this, 'render_fields' ) );
	}

	/**
	 * Determines whether to load this providers fields in the shortcode editor among other things.
	 *
	 * @return bool
	 */
	abstract public function enabled();

	/**
	 * Contains each providers unique fields.
	 *
	 * @deprecated 1.7.0 Use instead: $this->shortcode_tabs, $this->shortcode_subtabs & $this->shortcode_fields instead.
	 * @uses       self::instance()->shortcode_tabs()
	 *
	 * @return array
	 */
	public function fields() {
		return PUM_Admin_Helpers::flatten_fields_array( $this->shortcode_fields() );
	}

	/**
	 * Contains each providers unique global settings.
	 *
	 * @return array
	 */
	abstract public function register_settings();

	/**
	 * Contains each providers unique global settings tab sections..
	 *
	 * @param array $sections Array of settings page tab sections.
	 *
	 * @return array
	 */
	public function register_settings_tab_section( $sections = array() ) {
		$sections['subscriptions'][ $this->id ] = $this->name;

		return $sections;
	}

	/**
	 * Creates the inputs for each of the needed fields for the email provider
	 *
	 * TODO Determine how this should really work for visible custom fields.
	 *
	 * @param array $shortcode_atts Array of shortcodee attrs.
	 */
	public function render_fields( $shortcode_atts ) {
		$fields = PUM_Admin_Helpers::flatten_fields_array( $this->shortcode_fields() );

		foreach ( $fields as $key => $field ) {
			if ( ! $field['private'] && isset( $shortcode_atts[ $key ] ) ) {
				echo esc_html( '<input type="hidden" name="' . $key . '" value="' . $shortcode_atts[ $key ] . '" />' );
			}
		}
	}

	/**
	 * Process form value sanitization.
	 *
	 * @param array $values Values.
	 *
	 * @return array $values
	 */
	public function form_sanitization( $values = array() ) {
		return $values;
	}

	/**
	 * Process form values for errors.
	 *
	 * @param WP_Error $errors Errors object.
	 * @param array    $values Values.
	 *
	 * @return WP_Error
	 */
	public function form_validation( WP_Error $errors, $values = array() ) {
		return $errors;
	}

	/**
	 * Subscribes the user to the list
	 *
	 * @param array    $values        Values.
	 * @param array    $json_response JSON Response.
	 * @param WP_Error $errors        Errors object.
	 */
	public function form_submission( $values, &$json_response, WP_Error &$errors ) {
	}

	/**
	 * Internally processes sanitization only for the current provider.
	 *
	 * @param array $values Values.
	 *
	 * @return array $values
	 */
	public function process_form_sanitization( $values = array() ) {
		if ( $this->id !== $values['provider'] && ( 'none' === $values['provider'] && PUM_Options::get( 'newsletter_default_provider' ) !== $this->id ) ) {
			return $values;
		}

		return $this->form_sanitization( $values );
	}

	/**
	 * Internally processes validation only for the current provider.
	 *
	 * @param WP_Error $errors Errors object.
	 * @param array    $values Values.
	 *
	 * @return WP_Error
	 */
	public function process_form_validation( WP_Error $errors, $values = array() ) {
		if ( $this->id !== $values['provider'] && ( 'none' === $values['provider'] && PUM_Options::get( 'newsletter_default_provider' ) !== $this->id ) ) {
			return $errors;
		}

		return $this->form_validation( $errors, $values );
	}

	/**
	 * Internally processes submission only for the current provider.
	 *
	 * @param array    $values        Values.
	 * @param array    $json_response AJAX JSON Response array.
	 * @param WP_Error $errors        Errors object.
	 */
	public function process_form_submission( $values, &$json_response, WP_Error &$errors ) {
		if ( $this->id !== $values['provider'] && ( 'none' === $values['provider'] && PUM_Options::get( 'newsletter_default_provider' ) !== $this->id ) ) {
			return;
		}

		$this->form_submission( $values, $json_response, $errors );
	}

	/**
	 *
	 *
	 * @return string $tab_id;
	 */
	public function shortcode_tab_id() {
		return 'provider_' . $this->id;
	}

	/**
	 * Adds a tab for each provider. These will be hidden except for the chosen provider.
	 *
	 * @param array $tabs Array of tab.
	 *
	 * @return array
	 */
	public function shortcode_tabs( $tabs = array() ) {
		$resorted_tabs = array();

		foreach ( $tabs as $tab_id => $label ) {
			$resorted_tabs[ $tab_id ] = $label;

			if ( 'general' == $tab_id ) {
				$resorted_tabs[ $this->shortcode_tab_id() ] = $this->name;
			}
		}

		return $resorted_tabs;
	}

	/**
	 * Adds a subtabs for each provider. These will be hidden except for the chosen provider.
	 *
	 * @param array $subtabs Array of tab=>subtabs.
	 *
	 * @return array
	 */
	public function shortcode_subtabs( $subtabs = array() ) {
		return array_merge( $subtabs, array(
			$this->shortcode_tab_id() => array(
				'main' => $this->name,
			),
		) );
	}

	/**
	 * Registers the fields for this providers shortcode tab.
	 *
	 * @param array $fields Array of fields.
	 *
	 * @return array
	 */
	public function shortcode_fields( $fields = array() ) {

		$new_fields = $this->version < 2 ? PUM_Admin_Helpers::flatten_fields_array( $this->fields() ) : array();

		foreach ( $new_fields as $field_id => $field ) {
			if ( isset( $field['options'] ) ) {
				$new_fields[ $field_id ]['options'] = array_flip( $field['options'] );
			}
		}

		return array_merge( $fields, array(
			$this->shortcode_tab_id() => array(
				'main' => $new_fields,
			),
		) );
	}

	/**
	 * Registers the defaults for this provider.
	 *
	 * @param array $defaults Array of default values.
	 *
	 * @return array
	 */
	public function shortcode_defaults( $defaults ) {
		// Flatten fields array.
		$fields = PUM_Admin_Helpers::flatten_fields_array( $this->shortcode_fields() );

		return array_merge( $defaults, PUM_Admin_Helpers::get_field_defaults( $fields ) );
	}

	/**
	 * Gets default messages.
	 *
	 * @param string|null $context Context of the message to be returned.
	 *
	 * @return array|mixed|string
	 */
	public function default_messages( $context = null ) {
		return pum_get_newsletter_default_messages( $context );
	}

	/**
	 * Get default or customized messages.
	 *
	 * @param string $context Context.
	 * @param array  $values  Array of values.
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
	 * Process a message with dynamic values.
	 *
	 * @param string $message Message.
	 * @param array  $values  Array of values.
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
	 * Replaces a single matched message.
	 *
	 * @param string $message Message.
	 * @param string $match   Matched phrase.
	 * @param array  $values  Values for replacement.
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

	/**
	 * Magic method replacement.
	 *
	 * @param string $name Function or field name.
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( method_exists( $this, 'get_' . $name ) ) {
			$method = 'get_' . $name;

			return $this->$method();
		}

		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}

		return false;
	}
}
