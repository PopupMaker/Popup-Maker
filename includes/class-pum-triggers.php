<?php
/**
 * Triggers
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Triggers
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Triggers {

	public static $instance;

	public $triggers = array();

	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Popup_Maker ) ) {
			self::$instance = new PUM_Triggers;
		}

		return self::$instance;
	}

	public function add_triggers( $triggers = array() ) {
		foreach ( $triggers as $key => $trigger ) {

			if ( ! $trigger instanceof PUM_Trigger && is_array( $trigger ) ) {
				if ( empty( $trigger['id'] ) && ! is_numeric( $key ) ) {
					$trigger['id'] = $key;
				}

				$trigger = new PUM_Trigger( $trigger );
			}

			$this->add_trigger( $trigger );

		}
	}

	public function add_trigger( $trigger = null ) {
		if ( ! $trigger instanceof PUM_Trigger ) {
			return;
		}

		if ( ! isset ( $this->triggers[ $trigger->id ] ) ) {
			$this->triggers[ $trigger->id ] = $trigger;
		}

		return;
	}

	public function get_triggers() {
		return $this->triggers;
	}

}
