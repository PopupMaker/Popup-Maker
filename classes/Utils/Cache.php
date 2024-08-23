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
	 * @param        $key
	 * @param string $group
	 *
	 * @return mixed
	 */
	public static function get_timeout( $key, $group = '' ) {
		return apply_filters( 'pum_cache_timeout', pum_cache_timeout( $group ), $key, $group );
	}

	/**
	 * @param        $key
	 * @param        $data
	 * @param string $group
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
	 * @param        $key
	 * @param        $data
	 * @param string $group
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
	 * @param        $key
	 * @param        $data
	 * @param string $group
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
	 * @param        $key
	 * @param string $group
	 * @param bool   $force
	 * @param null   $found
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
	 * @param        $key
	 * @param string $group
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
	 * @param        $key
	 * @param int    $offset
	 * @param string $group
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
	 * @param        $key
	 * @param int    $offset
	 * @param string $group
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
