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

		$current_db_version = $this->get_installed_version();

		if ( ! $current_db_version || $current_db_version < $this->version ) {
			// Install the table.
			@$this->create_table();

			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name()}'" ) == $this->table_name() ) {
				$this->update_db_version();
			}
		}

		$wpdb->{$this->table_name} = $this->table_name();
	}

	/**
	 * Gets db version from new or old source.
	 *
	 * @return float
	 */
	public function get_installed_version() {
		// Get list of all current db table versions.
		$db_versions = get_option( 'pum_db_versions', [] );

		// #1 If it exists in new pum_db_vers[] option, move on.
		if ( isset( $db_versions[ $this->table_name ] ) ) {
			return (float) $db_versions[ $this->table_name ];
		}

		// #2 Else look for old key, if exists, migrate and delete.
		$db_version_old_key = get_option( $this->table_name . '_db_version' );

		if ( $db_version_old_key ) {
			if ( $db_version_old_key > 0 ) {
				$db_versions[ $this->table_name ] = (float) $db_version_old_key;
				update_option( 'pum_db_versions', $db_versions );
			}

			delete_option( $this->table_name . '_db_version' );
		}

		return (float) $db_version_old_key;
	}

	public function update_db_version() {
		// Get list of all current db table versions.
		$db_versions = get_option( 'pum_db_versions', [] );

		$db_versions[ $this->table_name ] = (float) $this->version;
		update_option( 'pum_db_versions', $db_versions );
	}

	/**
	 * Create the table
	 */
	abstract public function create_table();

	/**
	 * @return static
	 * @throws \Exception
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

		return $this->prepare_result( $wpdb->get_row( "SELECT * FROM {$this->table_name()} WHERE $this->primary_key = $row_id LIMIT 1;" ) );
	}

	/**
	 * @param object|array $result
	 *
	 * @return object|array
	 */
	public function prepare_result( $result ) {
		if ( ! $result || ( ! is_array( $result ) && ! is_object( $result ) ) ) {
			return $result;
		}

		if ( is_object( $result ) ) {
			$vars = get_object_vars( $result );
			foreach ( $vars as $key => $value ) {
				if ( is_string( $value ) ) {
					$result->$key = maybe_unserialize( $value );
				}
			}
		} elseif ( is_array( $result ) ) {
			foreach ( $result as $key => $value ) {
				if ( is_string( $value ) ) {
					$result[ $key ] = maybe_unserialize( $value );
				}
			}
		}

		return $result;
	}

	/**
	 * @param array|object[] $results
	 *
	 * @return mixed
	 */
	public function prepare_results( $results ) {

		foreach ( $results as $key => $result ) {
			$results[ $key ] = $this->prepare_result( $result );
		}

		return $results;
	}

	/**
	 * @return string
	 */
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

		return $this->prepare_result( $wpdb->get_row( "SELECT * FROM {$this->table_name()} WHERE $column = '$row_id' LIMIT 1;" ) );
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
	 * @param        $data
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
	 * @param        $row_id
	 * @param array  $data
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

		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = maybe_serialize( $value );
			}
		}

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
	 * @param       $query
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

	/**
	 * @param array  $args
	 * @param string $return_type
	 *
	 * @return array|mixed|object[]
	 */
	public function query( $args = array(), $return_type = OBJECT ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'fields'  => '*',
			'page'    => null,
			'limit'   => null,
			'offset'  => null,
			's'       => null,
			'orderby' => null,
			'order'   => null,
		) );

		$columns = $this->get_columns();

		$fields = $args['fields'];

		if ( $fields == '*' ) {
			$fields = array_keys( $columns );
		} else {
			$fields = explode( ',', $args['fields'] );
			$fields = array_map( 'trim', $fields );
			$fields = array_map( 'sanitize_text_field', $fields );
		}

		$select_fields = implode( '`, `', $fields );

		// Begin building query.
		$query = "SELECT `$select_fields` FROM {$this->table_name()}";

		// Set up $values array for wpdb::prepare
		$values = array();

		// Define an empty WHERE clause to start from.
		$where = "WHERE 1=1";

		// Build search query.
		if ( $args['s'] && ! empty( $args['s'] ) ) {

			$search = wp_unslash( trim( $args['s'] ) );

			$search_where = array();

			foreach ( $columns as $key => $type ) {
				if ( in_array( $key, $fields ) ) {
					if ( $type == '%s' || ( $type == '%d' && is_numeric( $search ) ) ) {
						$values[]       = '%' . $wpdb->esc_like( $search ) . '%';
						$search_where[] = "`$key` LIKE '%s'";
					}
				}
			}

			if ( ! empty( $search_where ) ) {
				$where .= ' AND (' . join( ' OR ', $search_where ) . ')';
			}
		}

		$query .= " $where";

		if ( ! empty( $args['orderby'] ) ) {
			$query    .= " ORDER BY %s";
			$values[] = wp_unslash( trim( $args['orderby'] ) );

			switch ( $args['order'] ) {
				case 'asc':
				case 'ASC':
					$query .= " ASC";
					break;
				case 'desc':
				case 'DESC':
				default:
					$query .= " DESC";
					break;
			}
		}

		if ( ! empty( $args['limit'] ) ) {
			$query    .= " LIMIT %d";
			$values[] = absint( $args['limit'] );
		}

		// Pagination.
		if ( $args['page'] >= 1 ) {
			$args['offset'] = ( $args['page'] * $args['limit'] ) - $args['limit'];
		}

		if ( ! empty( $args['offset'] ) ) {
			$query    .= " OFFSET %d";
			$values[] = absint( $args['offset'] );
		}

		if ( strpos( $query, '%s' ) || strpos( $query, '%d' ) ) {
			$query = $wpdb->prepare( $query, $values );
		}

		return $this->prepare_results( $wpdb->get_results( $query, $return_type ) );
	}

	/**
	 * Queries for total rows
	 *
	 * @param $args
	 *
	 * @return int
	 */
	public function total_rows( $args ) {
		// TODO REVIEW this can probably be done more efficiently. Look at how we do it for DB models.
		$args['limit']  = null;
		$args['offset'] = null;
		$args['page']   = null;

		$results = $this->query( $args );

		return $results ? count( $results ) : 0;
	}
}