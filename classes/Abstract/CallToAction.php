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
		$defaults = PUM_Utils_Fields::get_field_default_values( $this->fields() );

		return wp_parse_args( $atts, $defaults );
	}

	/**
	 * Function that returns array of fields by group.
	 *
	 * @return array
	 */
	protected function fields() {
		return [];
	}

	/**
	 * Get the built in base fields.
	 *
	 * @return array
	 */
	public function get_base_fields() {
		$base_fields = apply_filters(
			'pum_cta_base_fields',
			[
				'general' => [
					'text' => [
						'type'         => 'text',
						'label'        => __( 'Enter text for your call to action.', 'popup-maker' ),
						'std'          => __( 'Learn more', 'popup-maker' ),
						'dependencies' => [],
						'priority'     => 0.1,
					],
				],
			]
		);
	}

	/**
	 * Get fields including the built in default fields.
	 *
	 * @param bool $with_base Whether to include the core base fields that every CTA uses.
	 *
	 * @return array
	 */
	public function get_fields( $with_base = false ) {
		return $with_base ? array_merge( $this->get_base_fields(), $this->fields() ) : $this->fields();
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
