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

	public static function is_timestamp( $timestamp ) {
		return ( 1 === preg_match( '~^[1-9][0-9]*$~', $timestamp ) );
	}
}
