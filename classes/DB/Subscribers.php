<?php
// Exit if accessed directly

/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
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
	public $version = 1;

	/**
	 * The name of the primary column
	 */
	public $primary_key = 'ID';

	/**
	 * Get columns and formats
	 */
	public function get_columns() {
		return array(
			'ID'         => '%d',
			'uuid'       => '%s',
			'popup_id'   => '%d',
			'email_hash' => '%s',
			'email'      => '%s',
			'name'       => '%s',
			'fname'      => '%s',
			'lname'      => '%s',
			'values'     => '%s',
			'user_id'    => '%d',
			'created'    => '%s',
		);
	}

	/**
	 * Get default column values
	 */
	public function get_column_defaults() {
		return array(
			'uuid'       => '',
			'popup_id'   => 0,
			'email_hash' => '',
			'email'      => '',
			'name'       => '',
			'fname'      => '',
			'lname'      => '',
			'values'     => '',
			'user_id'    => 0,
			'created'    => current_time( 'mysql', 0 ),
		);
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function create_table() {

		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . $this->table_name() . " (
			`ID` BIGINT(20) NOT NULL AUTO_INCREMENT,
			`email_hash` VARCHAR(255) NOT NULL,
			`popup_id` BIGINT(20) NOT NULL,
			`user_id` BIGINT(20) NOT NULL,
			`email` VARCHAR(255) NOT NULL,
			`name` VARCHAR(255) NOT NULL,
			`fname` VARCHAR(255) NOT NULL,
			`lname` VARCHAR(255) NOT NULL,
			`values` LONGTEXT NOT NULL,
			`uuid` VARCHAR(255) NOT NULL,
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
}