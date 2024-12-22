<?php
/**
 * Call To Action collector.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Services\Collector;

use PopupMaker\Base\Service;
use PopupMaker\Interfaces\CallToAction;

defined( 'ABSPATH' ) || exit;

/**
 * Class CallToActions
 *
 * This class maintains a global set of all registered call to action types.
 */
class CallToActions extends Service {

	/**
	 * Holds array of registered $ctas.
	 *
	 * @var CallToAction[]
	 */
	private $data;

	/**
	 * Registers call to actions.
	 *
	 * @return void
	 */
	public function register_all() {
		$ctas = [];

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
	public function add( CallToAction $call_to_action ) {
		$this->data[ $call_to_action->key() ] = $call_to_action;
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
		$calltoactions = $this->get_all();

		return $calltoactions[ $key ] ?? false;
	}

	/**
	 * Exports all call to actions to an array for use with generators such as JS or PHP.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_as_array() {
		$calltoactions = [];

		foreach ( $this->get_all() as $key => $value ) {
			$calltoactions[ $key ] = $value->as_array();
		}

		return $calltoactions;
	}

	/**
	 * Generate an array containing a select list of key=>label.
	 *
	 * @return array<string,string>
	 */
	public function get_select_list() {
		$calltoactions = [];

		foreach ( $this->get_all() as $key => $value ) {
			$calltoactions[ $key ] = $value->label();
		}

		return $calltoactions;
	}
}
