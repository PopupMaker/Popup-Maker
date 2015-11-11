<?php
/**
 * Trigger
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Trigger
 * @copyright   Copyright (c) 2015, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
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

		if ( ! empty( $args['labels'] ) ) {
			$this->set_labels( $args['labels'] );
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

	public function field_before( $class = '' ) {
		?><div class="field <?php esc_attr_e( $class ); ?>"><?php
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

}
