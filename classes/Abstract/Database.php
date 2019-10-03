<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class PUM_Abstract_Database {

	/**
	 * @var static
	 */
	public static $instance;

	/**
	 * The name of our database table
	 */
	public $table_name = '';

	/**
	 * The version of our database table
	 */
	public $version = 1;

	/**
	 * The name of the primary column
	 */
	public $primary_key = 'ID';

	/**
	 * Get things started
	 */
	public function __construct() {
		global $wpdb;

		$current_db_version = get_option( $this->table_name . '_db_version' );

		if ( ! $current_db_version || $current_db_version < $this->version ) {
			// Install the table.
			@$this->create_table();

			if ( $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" ) == $this->table_name ) {
				update_option( $this->table_name . '_db_version', $this->version );
			}
		}
	}

	/**
	 * Create the table
	 */
	abstract public function create_table();

	/**
	 * @return static
	 */
	public static function instance() {
		$class = get_called_class();

		if ( ! isset( self::$instance[ $class ] ) ) {
			self::$instance[ $class ] = new $class;
		}

		return self::$instance[ $class ];
	}

	/**
	 * Retrieve a row by the primary key
	 *
	 * @param $row_id
	 *
	 * @return  object
	 */
	public function get( $row_id ) {
		global $wpdb;

		return $wpdb->get_row( "SELECT * FROM {$this->table_name()} WHERE $this->primary_key = $row_id LIMIT 1;" );
	}

	public function table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->table_name;
	}

	/**
	 * Retrieve a row by a specific column / value
	 *
	 * @param $column
	 * @param $row_id
	 *
	 * @return  object
	 */
	public function get_by( $column, $row_id ) {
		global $wpdb;

		return $wpdb->get_row( "SELECT * FROM {$this->table_name()} WHERE $column = '$row_id' LIMIT 1;" );
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 *
	 * @param $column
	 * @param $row_id
	 *
	 * @return  string
	 */
	public function get_column( $column, $row_id ) {
		global $wpdb;

		return $wpdb->get_var( "SELECT $column FROM {$this->table_name()} WHERE $this->primary_key = $row_id LIMIT 1;" );
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 *
	 * @param $column
	 * @param $column_where
	 * @param $column_value
	 *
	 * @return  string
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;

		return $wpdb->get_var( "SELECT $column FROM {$this->table_name()} WHERE $column_where = '$column_value' LIMIT 1;" );
	}

	/**
	 * Insert a new row
	 *
	 * @param $data
	 * @param string $type
	 *
	 * @return  int
	 */
	public function insert( $data ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'pum_pre_insert_' . $this->table_name, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = maybe_serialize( $value );
			}
		}

		$wpdb->insert( $this->table_name(), $data, $column_formats );

		do_action( 'pum_post_insert_' . $this->table_name, $wpdb->insert_id, $data );

		return $wpdb->insert_id;
	}

	/**
	 * Default column values
	 *
	 * @return  array
	 */
	public function get_column_defaults() {
		return array();
	}

	/**
	 * Whitelist of columns
	 *
	 * @return  array
	 */
	public function get_columns() {
		return array();
	}

	/**
	 * Update a row
	 *
	 * @param $row_id
	 * @param array $data
	 * @param string $where
	 *
	 * @return  bool
	 */
	public function update( $row_id, $data = array(), $where = '' ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $wpdb->update( $this->table_name(), $data, array( $where => $row_id ), $column_formats ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @param int $row_id
	 *
	 * @return  bool
	 */
	public function delete( $row_id = 0 ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name()} WHERE $this->primary_key = %d", $row_id ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @param $column
	 * @param $row_id
	 *
	 * @return  bool
	 */
	public function delete_by( $column, $row_id ) {
		global $wpdb;
		if ( empty( $row_id ) ) {
			return false;
		}
		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name()} WHERE $column = '%s'", $row_id ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Prepare query.
	 *
	 * @param $query
	 * @param array $args
	 *
	 * @return string
	 */
	public function prepare_query( $query, $args = array() ) {

		if ( $args['orderby'] ) {
			$query .= " ORDER BY {$args['orderby']} {$args['order']}";
		}

		$query .= " LIMIT {$args['limit']}";

		if ( $args['offset'] ) {
			$query .= " OFFSET {$args['offset']}";
		}

		$query .= ';';

		return $query;

	}

}