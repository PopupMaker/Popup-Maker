<?php
/**
 * Call To Action interface.
 *
 * @since       1.14
 * @package     PUM
 * @copyright   Copyright (c) 2020, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Interface_CallToAction
 */
interface PUM_Interface_CallToAction {

	/**
	 * Identifier string
	 *
	 * @return string
	 */
	public function key();

	/**
	 * Label for reference.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Function that renders the cta.
	 *
	 * @return string
	 */
	public function render( $atts = [] );

	/**
	 * Function that returns array of fields by group.
	 *
	 * @return array
	 */
	public function fields();

	/**
	 * Returns an array that represents the cta.
	 *
	 * Used to pass configs to JavaScript.
	 *
	 * @return array
	 */
	public function as_array();

}
