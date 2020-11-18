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

}
