<?php
/**
 * Call To Action type collector.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Services\Collector;

use PopupMaker\Base\Service;
use PopupMaker\Base\CallToAction;

defined( 'ABSPATH' ) || exit;

/**
 * Class CallToActionTypes
 *
 * This class maintains a global set of all registered call to action types.
 *
 * @since 1.21.0
 */
class CallToActionTypes extends Service {

	/**
	 * Holds array of registered $ctas.
	 *
	 * @var CallToAction[]
	 */
	private $data = [];

	/**
	 * Registers call to actions.
	 *
	 * @return void
	 */
	public function register_all() {
		$ctas = [
			'link' => new \PopupMaker\CallToAction\Link(),
		];

		/**
		 * Allow registering additional call to actions quickly.
		 *
		 * @param CallToAction[] $ctas Call to actions.
		 *
		 * @return CallToAction[]
		 */
		$ctas = apply_filters( 'popup_maker/registered_call_to_actions', $ctas );

		foreach ( $ctas as $call_to_action ) {
			$this->add( $call_to_action );
		}

		/**
		 * Allow manipulating registered call to actions.
		 *
		 * @param CallToActions $call_to_actions Call to actions.
		 *
		 * @return void
		 */
		do_action( 'popup_maker/register_call_to_actions', $this );
	}

	/**
	 * Add a call to action object to the collection.
	 *
	 * @param CallToAction $call_to_action Instance of a call to action.
	 */
	public function add( $call_to_action ) {
		$this->data[ $call_to_action->key ] = $call_to_action;
	}

	/**
	 * Get array of all registered call to actions.
	 *
	 * @return CallToAction[]
	 */
	public function get_all() {
		if ( ! did_action( 'popup_maker/register_call_to_actions' ) ) {
			$this->register_all();
		}

		return $this->data;
	}

	/**
	 * Get call to action by key.
	 *
	 * @param string $key Key of the call to action to retrieve.
	 *
	 * @return bool|CallToAction
	 */
	public function get( $key ) {
		$call_to_actions = $this->get_all();

		return $call_to_actions[ $key ] ?? false;
	}

	/**
	 * Exports all call to actions to an array for use with generators such as JS or PHP.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_as_array() {
		$call_to_actions = [];

		foreach ( $this->get_all() as $key => $value ) {
			$call_to_actions[ $key ] = $value->as_array();
		}

		return apply_filters( 'popup_maker/cta_types_as_array', $call_to_actions );
	}

	/**
	 * Generate an array containing a select list of key=>label.
	 *
	 * @return array<string,string>
	 */
	public function get_select_list() {
		$call_to_actions = [];

		foreach ( $this->get_all() as $key => $value ) {
			$call_to_actions[ $key ] = $value->label();
		}

		return apply_filters( 'popup_maker/cta_types_select_list', $call_to_actions );
	}
}
