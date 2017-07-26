<?php
/**
 * @copyright   Copyright (c) 2017, Jungle Plugins
 * @author      Daniel Iser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Cache
 */
class PUM_Cache {

	/**
	 * @var string
	 */
	static $prefix = 'jpf';

	/**
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
		return empty( $string ) ? static::$prefix : static::$prefix . '_' . $string;
	}

	/**
	 * @param $key
	 * @param string $group
	 *
	 * @return mixed
	 */
	public static function get_timeout( $key, $group = '' ) {
		return apply_filters( 'pum_cache_timeout', pum_cache_timeout( $group ), $key, $group );
	}

	/**
	 * @param $key
	 * @param $data
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function add( $key, $data, $group = '' ) {
		if ( ! static::enabled() ) {
			return true;
		}

		return wp_cache_add( $key, $data, static::prefix_( $group ), static::get_timeout( $key, $group ) );
	}

	/**
	 * @param $key
	 * @param $data
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function replace( $key, $data, $group = '' ) {
		if ( ! static::enabled() ) {
			return true;
		}

		return wp_cache_replace( $key, $data, static::prefix_( $group ), static::get_timeout( $key, $group ) );
	}

	/**
	 * @param $key
	 * @param $data
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function set( $key, $data, $group = '' ) {
		if ( ! static::enabled() ) {
			return true;
		}

		return wp_cache_set( $key, $data, static::prefix_( $group ), static::get_timeout( $key, $group ) );
	}

	/**
	 * @param $key
	 * @param string $group
	 * @param bool $force
	 * @param null $found
	 *
	 * @return bool|mixed
	 */
	public static function get( $key, $group = '', $force = false, &$found = null ) {
		if ( ! static::enabled() ) {
			return false;
		}

		return wp_cache_get( $key, static::prefix_( $group ), $force, $found );
	}

	/**
	 * @param $key
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function delete( $key, $group = '' ) {
		if ( ! static::enabled() ) {
			return true;
		}

		return wp_cache_delete( $key, static::prefix_( $group ) );
	}

	/**
	 * @param $key
	 * @param int $offset
	 * @param string $group
	 *
	 * @return bool|false|int
	 */
	public static function incr( $key, $offset = 1, $group = '' ) {
		if ( ! static::enabled() ) {
			return true;
		}

		return wp_cache_incr( $key, $offset, static::prefix_( $group ) );
	}

	/**
	 * @param $key
	 * @param int $offset
	 * @param string $group
	 *
	 * @return bool|false|int
	 */
	public static function decr( $key, $offset = 1, $group = '' ) {
		if ( ! static::enabled() ) {
			return true;
		}

		return wp_cache_decr( $key, $offset, static::prefix_( $group ) );
	}

}
