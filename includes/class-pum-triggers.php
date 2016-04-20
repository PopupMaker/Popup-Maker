<?php
/**
 * Triggers
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Triggers
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Triggers {

	public static $instance;

	public $triggers = array();

	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Triggers ) ) {
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

	public function get_trigger( $trigger = null ) {
		return isset( $this->triggers[ $trigger ] ) ? $this->triggers[ $trigger ] : null;
	}

	public function get_defaults( $trigger = null ) {
		$defaults = array();

		if ( ! $trigger ) {
			foreach ( $this->get_triggers() as $trigger ) {
				foreach ( $trigger->get_all_fields() as $section => $fields ) {
					foreach ( $fields as $field ) {
						if ( $section != 'general' ) {
							$defaults[ $trigger->get_id() ][ $section ][ $field['id'] ] = $field['std'];
						}
						else {
							$defaults[ $trigger->get_id() ][ $field['id'] ] = $field['std'];
						}
					}
				}
			}
		}
		else {
			$trigger = $this->get_trigger( $trigger );
			if ( $trigger ) {
				foreach ( $trigger->get_all_fields() as $section => $fields ) {
					foreach ( $fields as $field ) {
						if ( $section != 'general' ) {
							$defaults[ $section ][ $field['id'] ] = $field['std'];
						}
						else {
							$defaults[ $field['id'] ] = $field['std'];
						}
					}
				}
			}
		}

		return $defaults;
	}

	public function get_labels( $trigger = null ) {
		$labels = array();

		if ( ! $trigger ) {
			foreach ( $this->get_triggers() as $trigger ) {
				$labels[ $trigger->get_id() ] = $trigger->get_labels();
			}
		}
		else {
			$trigger = $this->get_trigger( $trigger );
			if ( $trigger ) {
				$labels = $trigger->get_labels();
			}
		}

		return $labels;
	}

	public function validate_trigger( $trigger = null, $settings = array() ) {
		if ( ! $trigger || empty( $settings ) ) {
			return $settings;
		}

		$trigger = $this->get_trigger( $trigger );
		if ( $trigger ) {
			$settings = $trigger->sanitize_fields( $settings );
		}

		return $settings;
	}

}
