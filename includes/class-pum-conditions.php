<?php
/**
 * Conditions
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Conditions
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Conditions {

	public static $instance;

	public $conditions = array();

	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Conditions ) ) {
			self::$instance = new PUM_Conditions;
		}

		return self::$instance;
	}

	public function add_conditions( $conditions = array() ) {
		foreach ( $conditions as $key => $condition ) {

			if ( ! $condition instanceof PUM_Condition && is_array( $condition ) ) {
				if ( empty( $condition['id'] ) && ! is_numeric( $key ) ) {
					$condition['id'] = $key;
				}

				$condition = new PUM_Condition( $condition );
			}

			$this->add_condition( $condition );

		}
	}

	public function add_condition( $condition = null ) {
		if ( ! $condition instanceof PUM_Condition ) {
			return;
		}

		if ( ! isset ( $this->conditions[ $condition->id ] ) ) {
			$this->conditions[ $condition->id ] = $condition;
		}

		return;
	}

	public function get_conditions() {
		return $this->conditions;
	}

	public function get_condition( $condition = null ) {
		return isset( $this->conditions[ $condition ] ) ? $this->conditions[ $condition ] : null;
	}

	public function get_defaults( $condition = null ) {
		$defaults = array();

		if ( ! $condition ) {
			foreach ( $this->get_conditions() as $condition ) {
				foreach ( $condition->get_all_fields() as $section => $fields ) {
					foreach ( $fields as $field ) {
						if ( $section != 'general' ) {
							$defaults[ $condition->get_id() ][ $section ][ $field['id'] ] = $field['std'];
						} else {
							$defaults[ $condition->get_id() ][ $field['id'] ] = $field['std'];
						}
					}
				}
			}
		} else {
			$condition = $this->get_condition( $condition );
			if ( $condition ) {
				foreach ( $condition->get_all_fields() as $section => $fields ) {
					foreach ( $fields as $field ) {
						if ( $section != 'general' ) {
							$defaults[ $section ][ $field['id'] ] = $field['std'];
						} else {
							$defaults[ $field['id'] ] = $field['std'];
						}
					}
				}
			}
		}

		return $defaults;
	}

	public function get_labels( $condition = null ) {
		$labels = array();

		if ( ! $condition ) {
			foreach ( $this->get_conditions() as $condition ) {
				$labels[ $condition->get_id() ] = $condition->get_labels();
			}
		} else {
			$condition = $this->get_condition( $condition );
			if ( $condition ) {
				$labels = $condition->get_labels();
			}
		}

		return $labels;
	}

	public function validate_condition( $condition = null, $settings = array() ) {
		if ( ! $condition || empty( $settings ) ) {
			return $settings;
		}

		$condition = $this->get_condition( $condition );
		if ( $condition ) {
			$settings = $condition->sanitize_fields( $settings );
		}

		return $settings;
	}

}
