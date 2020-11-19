<?php
/**
 * Call To Action abstract class.
 *
 * @since       1.14
 * @package     PUM
 * @copyright   Copyright (c) 2020, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Abstract_CallToAction
 */
abstract class PUM_Abstract_CallToAction implements PUM_Interface_CallToAction {

	/**
	 * Unique identifier token.
	 *
	 * @var string
	 */
	protected $key = '';

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
	public $current_version = 1;

	/**
	 * The constructor method which sets up all filters and actions to prepare fields and messages
	 */
	public function __construct() {

	}

	/**
	 * Gets the key identifier string.
	 *
	 * @return string
	 */
	public function key() {
		return $this->key;
	}

	/**
	 * Renders the cta.
	 *
	 * @param array $atts Array of attributes to control what is rendered.
	 *
	 * @return string
	 */
	public function render( $atts = [] ) {
		return '';
	}

	/**
	 * Parses user submitted options with missing defaults.
	 *
	 * @param array $atts User chosen options to be parsed.
	 * @return array
	 */
	public function parse_atts( $atts = [] ) {
		$defaults = PUM_Utils_Fields::get_form_default_values( $this->get_fields() );

		return wp_parse_args( $atts, $defaults );
	}

	/**
	 * Function that returns array of fields by group.
	 *
	 * @return array
	 */
	public function fields() {
		return [];
	}

	/**
	 * Get fields including the built in default fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return $this->fields();
	}

	/**
	 * Exports this to an array for use with generators such as JS or PHP.
	 *
	 * @return array
	 */
	public function as_array() {
		return [
			'key'     => $this->key(),
			'label'   => $this->label(),
			'fields'  => $this->fields(),
			'version' => $this->version,
		];
	}
}
