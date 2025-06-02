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
	 * Label for reference.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Function that returns array of fields by group.
	 *
	 * @return array
	 */
	public function fields();

	/**
	 * Handle the CTA action.
	 *
	 * @param \PopupMaker\Base\CallToAction $call_to_action Call to action object.
	 * @param array                         $extra_args     Optional. Additional data passed to the handler (will include popup_id).
	 *
	 * @return mixed The result of the action
	 */
	public function action_handler( $call_to_action, $extra_args = [] );

	/**
	 * Returns an array that represents the cta.
	 *
	 * Used to pass configs to JavaScript.
	 *
	 * @return array
	 */
	public function as_array();
}
