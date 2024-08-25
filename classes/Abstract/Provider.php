<?php
/**
 * Abstract class for Provider
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

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
		add_filter( 'pum_settings_fields', [ $this, 'register_settings' ] );
		add_filter( 'pum_settings_tab_sections', [ $this, 'register_settings_tab_section' ] );

		/**
		 * Don't add the shortcodes or default options or process anything if the provider is disabled.
		 */
		if ( ! $this->enabled() ) {
			return;
		}

		/** Shortcodes Fields */
		add_filter( 'pum_sub_form_shortcode_tabs', [ $this, 'shortcode_tabs' ] );
		add_filter( 'pum_sub_form_shortcode_subtabs', [ $this, 'shortcode_subtabs' ] );
		add_filter( 'pum_sub_form_shortcode_fields', [ $this, 'shortcode_fields' ] );
		add_filter( 'pum_sub_form_shortcode_defaults', [ $this, 'shortcode_defaults' ] );

		/** Forms Processing & AJAX */
		add_filter( 'pum_sub_form_sanitization', [ $this, 'process_form_sanitization' ], 10 );
		add_filter( 'pum_sub_form_validation', [ $this, 'process_form_validation' ], 10, 2 );
		add_action( 'pum_sub_form_submission', [ $this, 'process_form_submission' ], 10, 3 );

		/** Form Rendering */
		add_action( 'pum_sub_form_fields', [ $this, 'render_fields' ] );
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
	public function register_settings_tab_section( $sections = [] ) {
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
				echo wp_kses( '<input type="hidden" name="' . $key . '" value="' . $shortcode_atts[ $key ] . '" />', [
					'input' => [
						'type'  => 'hidden',
						'name'  => true,
						'value' => true,
					],
				] );
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
	public function form_sanitization( $values = [] ) {
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
	public function form_validation( WP_Error $errors, $values = [] ) {
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
	public function process_form_sanitization( $values = [] ) {
		if ( $this->id !== $values['provider'] && ( 'none' === $values['provider'] && PUM_Utils_Options::get( 'newsletter_default_provider' ) !== $this->id ) ) {
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
	public function process_form_validation( WP_Error $errors, $values = [] ) {
		if ( $this->id !== $values['provider'] && ( 'none' === $values['provider'] && PUM_Utils_Options::get( 'newsletter_default_provider' ) !== $this->id ) ) {
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
		if ( $this->id !== $values['provider'] && ( 'none' === $values['provider'] && PUM_Utils_Options::get( 'newsletter_default_provider' ) !== $this->id ) ) {
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
	public function shortcode_tabs( $tabs = [] ) {
		$resorted_tabs = [];

		foreach ( $tabs as $tab_id => $label ) {
			$resorted_tabs[ $tab_id ] = $label;

			if ( 'general' === $tab_id ) {
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
	public function shortcode_subtabs( $subtabs = [] ) {
		return array_merge(
			$subtabs,
			[
				$this->shortcode_tab_id() => [
					'main' => $this->name,
				],
			]
		);
	}

	/**
	 * Registers the fields for this providers shortcode tab.
	 *
	 * @param array $fields Array of fields.
	 *
	 * @return array
	 */
	public function shortcode_fields( $fields = [] ) {

		$new_fields = $this->version < 2 ? PUM_Admin_Helpers::flatten_fields_array( $this->fields() ) : [];

		foreach ( $new_fields as $field_id => $field ) {
			if ( isset( $field['options'] ) ) {
				$new_fields[ $field_id ]['options'] = array_flip( $field['options'] );
			}
		}

		return array_merge(
			$fields,
			[
				$this->shortcode_tab_id() => [
					'main' => $new_fields,
				],
			]
		);
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
	public function get_message( $context, $values = [] ) {
		$message = PUM_Utils_Options::get( "{$this->opt_prefix}{$context}_message", '' );

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
	protected function dynamic_message( $message = '', $values = [] ) {

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
	 * @param string $search   Matched phrase.
	 * @param array  $values  Values for replacement.
	 *
	 * @return mixed|string
	 */
	protected function message_text_replace( $message = '', $search = '', $values = [] ) {

		if ( empty( $search ) ) {
			return $message;
		}

		if ( strpos( $search, '||' ) !== false ) {
			$searches = explode( '||', $search );
		} else {
			$searches = [ $search ];
		}

		$replace = '';

		foreach ( $searches as $string ) {
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

		return str_replace( '{' . $search . '}', $replace, $message );
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
