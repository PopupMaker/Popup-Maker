<?php
/**
 * Utility functions.
 *
 * @since X.X.X
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;


/**
 * Change camelCase to snake_case.
 *
 * @param string $str String to convert.
 *
 * @return string Converted string.
 *
 * @since X.X.X
 */
function camel_case_to_snake_case( $str ) {
	return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $str ) );
}

/**
 * Change snake_case to camelCase.
 *
 * @param string $str String to convert.
 *
 * @return string Converted string.
 *
 * @since X.X.X
 */
function snake_case_to_camel_case( $str ) {
	return lcfirst( str_replace( '_', '', ucwords( $str, '_' ) ) );
}

/**
 * Get array values using dot.notation.
 *
 * @param string              $key Key to fetch.
 * @param array<string,mixed> $data Array to fetch from.
 * @param string|null         $key_case Case to use for key (snake_case|camelCase).
 *
 * @return mixed|null
 *
 * @since X.X.X
 */
function fetch_key_from_array( $key, $data, $key_case = null ) {
	// If key is .notation, then we need to traverse the array.
	$dotted_keys = explode( '.', $key );

	foreach ( $dotted_keys as $key ) {
		if ( $key_case ) {
			switch ( $key_case ) {
				case 'snake_case':
					// Check if key is camelCase & convert to snake_case.
					$key = camel_case_to_snake_case( $key );
					break;
				case 'camelCase':
					// Check if key is snake_case & convert to camelCase.
					$key = snake_case_to_camel_case( $key );
					break;
			}
		}

		if ( ! isset( $data[ $key ] ) ) {
			return null;
		}

		$data = $data[ $key ];
	}

	return $data ? $data : null;
}

/**
 * Generate a short unique ID.
 * This generates a unique ID that is URL-safe by combining timestamp and random elements.
 *
 * @param string $prefix Optional prefix for the UUID.
 * @param int    $random_length Length of random suffix (default 4).
 * @return string
 */
function generate_uuid( $prefix = '', $random_length = 4 ) {
	// Get microtime as base36 - this gives us a 6-7 character time component
	$time = base_convert( str_replace( '.', '', microtime( true ) ), 10, 36 );

	// Add random suffix
	$chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$random = '';

	for ( $i = 0; $i < $random_length; $i++ ) {
		$random .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
	}

	return $prefix . $time . $random;
}
