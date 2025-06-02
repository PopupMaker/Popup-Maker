<?php
/**
 * Call To Action abstract class.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Base;

defined( 'ABSPATH' ) || exit;


/**
 * Class CallToAction
 *
 * @since X.X.X
 */
abstract class CallToAction implements \PopupMaker\Interfaces\CallToAction {

	/**
	 * Unique identifier token.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Label for reference.
	 *
	 * @return string
	 */
	abstract public function label();

	/**
	 * Function that returns array of fields by group.
	 *
	 * @return array
	 */
	abstract public function fields();

	/**
	 * Handle the CTA action.
	 *
	 * @param \PopupMaker\Base\CallToAction $call_to_action Call to action object.
	 * @param array                         $extra_args     Optional. Additional data passed to the handler (will include popup_id).
	 *
	 * @return mixed The result of the action
	 */
	abstract public function action_handler( $call_to_action, $extra_args = [] );

	/**
	 * Returns an array that represents the cta.
	 *
	 * Used to pass configs to JavaScript.
	 *
	 * @return array
	 */
	public function as_array() {
		return [
			'key'    => $this->key,
			'label'  => $this->label(),
			'fields' => $this->fields(),
		];
	}
}
