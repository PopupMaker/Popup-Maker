<?php
/**
 * Cache Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class PUM_Utils_Cache
 */
class PUM_Utils_Cache {

	/**
	 * @var string
	 */
	public static $prefix = 'pum';

	/**
	 * @return bool
	 */
	public static function enabled() {
		return (bool) ! pum_get_option( 'disable_cache', false );
	}

	/**
	 * Returns the general
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public static function prefix_( $str = '' ) {
		return empty( $str ) ? self::$prefix : self::$prefix . '_' . $str;
	}

	/**
	 * Get cache timeout for a key
	 *
	 * @param string|int $key Cache key
	 * @param string     $group Cache group
	 *
	 * @return int Cache timeout in seconds
	 */
	public static function get_timeout( $key, $group = '' ) {
		return apply_filters( 'pum_cache_timeout', pum_cache_timeout( $group ), $key, $group );
	}

	/**
	 * Add data to cache (only if key doesn't exist)
	 *
	 * @param string|int $key Cache key
	 * @param mixed      $data Data to cache (any serializable value)
	 * @param string     $group Cache group
	 *
	 * @return bool True on success, false on failure
	 */
	public static function add( $key, $data, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_add( $key, $data, self::prefix_( $group ), self::get_timeout( $key, $group ) );
	}

	/**
	 * Replace data in cache (only if key exists)
	 *
	 * @param string|int $key Cache key
	 * @param mixed      $data Data to cache (any serializable value)
	 * @param string     $group Cache group
	 *
	 * @return bool True on success, false on failure
	 */
	public static function replace( $key, $data, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_replace( $key, $data, self::prefix_( $group ), self::get_timeout( $key, $group ) );
	}

	/**
	 * Set data in cache (create or update)
	 *
	 * @param string|int $key Cache key
	 * @param mixed      $data Data to cache (any serializable value)
	 * @param string     $group Cache group
	 *
	 * @return bool True on success, false on failure
	 */
	public static function set( $key, $data, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_set( $key, $data, self::prefix_( $group ), self::get_timeout( $key, $group ) );
	}

	/**
	 * Get data from cache
	 *
	 * @param string|int $key Cache key
	 * @param string     $group Cache group
	 * @param bool       $force Force refresh from persistent cache
	 * @param-out bool|null $found Whether the key was found in cache
	 *
	 * @return mixed|false Cache data on success, false on failure or cache disabled
	 */
	public static function get( $key, $group = '', $force = false, &$found = null ) {
		if ( ! self::enabled() ) {
			return false;
		}

		return wp_cache_get( $key, self::prefix_( $group ), $force, $found );
	}

	/**
	 * Delete data from cache
	 *
	 * @param string|int $key Cache key
	 * @param string     $group Cache group
	 *
	 * @return bool True on success, false on failure
	 */
	public static function delete( $key, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_delete( $key, self::prefix_( $group ) );
	}

	/**
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function delete_group( $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		if ( ! function_exists( 'wp_cache_delete_group' ) ) {
			return false;
		}

		return wp_cache_delete_group( self::prefix_( $group ) );
	}



	/**
	 * Increment numeric cache value
	 *
	 * @param string|int $key Cache key
	 * @param int        $offset Amount to increment
	 * @param string     $group Cache group
	 *
	 * @return int|bool New value on success, false on failure, true when cache disabled
	 */
	public static function incr( $key, $offset = 1, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_incr( $key, $offset, self::prefix_( $group ) );
	}

	/**
	 * Decrement numeric cache value
	 *
	 * @param string|int $key Cache key
	 * @param int        $offset Amount to decrement
	 * @param string     $group Cache group
	 *
	 * @return int|bool New value on success, false on failure, true when cache disabled
	 */
	public static function decr( $key, $offset = 1, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_decr( $key, $offset, self::prefix_( $group ) );
	}
}
