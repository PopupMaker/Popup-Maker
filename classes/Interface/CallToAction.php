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
	public function render();

}
