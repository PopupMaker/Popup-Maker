<?php
// Exit if accessed directly

/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PUM_Subscribers Class
 */
class PUM_DB_Subscribers extends PUM_Abstract_Database {

	/**
	 * The name of our database table
	 */
	public $table_name = 'pum_subscribers';

	/**
	 * The version of our database table
	 */
	public $version = 3;

	/**
	 * The name of the primary column
	 */
	public $primary_key = 'ID';

	/**
	 * Get columns and formats
	 */
	public function get_columns() {
		return array(
			'ID'           => '%d',
			'uuid'         => '%s',
			'popup_id'     => '%d',
			'email_hash'   => '%s',
			'email'        => '%s',
			'name'         => '%s',
			'fname'        => '%s',
			'lname'        => '%s',
			'values'       => '%s',
			'user_id'      => '%d',
			'consent_args' => '%s',
			'consent'      => '%s',
			'created'      => '%s',
		);
	}

	/**
	 * Get default column values
	 */
	public function get_column_defaults() {
		return array(
			'uuid'         => '',
			'popup_id'     => 0,
			'email_hash'   => '',
			'email'        => '',
			'name'         => '',
			'fname'        => '',
			'lname'        => '',
			'values'       => '',
			'user_id'      => 0,
			'consent_args' => '',
			'consent'      => 'no',
			'created'      => current_time( 'mysql', 0 ),
		);

	}

	/**
	 * Create the table
	 */
	public function create_table() {

		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . $this->table_name() . " (
			`ID` BIGINT(20) NOT NULL AUTO_INCREMENT,
			`email_hash` VARCHAR(32) NOT NULL,
			`popup_id` BIGINT(20) NOT NULL,
			`user_id` BIGINT(20) NOT NULL,
			`email` VARCHAR(191) NOT NULL,
			`name` VARCHAR(255) NOT NULL,
			`fname` VARCHAR(255) NOT NULL,
			`lname` VARCHAR(255) NOT NULL,
			`values` LONGTEXT NOT NULL,
			`uuid` VARCHAR(255) NOT NULL,
			`consent` VARCHAR(255) NOT NULL,
			`consent_args` LONGTEXT NOT NULL,
			`created` DATETIME NOT NULL,
		  PRIMARY KEY (ID),
		  KEY email (email),
		  KEY user_id (user_id),
		  KEY popup_id (popup_id),
		  KEY email_hash (email_hash)
		) $charset_collate;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	public function get_by_email( $email = '' ) {

	}


	public function query( $args = array(), $return_type = 'OBJECT' ) {
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

		return $wpdb->get_results( $query, $return_type );
	}

	/**
	 * @param $args
	 *
	 * @return int
	 */
	public function total_rows( $args ) {
		$args['limit']  = null;
		$args['offset'] = null;
		$args['page']   = null;

		$results = $this->query( $args );

		return $results ? count( $results ) : 0;
	}
}
