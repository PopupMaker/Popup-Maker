<?php
/**
 * Call To Action interface.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface CallToAction
 *
 * @since X.X.X
 */
interface CallToAction {

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
