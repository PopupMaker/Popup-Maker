<?php
/**
 * Condition
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Condition
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Condition extends PUM_Fields {

	public $id;

	public $labels = array();

	public $field_prefix = 'popup_conditions';

	public $field_name_format = '{$prefix}[][][{$field}]';

	public $group = 'general';

	/**
	 * Sets the $id of the Condition and returns the parent __construct()
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$this->id = $args['id'];

		if ( ! empty( $args['labels'] ) ) {
			$this->set_labels( $args['labels'] );
		}

		if ( ! empty( $args['group'] ) ) {
			$this->group = $args['group'];
		}

		return parent::__construct( $args );
	}

	public function get_id() {
		return $this->id;
	}

	public function set_labels( $labels = array() ) {
		$this->labels = wp_parse_args( $labels, array(
			'name' => __( 'Condition', 'popup-maker' ),
		) );
	}

	public function get_label( $key ) {
		return isset( $this->labels[ $key ] ) ? $this->labels[ $key ] : null;
	}

	public function get_labels() {
		return $this->labels;
	}

	public function get_field_name( $field ) {
		return str_replace(
			array(
				'{$prefix}',
				'{$section}',
				'{$field}'
			),
			array(
				$this->field_prefix,
				$field['section'] != 'general' ? "[{$field['section']}]" : '',
				$field['id']
			),
			$this->field_name_format
		);
	}

	public function field_before( $class = '' ) {
		?><div class="facet-col <?php esc_attr_e( $class ); ?>"><?php
	}

	public function field_after() {
		?></div><?php
	}

	/**
	 * Sanitize fields
	 *
	 * @param array $values
	 *
	 * @return string $input Sanitized value
	 * @internal param array $input The value inputted in the field
	 *
	 */
	function sanitize_fields( $values = array() ) {

		$sanitized_values = array();

		foreach ( $this->get_all_fields() as $section => $fields ) {
			foreach ( $fields as $field ) {

				if ( $section != 'general' ) {
					$value = isset( $values[ $section ][ $field['id'] ] ) ? $values[ $section ][ $field['id'] ] : null;
				} else {
					$value = isset( $values[ $field['id'] ] ) ? $values[ $field['id'] ] : null;
				}

				$value = $this->sanitize_field( $field, $value );

				if ( ! is_null( $value ) ) {
					if ( $section != 'general' ) {
						$sanitized_values[ $section ][ $field['id'] ] = $value;
					} else {
						$sanitized_values[ $field['id'] ] = $value;
					}
				}
			}
		}

		return $sanitized_values;
	}

}
