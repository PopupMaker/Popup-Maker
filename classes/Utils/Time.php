<?php
/**
 * Time Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Time
 */
class PUM_Utils_Time {

	/**
	 * Check if a value is a valid Unix timestamp.
	 *
	 * Validates whether the input represents a valid Unix timestamp.
	 * A valid timestamp must be a positive integer (as string or int).
	 * Unix timestamps are positive integers representing seconds since 1970-01-01 00:00:00 UTC.
	 *
	 * @param int|string $timestamp Value to check for timestamp validity (accepts numeric strings)
	 * @return bool True if the value is a valid Unix timestamp, false otherwise
	 */
	public static function is_timestamp( $timestamp ) {
		return ( 1 === preg_match( '~^[1-9][0-9]*$~', (string) $timestamp ) );
	}
}
