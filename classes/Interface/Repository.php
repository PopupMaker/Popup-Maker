<?php
/**
 * Interface for Repository
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

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
	 * @param int $id
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 */
	public function get_item( $id );

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function has_item( $id );

	/**
	 * @param array $args
	 *
	 * @return WP_Post[||PUM_Abstract_Model_Post[]
	 */
	public function get_items( $args = [] );

	/**
	 * @param array $data
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 */
	public function create_item( $data );

	/**
	 * @param int   $id
	 * @param array $data
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 */
	public function update_item( $id, $data );

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function delete_item( $id );
}
