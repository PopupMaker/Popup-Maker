<?php
/******************************************************************************
 * @Copyright (c) 2018, Code Atlantic                                        *
 ******************************************************************************/

namespace ForumWP\Repository;

use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Items
 *
 * @package ForumWP\Repository
 */
class Items implements \ForumWP\Interfaces\Repository {

	protected $items = array();

	/**
	 * Initialize the repository.
	 *
	 * @param array $fields
	 */
	public function __construct( $fields = array() ) {
		$this->items = $fields;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function get_item( $id ) {
		if ( ! $this->has_item( $id ) ) {
			throw new InvalidArgumentException( sprintf( __( 'No tab with id %s found', 'forumwp' ), $id ) );
		}

		return $this->items[ $id ];
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function has_item( $id ) {
		return isset( $this->items[ $id ] );
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	public function get_items( $args ) {
		return $this->items;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function create_item( $data ) {
		// TODO Implement this.
	}

	/**
	 * @param int   $id
	 * @param array $data
	 *
	 * @return array
	 */
	public function update_item( $id, $data ) {
		if ( ! $this->has_item( $id ) ) {
			return $this->create_item( $data );
		}

		$this->items[ $id ] = array_merge( $this->items[ $id ], $data );

		return $this->items[ $id ];
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function delete_item( $id ) {
		unset( $this->items[ $id ] );

		return true;
	}

}