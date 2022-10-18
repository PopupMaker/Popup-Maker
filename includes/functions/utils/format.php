<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @param int      $time
 * @param int|null $current
 *
 * @return mixed
 */
function pum_human_time( $time, $current = null ) {
	return PUM_Utils_Format::human_time( $time, $current );
}

/**
 * @param int|float $number
 * @param string    $format
 *
 * @return int|string
 */
function pum_format_number( $number, $format = '' ) {
	return PUM_Utils_Format::number( $number, $format );
}

/**
 * @param int|float $number
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
 * @param string $content
 *
 * @return string
 */
function pum_unwrap_urls( $content = '' ) {
	return PUM_Utils_Format::unwrap_urls( $content );
}
