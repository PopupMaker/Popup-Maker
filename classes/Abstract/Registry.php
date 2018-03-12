<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the construct for building an item registry or collection.
 *
 * @since 1.7.0
 */
abstract class PUM_Abstract_Registry {

	/**
	 * Array of registry items.
	 *
	 * @var    array
	 */
	private $items = array();

	/**
	 * Adds an item to the registry.
	 *
	 * @param int    $item_id   Item ID.
	 * @param array  $attributes {
	 *     Item attributes.
	 *
	 *     @type string $class Item handler class.
	 *     @type string $file  Item handler class file.
	 * }
	 *
	 * @return true Always true.
	 */
	public function add_item( $item_id, $attributes ) {
		foreach ( $attributes as $attribute => $value ) {
			$this->items[ $item_id ][ $attribute ] = $value;
		}

		return true;
	}

	/**
	 * Removes an item from the registry by ID.
	 *
	 * @param string $item_id Item ID.
	 */
	public function remove_item( $item_id ) {
		unset( $this->items[ $item_id ] );
	}

	/**
	 * Retrieves an item and its associated attributes.
	 *
	 * @param string $item_id Item ID.
	 *
	 * @return array|false Array of attributes for the item if registered, otherwise false.
	 */
	public function get( $item_id ) {
		if ( array_key_exists( $item_id, $this->items ) ) {
			return $this->items[ $item_id ];
		}
		return false;
	}

	/**
	 * Retrieves registered items.
	 *
	 * @return array The list of registered items.
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Only intended for use by tests.
	 */
	public function _reset_items() {
		if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
			_doing_it_wrong( 'PUM_Abstract_Registry::_reset_items', 'This method is only intended for use in phpunit tests', '1.7.0' );
		} else {
			$this->items = array();
		}
	}
}
