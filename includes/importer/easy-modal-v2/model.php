<?php
/**
 * Model for easy-modal-v2 Importer
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

// Exit if accessed directly.
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

	/**
	 * Importer Model constructor.
	 *
	 * @param $id
	 * @param int $limit
	 */
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

	/**
	 * Load model table name.
	 *
	 * @param $query
	 */
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

	/**
	 * Saves model table name.
	 */
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

	/**
	 * Delete model table name.
	 */
	public function delete() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->_table_name;

		return $wpdb->delete( $table_name, [ $this->_pk => $this->{$this->_pk} ] );
	}

	/**
	 * Returns values as an array of keys.
	 */
	public function as_array() {
		$values = $this->_data;
		foreach ( $values as $key => $value ) {
			$values[ $key ] = $this->$key;
		}

		return $values;
	}

	/**
	 * Process load.
	 *
	 * @param array $data Values to load.
	 */
	public function process_load( $data ) {
		foreach ( $data as $key => $val ) {
			if ( array_key_exists( $key, $this->_data ) ) {
				$this->$key = maybe_unserialize( $val );
			}
		}
	}

	/**
	 * Checks values and serializes it if needed.
	 */
	public function serialized_values() {
		$values = $this->_data;

		foreach ( $values as $key => $value ) {
			if ( 'id' !== $key ) {
				$values[ $key ] = maybe_serialize( $this->$key );
			}
		}

		return $values;
	}

	/**
	 * Gets existing key data.
	 *
	 * @param array key Data being passed in.
	 */
	public function __get( $key ) {
		if ( array_key_exists( $key, $this->_data ) ) {
			return $this->_data[ $key ];
		} elseif ( 'id' === $key ) {
			if ( array_key_exists( $this->_pk, $this->_data ) ) {
				return $this->_data[ $this->_pk ];
			}
		}
	}

	/**
	 * Sets value.
	 *
	 * @param $key Item.
	 * @param $value Value.
	 */
	public function __set( $key, $value ) {
		if ( array_key_exists( $key, $this->_data ) ) {
			$this->_data[ $key ] = $value;

			return;
		}
	}

	/**
	 * Checks if property is set.
	 *
	 * @param $name Item to check.
	 */
	public function __isset( $name ) {
		return isset( $this->_data[ $name ] );
	}

	/**
	 * Retrieves data.
	 */
	public function fields() {
		return array_keys( $this->_data );
	}

	/**
	 * Sets fields data.
	 */
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

	/**
	 * Sets the given items.
	 *
	 * @param $key Item to set.
	 * @param $value Value to set.
	 */
	public function offsetSet( $key, $value ) {
		$this->__set( $key, $value );
	}

	/**
	 * Gets the value for the item.
	 *
	 * @param $key Item to get.
	 */
	public function offsetGet( $key ) {
		return $this->$key;
	}

	/**
	 * Unsets the given item.
	 *
	 * @param $key Item to unset.
	 */
	public function offsetUnset( $key ) {
		$this->_data[ $key ] = null;
	}
}
