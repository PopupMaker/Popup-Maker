<?php
/**
 * Importer for easy-modal model
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EModal_Model {
	protected $_class_name     = 'EModal_Model';
	protected $_table_name     = '';
	protected $_pk             = 'id';
	protected $_data           = [];
	protected $_default_fields = [];
	protected $_state          = null;

	public function __construct( $id = null, $limit = 1 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_name;
		$class_name = strtolower( $this->_class_name );

		$this->_data = apply_filters( "{$class_name}_fields", $this->_default_fields );

		if ( $id && is_numeric( $id ) ) {
			$row = $wpdb->get_row( "SELECT * FROM $table_name WHERE $this->_pk = $id LIMIT 1", ARRAY_A );
			if ( $row[ $this->_pk ] ) {
				$this->process_load( $row );
			}
		} else {
			$this->set_fields( apply_filters( "{$class_name}_defaults", [] ) );
		}

		return $this;
	}

	public function load( $query = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_name;

		if ( ! $query ) {
			$query = "SELECT * FROM $table_name";
		}
		$rows = $wpdb->get_results( $query, ARRAY_A );
		if ( ! empty( $rows ) ) {
			$results = [];
			foreach ( $rows as $row ) {
				$model = new $this->_class_name();
				$model->process_load( $row );
				$results[] = $model;
			}

			return $results;
		}

		return [];
	}

	public function save() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_name;
		if ( $this->id ) {
			if ( ! $wpdb->update( $table_name, $this->serialized_values(), [ $this->_pk => $this->{$this->_pk} ] ) ) {
				$wpdb->insert( $table_name, $this->serialized_values() );
				$this->id = $wpdb->insert_id;
			}
		} else {
			$wpdb->insert( $table_name, $this->serialized_values() );
			$this->id = $wpdb->insert_id;
		}
	}

	public function delete() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_name;

		return $wpdb->delete( $table_name, [ $this->_pk => $this->{$this->_pk} ] );
	}

	public function as_array() {
		$values = $this->_data;
		foreach ( $values as $key => $value ) {
			$values[ $key ] = $this->$key;
		}

		return $values;
	}

	public function process_load( $data ) {
		foreach ( $data as $key => $val ) {
			if ( array_key_exists( $key, $this->_data ) ) {
				$this->$key = maybe_unserialize( $val );
			}
		}
	}

	public function serialized_values() {
		$values = $this->_data;

		foreach ( $values as $key => $value ) {
			if ( 'id' !== $key ) {
				$values[ $key ] = maybe_serialize( $this->$key );
			}
		}

		return $values;
	}

	public function __get( $key ) {
		if ( array_key_exists( $key, $this->_data ) ) {
			return $this->_data[ $key ];
		} elseif ( 'id' === $key ) {
			if ( array_key_exists( $this->_pk, $this->_data ) ) {
				return $this->_data[ $this->_pk ];
			}
		}
	}

	public function __set( $key, $value ) {
		if ( array_key_exists( $key, $this->_data ) ) {
			$this->_data[ $key ] = $value;

			return;
		}
	}

	public function __isset( $name ) {
		return isset( $this->_data[ $name ] );
	}

	public function fields() {
		return array_keys( $this->_data );
	}

	public function set_fields( array $data ) {
		foreach ( $data as $key => $val ) {
			if ( array_key_exists( $key, $this->_data ) ) {
				if ( is_array( $this->$key ) && is_array( $val ) ) {
					$this->$key = array_replace_recursive( $this->$key, $val );
				} else {
					$this->$key = $val;
				}
			}
		}
	}

	// Array Access Interface
	public function offsetExists( $key ) {
		return array_key_exists( $key, $this->as_array() );
	}

	public function offsetSet( $key, $value ) {
		$this->__set( $key, $value );
	}

	public function offsetGet( $key ) {
		return $this->$key;
	}

	public function offsetUnset( $key ) {
		$this->_data[ $key ] = null;
	}
}
