<?php
/**
 * Repository Interface
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface PUM_Interface_Repository
 *
 * Interface between WP_Query and our data needs. Essentially a query factory.
 *
 * @package ForumWP\Interfaces
 */
interface PUM_Interface_Repository {

	/**
	 * Get specified item.
	 *
	 * @param int $id
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 */
	public function get_item( $id );

	/**
	 * Function has_item.
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function has_item( $id );

	/**
	 * Gets items.
	 *
	 * @param array $args
	 *
	 * @return WP_Post[||PUM_Abstract_Model_Post[]
	 */
	public function get_items( $args = [] );

	/**
	 * Creates item with data.
	 *
	 * @param array $data
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 */
	public function create_item( $data );

	/**
	 * Updates item.
	 *
	 * @param int   $id
	 * @param array $data
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 */
	public function update_item( $id, $data );

	/**
	 * Delete specified item.
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function delete_item( $id );

}
