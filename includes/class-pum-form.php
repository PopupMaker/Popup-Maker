<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Form extends PUM_Fields {

	public $id;

	public $field_prefix = 'pum_form';

	public $field_name_format = '{$prefix}[{$field}]';

	/**
	 * Sets the $id of the Cookie and returns the parent __construct()
	 *
	 * @param array $id
	 * @param array $args
	 */
	public function __construct( $id, $args = array() ) {
		$this->id = $id;

		if ( empty( $args['id'] ) ) {
			$args['id'] = $id;
		}

		if ( isset( $args['field_prefix'] ) ) {
			$this->field_prefix = $args['field_prefix'];
		}

		if ( isset( $args['field_name_format'] ) ) {
			$this->field_name_format = $args['field_name_format'];
		}

		return parent::__construct( $args );
	}

	public function get_id() {
		return $this->id;
	}

}
