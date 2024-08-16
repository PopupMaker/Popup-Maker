<?php
/**
 * Subscribers DB Handler
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */
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
	public $version = 20200917;

	/**
	 * The name of the primary column
	 */
	public $primary_key = 'ID';

	/**
	 * Get columns and formats
	 */
	public function get_columns() {
		return [
			'ID'           => '%d',
			'uuid'         => '%s',
			'popup_id'     => '%d',
			'email_hash'   => '%s',
			'email'        => '%s',
			'name'         => '%s',
			'fname'        => '%s',
			'lname'        => '%s',
			'user_id'      => '%d',
			'consent_args' => '%s',
			'consent'      => '%s',
			'created'      => '%s',
		];
	}

	/**
	 * Get default column values
	 */
	public function get_column_defaults() {
		return [
			'uuid'         => '',
			'popup_id'     => 0,
			'email_hash'   => '',
			'email'        => '',
			'name'         => '',
			'fname'        => '',
			'lname'        => '',
			'user_id'      => 0,
			'consent_args' => '',
			'consent'      => 'no',
			'created'      => current_time( 'mysql', 0 ),
		];
	}

	/**
	 * Create the table
	 */
	public function create_table() {

		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();

		/**
		 * - [x] You must put each field on its own line in your SQL statement.
		 * - [x] You must have two spaces between the words PRIMARY KEY and the definition of your primary key.
		 * - [x] You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY.
		 * - [x] KEY must be followed by a SINGLE SPACE then the key name then a space then open parenthesis with the field name then a closed parenthesis.
		 * - [x] You must not use any apostrophes or backticks around field names.
		 * - [x] Field types must be all lowercase.
		 * - [x] SQL keywords, like CREATE TABLE and UPDATE, must be uppercase.
		 * - [x] You must specify the length of all fields that accept a length parameter. int(11), for example.
		 */
		$sql = 'CREATE TABLE ' . $this->table_name() . " (
			ID bigint(20) NOT NULL AUTO_INCREMENT,
			email_hash varchar(32) NOT NULL,
			popup_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			email varchar(191) NOT NULL,
			name varchar(255) NOT NULL,
			fname varchar(255) NOT NULL,
			lname varchar(255) NOT NULL,
			uuid varchar(255) NOT NULL,
			consent varchar(255) NOT NULL,
			consent_args longtext NOT NULL,
			created datetime NOT NULL,
		  PRIMARY KEY  (ID),
		  KEY email (email),
		  KEY user_id (user_id),
		  KEY popup_id (popup_id),
		  KEY email_hash (email_hash)
		) $charset_collate;";

		$results = dbDelta( $sql );

		// Strip prefix to ensure it doesn't leak unintentionally.
		$results = str_replace( $wpdb->prefix, '', implode( ',', $results ) );

		pum_log_message( 'Subscriber table results: ' . $results );

		$previous_error = $wpdb->last_error; // The show tables query will erase the last error. So, record it now in case we need it.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name()}'" ) !== $this->table_name() ) {
			pum_log_message( 'Subscriber table exists check failed! Last error from wpdb: ' . str_replace( $wpdb->prefix, '', $previous_error ) );
		}

		update_option( $this->table_name . '_db_version', $this->version );
	}

	public function get_by_email( $email = '' ) {
	}


	public function query( $args = [], $return_type = 'OBJECT' ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			[
				'fields'  => '*',
				'page'    => null,
				'limit'   => null,
				'offset'  => null,
				's'       => null,
				'orderby' => null,
				'order'   => null,
			]
		);

		$columns = $this->get_columns();

		$fields = $args['fields'];

		if ( '*' === $fields ) {
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
		$values = [];

		// Define an empty WHERE clause to start from.
		$where = 'WHERE 1=1';

		// Build search query.
		if ( $args['s'] && ! empty( $args['s'] ) ) {
			$search = wp_unslash( trim( $args['s'] ) );

			$search_where = [];

			foreach ( $columns as $key => $type ) {
				if ( in_array( $key, $fields ) ) {
					if ( '%s' === $type || ( '%d' === $type && is_numeric( $search ) ) ) {
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
			$query   .= ' ORDER BY %i';
			$values[] = wp_unslash( trim( $args['orderby'] ) );

			switch ( $args['order'] ) {
				case 'asc':
				case 'ASC':
					$query .= ' ASC';
					break;
				case 'desc':
				case 'DESC':
				default:
					$query .= ' DESC';
					break;
			}
		}

		if ( ! empty( $args['limit'] ) ) {
			$query   .= ' LIMIT %d';
			$values[] = absint( $args['limit'] );
		}

		// Pagination.
		if ( $args['page'] >= 1 ) {
			$args['offset'] = ( $args['page'] * $args['limit'] ) - $args['limit'];
		}

		if ( ! empty( $args['offset'] ) ) {
			$query   .= ' OFFSET %d';
			$values[] = absint( $args['offset'] );
		}

		if ( strpos( $query, '%s' ) || strpos( $query, '%d' ) || strpos( $query, '%i' ) ) {
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
