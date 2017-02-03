<?php
/**
 * Condition
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Condition
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
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

	/**
	 * @var string
	 */
	public $templ_value_format = '{$field}';

	public $group = 'general';

	/**
	 * Sets the $id of the Condition and returns the parent __construct()
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'id' => '',
			'group' => '',
			'name' => '',
			'labels' => array(),
			'advanced' => false,
		) );

		$this->id = $args['id'];

        if ( ! empty( $args['labels'] ) && is_array( $args['labels'] ) ) {
            $labels = $args['labels'];
        } else {
            $labels = array();
        }

        if ( ! empty( $args['name'] ) ) {
            $labels['name'] = $args['name'];
        }

        $this->set_labels( $labels );

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

    public function has_callback( $valid_callback = true ) {
    	if ( empty( $this->args['callback'] ) ) {
    		return false;
	    }

        return $valid_callback ? is_callable( $this->args['callback'] ) : true;
    }

    public function is_advanced() {
	    return $this->args['advanced'] != false;
    }

    public function get_callback() {

        if ( $this->has_callback() && is_callable( $this->args['callback'] ) ) {
            $callback = $this->args['callback'];
        } elseif ( method_exists( 'PUM_Condition_Callbacks', $this->id ) ) {
            $callback = array( 'PUM_Condition_Callbacks', $this->id );
        } else {
            $callback = "pum_condition_{$this->id}";
        }

        $callback =  apply_filters( 'pum_condition_get_callback', $callback, $this );

        return is_callable( $callback ) ? $callback : '__return_false';
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

	/**
	 * @return array
	 */
	public function get_all_fields() {
		$all_fields = array();
		foreach ( $this->fields as $section => $fields ) {
			$all_fields = array_merge( $all_fields, $this->get_fields( $section ) );
		}

		return $all_fields;
	}


	/**
	 * @param array $values
	 */
	function render_fields( $values = array() ) {
		foreach ( $this->get_all_fields() as $id => $args ) {
			$value = isset( $values[ $args['id'] ] ) ? $values[ $args['id'] ] : null;

			$this->render_field( $args, $value );
		}
	}

	/**
	 */
	public function render_templ_fields() {
		foreach ( $this->get_all_fields() as $id => $args ) {
			$this->render_templ_field( $args );
		}
	}

	public function field_before( $args = array() ) {
		$classes = is_array( $args ) ? $this->field_classes( $args ) : ( is_string( $args ) ? $args : '' );
		?><div class="facet-col  <?php esc_attr_e( $classes ); ?>"><?php
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

        $sanitized_values['not_operand'] = isset( $values['not_operand'] ) && $values['not_operand'] ? 1 : 0;

        if ( isset( $values['target'] ) && $values['target'] == $this->get_id() ) {
            $sanitized_values['target'] = $this->get_id();
        }

		foreach ( $this->get_all_fields() as $id => $field ) {
			$value = isset( $values[ $field['id'] ] ) ? $values[ $field['id'] ] : null;
			$value = $this->sanitize_field( $field, $value );
			if ( ! is_null( $value ) ) {
				$sanitized_values[ $field['id'] ] = $value;
			}
		}

		return $sanitized_values;
	}

}
