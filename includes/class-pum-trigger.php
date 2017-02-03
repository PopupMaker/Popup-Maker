<?php
/**
 * Trigger
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Trigger
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Trigger extends PUM_Fields {

	public $id;

	public $labels = array();

	public $field_prefix = 'trigger_settings';

	public $field_name_format = '{$prefix}{$section}[{$field}]';

	/**
	 * Sets the $id of the Trigger and returns the parent __cunstruct()
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$this->id = $args['id'];

        $labels = pum_get_trigger_labels();
		if ( ! empty( $args['labels'] ) ) {
			$this->set_labels( $args['labels'] );
        } elseif ( isset( $labels[ $args['id'] ] ) ) {
            $this->set_labels( $labels[ $args['id'] ] );
        } else {
            $this->set_labels();
        }

        if ( empty( $args['sections'] ) ) {
            $this->sections = pum_get_trigger_section_labels();
        }

		return parent::__construct( $args );
	}

	public function get_id() {
		return $this->id;
	}

	public function set_labels( $labels = array() ) {
		$this->labels = wp_parse_args( $labels, array(
			'name' => __( 'Trigger', 'popup-maker' ),
			'modal_title' => __( 'Trigger Settings', 'popup-maker' ),
			'settings_column' => '',
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

	public function field_before( $args = array() ) {
		$classes = is_array( $args ) ? $this->field_classes( $args ) : ( is_string( $args ) ? $args : '' );
		?><div class="field <?php esc_attr_e( $classes ); ?>"><?php
	}

	public function field_after() {
		?></div><?php
	}


	/**
	 * Heading Callback
	 *
	 * Renders the heading.
	 *
	 * @param array $args Arguments passed by the setting
	 *
	 * @return void
	 */
	public function heading_callback( $args ) { ?>
		<h2 class="pum-setting-heading"><?php esc_html_e( $args['desc'] ); ?></h2>
		<hr/><?php
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
				}
				else {
					$value = isset( $values[ $field['id'] ] ) ? $values[ $field['id'] ] : null;
				}

				$value = $this->sanitize_field( $field, $value );

				//if ( ! is_null( $value ) ) {
					if ( $section != 'general' ) {
						$sanitized_values[ $section ][ $field['id'] ] = $value;
					}
					else {
						$sanitized_values[ $field['id'] ] = $value;
					}
				//}
			}
		}

		return $sanitized_values;
	}

}
