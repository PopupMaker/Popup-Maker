<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Add a cache key via PUM Cache.
 *
 * @param $key
 * @param $data
 * @param string $group
 *
 * @return bool
 */
function pum_cache_add( $key, $data, $group = '' ) {
	return PUM_Cache::add( $key, $data, $group );
}

/**
 * Set a cache key via PUM Cache.
 *
 * @param $key
 * @param $data
 * @param string $group
 *
 * @return bool
 */
function pum_cache_set( $key, $data, $group = '' ) {
	return PUM_Cache::set( $key, $data, $group );
}

/**
 * Replace a cache key via PUM Cache.
 *
 * @param $key
 * @param $data
 * @param string $group
 *
 * @return bool
 */
function pum_cache_replace( $key, $data, $group = '' ) {
	return PUM_Cache::replace( $key, $data, $group );
}

/**
 * Get a cache key via PUM Cache.
 *
 * @param $key
 * @param string $group
 * @param bool $force
 * @param null $found
 *
 * @return bool|mixed
 */
function pum_cache_get( $key, $group = '', $force = false, &$found = null ) {
	return PUM_Cache::get( $key, $group, $force, $found );
}

/**
 * Delete a cache key via PUM Cache.
 *
 * @param $key
 * @param string $group
 *
 * @return bool
 */
function pum_cache_delete( $key, $group = '' ) {
	return PUM_Cache::delete( $key, $group );
}

/**
 * Delete a cache group via PUM Cache.
 *
 * @param string $group
 *
 * @return bool
 */
function pum_cache_delete_group( $group = '' ) {
	return PUM_Cache::delete_group( $group );
}

/**
 * Increase a numeric cache value by the offset.
 *
 * @param $key
 * @param int $offset
 * @param string $group
 *
 * @return bool|false|int
 */
function pum_cache_incr( $key, $offset = 1, $group = '' ) {
	return PUM_Cache::incr( $key, $offset, $group );
}

/**
 * Decrease a numeric cache value by the offset.
 *
 * @param $key
 * @param int $offset
 * @param string $group
 *
 * @return bool|false|int
 */
function pum_cache_decr( $key, $offset = 1, $group = '' ) {
	return PUM_Cache::decr( $key, $offset, $group );
}

/**
 * Gets the filterable timeout for a cache object by key.
 *
 * @param $key
 *
 * @return int
 */
function pum_cache_timeout( $key ) {
	static $timeouts;

	if ( ! isset( $timeouts ) ) {
		$timeouts = apply_filters( 'pum_cache_timeouts', array() );
	}

	return isset( $timeouts[ $key ] ) ? $timeouts[ $key ] : 0;
}
