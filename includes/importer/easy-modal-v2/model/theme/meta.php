<?php
/**
 * Importer for easy-modal model theme meta
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EModal_Model_Theme_Meta extends EModal_Model {
	protected $_class_name     = 'EModal_Model_Theme_Meta';
	protected $_table_name     = 'em_theme_metas';
	protected $_pk             = 'theme_id';
	protected $_default_fields = [
		'id'        => null,
		'theme_id'  => null,
		'overlay'   => [],
		'container' => [],
		'close'     => [],
		'title'     => [],
		'content'   => [],
	];

	public function __construct( $id = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_name;
		$class_name = strtolower( $this->_class_name );

		$this->_default_fields['theme_id'] = $id;
		$this->_data                       = apply_filters( "{$class_name}_fields", $this->_default_fields );
		if ( $id && is_numeric( $id ) ) {
			$row = $wpdb->get_row( "SELECT * FROM $table_name WHERE theme_id = $id ORDER BY id DESC LIMIT 1", ARRAY_A );
			if ( $row[ $this->_pk ] ) {
				$this->process_load( $row );
			}
		} else {
			$this->set_fields( apply_filters( "{$class_name}_defaults", [] ) );
		}

		return $this;
	}

	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_name;

		$rows = $wpdb->get_col( "SELECT id FROM $table_name WHERE theme_id = $this->theme_id ORDER BY id DESC" );
		if ( count( $rows ) ) {
			$this->id = $rows[0];
			$wpdb->update( $table_name, $this->serialized_values(), [ 'id' => $this->id ] );
		} else {
			$wpdb->insert( $table_name, $this->serialized_values() );
			$this->id = $wpdb->insert_id;
		}
	}

}
