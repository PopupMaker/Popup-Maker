<?php
/**
 * DataStorage Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initializes a temporary data storage engine used by core in various capacities.
 */
class PUM_Utils_DataStorage {

	/**
	 * Retrieves stored data by key.
	 *
	 * Given a key, get the information from the database directly.
	 *
	 * @param string     $key The stored option key.
	 * @param null|mixed $default_value Optional. A default value to retrieve should `$value` be empty.
	 *                            Default null.
	 *
	 * @return mixed|false The stored data, value of `$default_value` if not null, otherwise false.
	 */
	public static function get( $key, $default_value = null ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", $key ) );

		if ( empty( $value ) && ! is_null( $default_value ) ) {
			return $default_value;
		}

		return empty( $value ) ? false : maybe_unserialize( $value );
	}

	/**
	 * Write some data based on key and value.
	 *
	 * @param string $key The option_name.
	 * @param mixed  $value The value to store.
	 */
	public static function write( $key, $value ) {
		global $wpdb;

		$value = maybe_serialize( $value );

		$data = [
			'option_name'  => $key,
			'option_value' => $value,
			'autoload'     => 'no',
		];

		$formats = self::get_data_formats( $value );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->replace( $wpdb->options, $data, $formats );
	}

	/**
	 * Derives the formats array based on the type of $value.
	 *
	 * @param mixed $value Value to store.
	 *
	 * @return array Formats array. First and last values will always be string ('%s').
	 */
	public static function get_data_formats( $value ) {

		switch ( gettype( $value ) ) {
			case 'integer':
				$formats = [ '%s', '%d', '%s' ];
				break;

			case 'double':
				$formats = [ '%s', '%f', '%s' ];
				break;

			default:
			case 'string':
				$formats = [ '%s', '%s', '%s' ];
				break;
		}

		return $formats;
	}

	/**
	 * Deletes a piece of stored data by key.
	 *
	 * @param string $key The stored option name to delete.
	 *
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public static function delete( $key ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->delete( $wpdb->options, [ 'option_name' => $key ] );
	}

	/**
	 * Deletes all options matching a given RegEx pattern.
	 *
	 * @param string $pattern Pattern to match against option keys.
	 *
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public static function delete_by_match( $pattern ) {
		global $wpdb;

		// Double check to make sure the batch_id got included before proceeding.
		if ( '^[0-9a-z\\_]+' !== $pattern && ! empty( $pattern ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name REGEXP %s", $pattern ) );
		} else {
			$result = false;
		}

		return $result;
	}
}
