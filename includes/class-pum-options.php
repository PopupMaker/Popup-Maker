<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Options
 */
class PUM_Options {

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
	 */
	public static function init() {
		// Set the prefix on init.
		self::$_data = self::get_all();
	}

	/**
	 * Get Settings
	 *
	 * Retrieves all plugin settings
	 *
	 * @return array settings
	 */
	public static function get_all() {
		$settings = get_option( self:: $_prefix . 'settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return apply_filters( self:: $_prefix . 'get_options', $settings );
	}

	/**
	 * Get an option
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @param string $key
	 * @param bool $default
	 *
	 * @return mixed
	 */
	public static function get( $key = '', $default = false ) {
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
	 * @since 1.0.0
	 *
	 * @param string $key The Key to update
	 * @param string|bool|int $value The value to set the key to
	 *
	 * @return boolean True if updated, false if not.
	 */
	public static function update( $key = '', $value = false ) {

		// If no key, exit
		if ( empty( $key ) ) {
			return false;
		}

		if ( empty( $value ) ) {
			$remove_option = self::delete( $key );

			return $remove_option;
		}

		// First let's grab the current settings
		$options = get_option( self:: $_prefix . 'settings' );

		// Let's let devs alter that value coming in
		$value = apply_filters( self::$_prefix . 'update_option', $value, $key );

		// Next let's try to update the value
		$options[ $key ] = $value;
		$did_update      = update_option( self:: $_prefix . 'settings', $options );

		// If it updated, let's update the global variable
		if ( $did_update ) {
			self::$_data[ $key ] = $value;

		}

		return $did_update;
	}

	/**
	 * Remove an option
	 *
	 * Removes a setting value in both the db and the global variable.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The Key to delete
	 *
	 * @return boolean True if updated, false if not.
	 */
	public static function delete( $key = '' ) {

		// If no key, exit
		if ( empty( $key ) ) {
			return false;
		}

		// First let's grab the current settings
		$options = get_option( self:: $_prefix . 'settings' );

		// Next let's try to update the value
		if ( isset( $options[ $key ] ) ) {
			unset( $options[ $key ] );
		}

		$did_update = update_option( self:: $_prefix . 'settings', $options );

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
	public static function remap_keys( $remap_array = array() ) {
		$options = self::get_all();

		foreach ( $remap_array as $key => $new_key ) {
			$options[ $new_key ] = self::get( $key, false );
			unset( $options[ $key ] );
		}

		$did_update = update_option( self:: $_prefix . 'settings', $options );

		// If it updated, let's update the global variable
		if ( $did_update ) {
			self::$_data = $options;
		}

		return $did_update;
	}

}
