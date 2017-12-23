<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get all forum options.
 *
 * @return mixed
 */
function pum_get_options() {
	return PUM_Options::get_all();
}

/**
 * Get a forum option.
 *
 * @param string $key
 * @param mixed $default
 *
 * @return mixed
 */
function pum_get_option( $key, $default = false ) {
	return PUM_Options::get( $key, $default );
}

/**
 * Update a forum option.
 *
 * @param string $key
 * @param bool $value
 *
 * @return bool
 */
function pum_update_option( $key = '', $value = false ) {
	return PUM_Options::update( $key, $value );
}

/**
 * Delete a forum option
 *
 * @param string $key
 *
 * @return bool
 */
function pum_delete_option( $key = '' ) {
	return PUM_Options::delete( $key );
}
