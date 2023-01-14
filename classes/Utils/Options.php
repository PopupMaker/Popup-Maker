<?php
/**
 * Options Utility
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Options
 */
class PUM_Utils_Options {

	/**
	 * Unique Prefix per plugin.
	 *
	 * @var string
	 */
	public static $_prefix = 'popmake_';

	/**
	 * Keeps static copy of the options during runtime.
	 *
	 * @var null|array
	 */
	private static $_data;

	/**
	 * Initialize Options on run.
	 *
	 * @param bool $force
	 */
	public static function init( $force = false ) {
		global $popmake_options;

		if ( ! isset( self::$_data ) || $force ) {
			self::$_data = self::get_all();

			/** @deprecated 1.7.0 */
			$popmake_options = self::$_data;
		}
	}

	/**
	 * Get Settings
	 *
	 * Retrieves all plugin settings
	 *
	 * @return array settings
	 */
	public static function get_all() {
		$settings = get_option( self::$_prefix . 'settings', [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		/* @deprecated filter. */
		$settings = apply_filters( 'popmake_get_settings', $settings );

		return apply_filters( self::$_prefix . 'get_options', $settings );
	}

	/**
	 * Get an option
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @param string $key
	 * @param bool   $default
	 *
	 * @return mixed
	 */
	public static function get( $key = '', $default = false ) {
		// Passive initialization.
		self::init();

		$value = isset( self::$_data[ $key ] ) ? self::$_data[ $key ] : $default;

		return apply_filters( self::$_prefix . 'get_option', $value, $key, $default );
	}

	/**
	 * Update an option
	 *
	 * Updates an setting value in both the db and the global variable.
	 * Warning: Passing in an empty, false or null string value will remove
	 *          the key from the _options array.
	 *
	 * @param string          $key   The Key to update
	 * @param string|bool|int $value The value to set the key to
	 *
	 * @return boolean True if updated, false if not.
	 */
	public static function update( $key = '', $value = false ) {
		// Passive initialization.
		self::init();

		// If no key, exit
		if ( empty( $key ) ) {
			return false;
		}

		if ( empty( $value ) ) {
			$remove_option = self::delete( $key );

			return $remove_option;
		}

		// First let's grab the current settings
		$options = get_option( self::$_prefix . 'settings' );

		// Let's let devs alter that value coming in
		$value = apply_filters( self::$_prefix . 'update_option', $value, $key );

		// Next let's try to update the value
		$options[ $key ] = $value;
		$did_update      = update_option( self::$_prefix . 'settings', $options );

		// If it updated, let's update the global variable
		if ( $did_update ) {
			self::$_data[ $key ] = $value;

		}

		return $did_update;
	}

	/**
	 * Update the entire settings array from a new array.
	 *
	 * @param array $new_options
	 *
	 * @return bool
	 */
	public static function update_all( $new_options = [] ) {
		// First let's grab the current settings
		$options = get_option( self::$_prefix . 'settings' );

		// Lets merge options that may exist previously that are not existing now.
		$new_options = wp_parse_args( $new_options, $options );

		$did_update = update_option( self::$_prefix . 'settings', $new_options );

		// If it updated, let's update the global variable
		if ( $did_update ) {
			self::$_data = $new_options;
		}

		return $did_update;
	}

	/**
	 * Merge the new options into the settings array.
	 *
	 * @param array $new_options
	 *
	 * @return bool
	 */
	public static function merge( $new_options = [] ) {

		$options = self::get_all();

		// Merge new options.
		foreach ( $new_options as $key => $val ) {
			$options[ $key ] = ! empty( $val ) ? $val : false;
		}

		$did_update = update_option( self::$_prefix . 'settings', $options );

		// If it updated, let's update the global variable
		if ( $did_update ) {
			self::$_data = $options;
		}

		return $did_update;
	}

	/**
	 * Remove an option or multiple
	 *
	 * Removes a setting value in both the db and the global variable.
	 *
	 * @param string|array $keys The Key/s to delete
	 *
	 * @return boolean True if updated, false if not.
	 */
	public static function delete( $keys = '' ) {
		// Passive initialization.
		self::init();

		// If no key, exit
		if ( empty( $keys ) ) {
			return false;
		} elseif ( is_string( $keys ) ) {
			$keys = [ $keys ];
		}

		// First let's grab the current settings
		$options = get_option( self::$_prefix . 'settings' );

		// Remove each key/value pair.
		foreach ( $keys as $key ) {
			if ( isset( $options[ $key ] ) ) {
				unset( $options[ $key ] );
			}
		}

		$did_update = update_option( self::$_prefix . 'settings', $options );

		// If it updated, let's update the global variable
		if ( $did_update ) {
			self::$_data = $options;
		}

		return $did_update;
	}

	/**
	 * Remaps option keys.
	 *
	 * @param array $remap_array an array of $old_key => $new_key values.
	 *
	 * @return bool
	 */
	public static function remap_keys( $remap_array = [] ) {
		$options = self::get_all();

		foreach ( $remap_array as $key => $new_key ) {
			$value = self::get( $key, false );
			if ( ! empty( $value ) ) {
				$options[ $new_key ] = $value;
			}
			unset( $options[ $key ] );
		}

		$did_update = update_option( self::$_prefix . 'settings', $options );

		// If it updated, let's update the global variable
		if ( $did_update ) {
			self::$_data = $options;
		}

		return $did_update;
	}

}
