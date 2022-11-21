<?php
/**
 * Utility Format Handler
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Determines the difference between two time stamps
 *
 * @param int      $time Time to format
 * @param int|null $current Current time
 *
 * @return mixed
 */
function pum_human_time( $time, $current = null ) {
	return PUM_Utils_Format::human_time( $time, $current );
}

/**
 * Formats number - function for utility
 *
 * @param int|float $number Number to format
 * @param string    $format Format
 *
 * @return int|string
 */
function pum_format_number( $number, $format = '' ) {
	return PUM_Utils_Format::number( $number, $format );
}

/**
 * @param int|float $number Number
 * @param string    $format U|human|human-readable
 *
 * @return int|string
 */
function pum_format_time( $number, $format = '' ) {
	return PUM_Utils_Format::time( $number, $format );
}

/**
 * Removes <p></p> around URLs
 *
 * @param string $content Content to remove paragraph tags from.
 *
 * @return string
 */
function pum_unwrap_urls( $content = '' ) {
	return PUM_Utils_Format::unwrap_urls( $content );
}
