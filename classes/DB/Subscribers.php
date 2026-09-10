<?php
/**
 * Subscribers DB Handler
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PUM_Subscribers Class
 */
class PUM_DB_Subscribers extends PUM_Abstract_Database {

	/**
	 * Option used to track the stored subscriber name scrub.
	 */
	const NAME_SCRUB_OPTION = 'pum_subscriber_name_scrub_20260910';

	/**
	 * Number of unsafe subscriber rows to scrub per request.
	 */
	const NAME_SCRUB_BATCH_SIZE = 100;

	/**
	 * The name of our database table
	 */
	public $table_name = 'pum_subscribers';

	/**
	 * The version of our database table
	 */
	public $version = 20260810;

	/**
	 * The name of the primary column
	 */
	public $primary_key = 'ID';

	/**
	 * Initialize the database and continue any pending security scrub.
	 */
	public function __construct() {
		parent::__construct();

		$this->maybe_scrub_unsafe_name_fields();
	}

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
	 * Continue the one-time scrub of unsafe stored subscriber names.
	 */
	private function maybe_scrub_unsafe_name_fields() {
		$cursor = get_option( self::NAME_SCRUB_OPTION, 0 );

		if ( 'complete' === $cursor ) {
			return;
		}

		$result = $this->scrub_unsafe_name_fields( absint( $cursor ), self::NAME_SCRUB_BATCH_SIZE );

		if ( false === $result ) {
			return;
		}

		update_option( self::NAME_SCRUB_OPTION, $result['complete'] ? 'complete' : $result['last_id'], false );
	}

	/**
	 * Scrub one batch of stored HTML from subscriber name fields.
	 *
	 * @param int $after_id Only inspect rows after this subscriber ID.
	 * @param int $limit    Maximum number of suspicious rows to process.
	 *
	 * @return array{processed:int,updated:int,last_id:int,complete:bool}|false Batch result, or false on failure.
	 */
	public function scrub_unsafe_name_fields( $after_id = 0, $limit = 100 ) {
		global $wpdb;

		$after_id = absint( $after_id );
		$limit    = max( 1, absint( $limit ) );
		$pattern  = '%' . $wpdb->esc_like( '<' ) . '%';

		if ( $this->wp_version >= 6.2 ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT ID, name, fname, lname FROM %i WHERE ID > %d AND (name LIKE %s OR fname LIKE %s OR lname LIKE %s) ORDER BY ID ASC LIMIT %d',
					$this->table_name(),
					$after_id,
					$pattern,
					$pattern,
					$pattern,
					$limit
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// Ignored because the table name is an internal identifier and WordPress <=6.2 does not support %i.
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT ID, name, fname, lname FROM {$this->table_name()} WHERE ID > %d AND (name LIKE %s OR fname LIKE %s OR lname LIKE %s) ORDER BY ID ASC LIMIT %d",
					$after_id,
					$pattern,
					$pattern,
					$pattern,
					$limit
				),
				ARRAY_A
			);
		}

		if ( ! is_array( $rows ) ) {
			return false;
		}

		$updated = 0;
		$last_id = $after_id;

		foreach ( $rows as $row ) {
			$data = [];

			foreach ( [ 'name', 'fname', 'lname' ] as $field ) {
				$sanitized = sanitize_text_field( $row[ $field ] );

				if ( $sanitized !== $row[ $field ] ) {
					$data[ $field ] = $sanitized;
				}
			}

			if ( ! empty( $data ) ) {
				$result = $wpdb->update(
					$this->table_name(),
					$data,
					[ 'ID' => absint( $row['ID'] ) ],
					null,
					[ '%d' ]
				);

				if ( false === $result ) {
					return false;
				}

				$updated += $result;
			}

			$last_id = absint( $row['ID'] );
		}

		return [
			'processed' => count( $rows ),
			'updated'   => $updated,
			'last_id'   => $last_id,
			'complete'  => count( $rows ) < $limit,
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
		  KEY email_hash (email_hash),
		  KEY created (created)
		) $charset_collate;";

		$results = dbDelta( $sql );

		// Strip prefix to ensure it doesn't leak unintentionally.
		$results = str_replace( $wpdb->prefix, '', implode( ',', $results ) );

		pum_log_message( 'Subscriber table results: ' . $results );

		$previous_error = $wpdb->last_error; // The show tables query will erase the last error. So, record it now in case we need it.

		if ( $this->wp_version >= 6.2 ) {
			$table_found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name() ) );
		} else {
			// Ignored because table names need string quotes for SHOW TABLES LIKE, not identifier backticks.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$table_found = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name()}'" );
		}

		if ( $this->table_name() !== $table_found ) {
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
				'where'   => [],
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

		$where = $this->prepare_where_clause( $args, $fields, $values );

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
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$query = $wpdb->prepare( $query, $values );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $query, $return_type );
	}

	/**
	 * Build the shared WHERE clause for row and count queries.
	 *
	 * @param array<string,mixed> $args Query arguments.
	 * @param string[]            $fields Selected fields.
	 * @param array<int,mixed>    $values Prepared-query values.
	 * @return string
	 */
	protected function prepare_where_clause( $args, $fields, &$values ) {
		global $wpdb;

		$columns = $this->get_columns();
		$where   = 'WHERE 1=1';

		foreach ( (array) $args['where'] as $column => $value ) {
			if ( ! isset( $columns[ $column ] ) || ! is_scalar( $value ) ) {
				continue;
			}

			$where   .= " AND `$column` = {$columns[$column]}";
			$values[] = $value;
		}

		if ( $args['s'] && ! empty( $args['s'] ) ) {
			$search       = wp_unslash( trim( $args['s'] ) );
			$search_where = [];

			foreach ( $columns as $key => $type ) {
				if ( in_array( $key, $fields, true ) && ( '%s' === $type || ( '%d' === $type && is_numeric( $search ) ) ) ) {
					$values[]       = '%' . $wpdb->esc_like( $search ) . '%';
					$search_where[] = "`$key` LIKE '%s'";
				}
			}

			if ( ! empty( $search_where ) ) {
				$where .= ' AND (' . join( ' OR ', $search_where ) . ')';
			}
		}

		return $where;
	}

	/**
	 * @param $args
	 *
	 * @return int
	 */
	public function total_rows( $args ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			[
				'fields' => '*',
				's'      => null,
				'where'  => [],
			]
		);

		$fields = '*' === $args['fields'] ? array_keys( $this->get_columns() ) : array_map( 'trim', explode( ',', $args['fields'] ) );
		$values = [];
		$where  = $this->prepare_where_clause( $args, $fields, $values );
		$query  = "SELECT COUNT(*) FROM %i $where";
		$values = array_merge( [ $this->table_name() ], $values );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $values ) );
	}
}
