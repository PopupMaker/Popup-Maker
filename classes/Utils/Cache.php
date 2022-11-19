<?php
/**
 * Cache utility.
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class PUM_Utils_Cache
 */
class PUM_Utils_Cache {

	/**
	 * Sets prefix
	 *
	 * @var string
	 */
	static $prefix = 'pum';

	/**
	 * Checks if enabled
	 *
	 * @return bool
	 */
	public static function enabled() {
		return (bool) ! pum_get_option( 'disable_cache', false );
	}

	/**
	 * Returns the general
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function prefix_( $string = '' ) {
		return empty( $string ) ? self::$prefix : self::$prefix . '_' . $string;
	}

	/**
	 * Gets cache timeout
	 *
	 * @param        $key Specified item.
	 * @param string $group Group to retrieve.
	 *
	 * @return mixed
	 */
	public static function get_timeout( $key, $group = '' ) {
		return apply_filters( 'pum_cache_timeout', pum_cache_timeout( $group ), $key, $group );
	}

	/**
	 * Add cache function utility
	 *
	 * @param        $key Specified item
	 * @param        $data Data
	 * @param string $group Group string
	 *
	 * @return bool
	 */
	public static function add( $key, $data, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_add( $key, $data, self::prefix_( $group ), self::get_timeout( $key, $group ) );
	}

	/**
	 * Replaces cache
	 *
	 * @param        $key Specified item.
	 * @param        $data Data
	 * @param string $group Group
	 *
	 * @return bool
	 */
	public static function replace( $key, $data, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_replace( $key, $data, self::prefix_( $group ), self::get_timeout( $key, $group ) );
	}

	/**
	 * Sets cache
	 *
	 * @param        $key Specified item.
	 * @param        $data Data
	 * @param string $group Group
	 *
	 * @return bool
	 */
	public static function set( $key, $data, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_set( $key, $data, self::prefix_( $group ), self::get_timeout( $key, $group ) );
	}

	/**
	 * Gets cache
	 *
	 * @param        $key Specified item.
	 * @param string $group Group
	 * @param bool   $force Force get cache - false by default.
	 * @param null   $found Found get cache - null by default.
	 *
	 * @return bool|mixed
	 */
	public static function get( $key, $group = '', $force = false, &$found = null ) {
		if ( ! self::enabled() ) {
			return false;
		}

		return wp_cache_get( $key, self::prefix_( $group ), $force, $found );
	}

	/**
	 * Delete cache
	 *
	 * @param        $key Specified item.
	 * @param string $group Group
	 *
	 * @return bool
	 */
	public static function delete( $key, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_delete( $key, self::prefix_( $group ) );
	}

	/**
	 * Cache Delete Group
	 *
	 * @param string $group Group to delete.
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
	 * Increments numeric cache item’s value.
	 *
	 * @param        $key Specified item.
	 * @param int    $offset Amount to increment.
	 * @param string $group Group
	 *
	 * @return bool|false|int
	 */
	public static function incr( $key, $offset = 1, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_incr( $key, $offset, self::prefix_( $group ) );
	}

	/**
	 * Decrements numeric cache item’s value.
	 *
	 * @param        $key Specified item.
	 * @param int    $offset Amount to decrement.
	 * @param string $group Group
	 *
	 * @return bool|false|int
	 */
	public static function decr( $key, $offset = 1, $group = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		return wp_cache_decr( $key, $offset, self::prefix_( $group ) );
	}

}
