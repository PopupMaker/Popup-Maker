<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks whether function is disabled.
 *
 * @since 1.4
 *
 * @param string $function Name of the function.
 *
 * @return bool Whether or not function is disabled.
 */
function pum_is_func_disabled( $function ) {
	$disabled = explode( ',', ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}


if ( ! function_exists( 'write_log' ) ) {
	function write_log( $log ) {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}
}

if ( ! function_exists( 'boolval' ) ) {
	function boolval( $val ) {
		return ( bool ) $val;
	}
}
