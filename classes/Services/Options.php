<?php
/**
 * Plugin options manager..
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Services;

use PopupMaker\Base\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class Options
 */
class Options extends Service {

	/**
	 * Unique Prefix per plugin.
	 *
	 * @var string
	 */
	public $prefix;

	/**
	 * Action namespace.
	 *
	 * @var string
	 */
	public $namespace;

	/**
	 * Initialize Options on run.
	 *
	 * @param \PopupMaker\Plugin\Core $container Container.
	 */
	public function __construct( $container ) {
		parent::__construct( $container );

		$prefix = (string) $this->container->get( 'option_prefix' );

		// Set the prefix on init.
		$this->prefix    = ! empty( $prefix ) ? trim( $prefix, '_' ) . '_' : '';
		$this->namespace = ! empty( $prefix ) ? trim( $prefix, '_/' ) . '/' : '';
	}

	/**
	 * Get Settings
	 *
	 * Retrieves all plugin settings
	 *
	 * @return array<string,mixed> settings
	 */
	public function get_all() {
		$settings = \get_option( $this->prefix . 'settings' );

		// TODO the below should really be done at install or during upgrade.
		// if ( ! is_array( $settings ) ) {
		// $settings = \PopupMaker\get_default_settings();
		// \update_option( $this->prefix . 'settings', $settings );
		// }

		/**
		 * Filter the settings.
		 *
		 * @param array $settings Settings.
		 *
		 * @return array
		 */
		return apply_filters( $this->namespace . 'get_options', $settings );
	}

	/**
	 * Get an option
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @param string $key Option key.
	 * @param bool   $default_value Default value.
	 *
	 * @return mixed|void
	 */
	public function get( $key = '', $default_value = false ) {
		$data = $this->get_all();

		// Fetch key from array, converting to camelcase (how data is stored). Supports dot.notation.
		$value = \PopupMaker\fetch_key_from_array( $key, $data, 'camelCase' );

		// If no value, return default.
		if ( null === $value ) {
			$value = $default_value;
		}

		/**
		 * Filter the option.
		 *
		 * @param mixed $value Option value.
		 * @param string $key Option key.
		 * @param mixed $default_value Default value.
		 *
		 * @return mixed
		 */
		return apply_filters( $this->namespace . 'get_option', $value, $key, $default_value );
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
	 * @param string          $key The Key to update.
	 * @param string|bool|int $value The value to set the key to.
	 *
	 * @return boolean True if updated, false if not.
	 */
	public function update( $key = '', $value = false ) {

		// If no key, exit.
		if ( empty( $key ) ) {
			return false;
		}

		if ( empty( $value ) ) {
			$remove_option = $this->delete( $key );

			return $remove_option;
		}

		// First let's grab the current settings.
		$options = $this->get_all();

		/**
		 * Filter the new option value.
		 *
		 * @param mixed $value Option value.
		 * @param string $key Option key.
		 *
		 * @return mixed
		 */
		$value = apply_filters( $this->namespace . 'update_option', $value, $key );

		// Next let's try to update the value.
		$options[ $key ] = $value;
		$did_update      = \update_option( $this->prefix . 'settings', $options );

		return $did_update;
	}

	/**
	 * Update many values at once.
	 *
	 * @param array<string,mixed> $new_options Array of new replacement options.
	 *
	 * @return bool
	 */
	public function update_many( $new_options = [] ) {
		$options = $this->get_all();

		// Lets merge options that may exist previously that are not existing now.
		foreach ( $new_options as $key => $value ) {
			// If no key, exit.
			if ( empty( $key ) ) {
				continue;
			}

			if ( empty( $value ) && isset( $options[ $key ] ) ) {
				unset( $options[ $key ] );
			}

			/**
			 * Filter the new option value.
			 *
			 * @param mixed $value Option value.
			 * @param string $key Option key.
			 *
			 * @return mixed
			 */
			$value = apply_filters( $this->namespace . 'update_option', $value, $key );

			// Next let's try to update the value.
			$options[ $key ] = $value;
		}

		$did_update = \update_option( $this->prefix . 'settings', $options );

		return $did_update;
	}

	/**
	 * Remove an option
	 *
	 * @param string|string[] $keys Can be a single string  or array of option keys.
	 *
	 * @return boolean True if updated, false if not.
	 */
	public function delete( $keys ) {

		// If no key, exit.
		if ( empty( $keys ) ) {
			return false;
		} elseif ( is_string( $keys ) ) {
			$keys = [ $keys ];
		}

		// First let's grab the current settings.
		$options = $this->get_all();

		// Remove each key/value pair.
		foreach ( $keys as $key ) {
			if ( isset( $options[ $key ] ) ) {
				unset( $options[ $key ] );
			}
		}

		$did_update = \update_option( $this->prefix . 'settings', $options );

		return $did_update;
	}

	/**
	 * Remaps option keys.
	 *
	 * @param array<string,string> $remap_array an array of $old_key => $new_key values.
	 *
	 * @return bool
	 */
	public function remap_keys( $remap_array = [] ) {
		$options = $this->get_all();

		/**
		 * Remap array keys by first getting current value,
		 * moving it to new key, finally deleting old key.
		 */
		foreach ( $remap_array as $key => $new_key ) {
			$value = $this->get( $key, false );
			if ( ! empty( $value ) ) {
				$options[ $new_key ] = $value;
			}
			unset( $options[ $key ] );
		}

		$did_update = \update_option( $this->prefix . 'settings', $options );

		return $did_update;
	}
}
