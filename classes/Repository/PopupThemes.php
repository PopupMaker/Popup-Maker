<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

namespace ForumWP\Repository;

use Exception;
use ForumWP\Abstracts\Repository\Posts;
use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Forums
 *
 * @package ForumWP\Repository
 */
class Popups extends Posts {

	/**
	 * @var string
	 */
	protected $model = '\ForumWP\Model\Forum';

	/**
	 * @return string
	 */
	protected function get_post_type() {
		return forumwp_get_forum_post_type();
	}

	/**
	 * Returns an array of enforced query args that can't be over ridden, such as post type.
	 *
	 * @return array
	 */
	public function default_strict_query_args() {
		return array(
			'post_type' => $this->get_post_type(),
		);
	}

	/**
	 * Build the args for WP Query.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	protected function build_wp_query_args( $args = array() ) {
		// Ordering
		$orderby = array();

		// Meta Query
		if ( ! isset( $args['meta_query'] ) ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
			);
		}

		if ( isset( $args['forums'] ) ) {
			/**
			 * If Looking for specific forums. No need for filtering.
			 */
			$args['post__in'] = wp_parse_id_list( $args['forums'] );

			unset( $args['forums'] );
		} elseif ( empty( $args['post__in'] ) ) {

			/**
			 * If Looking for specific forums. No need for filtering.
			 */
			$type = isset( $args['type'] ) ? $args['type'] : '';
			unset( $args['type'] );

			// Types or false
			$types = null;

			if ( $type == 'all' ) {
				$types = array( 'forum', 'category' );
			} elseif ( $type != '' ) {
				$types = (array) $type;
			}

			// Type Meta Query
			if ( isset( $types ) ) {
				$args['meta_query'][] = array(
					'key'     => '_forumwp_type',
					'value'   => $types,
					'compare' => 'IN',
				);
			}

			// Forums to get per page
			if ( ! isset( $args['posts_per_page'] ) ) {
				$args['posts_per_page'] = forumwp_get_option( 'forums_per_page', 20 );
			}
		}

		/**
		 * Apply easy ordering options or allow setting it manually.
		 */
		if ( ! isset( $args['orderby'] ) ) {
			$orderby['post_modified'] = isset( $args['order'] ) ? $args['order'] : 'DESC';
		} elseif ( ! empty( $args['post__in'] ) && in_array( $args['orderby'], array( 'post__in', 'user_order' ) ) ) {
			// This one can't be part of an $orderby array so needs to override.
			$orderby = 'post__in';
		} else {
			switch ( $args['orderby'] ) {
				case 'name' :
					$orderby['post_title'] = isset( $args['order'] ) ? $args['order'] : 'ASC';
					break;
				case 'date' :
					$orderby['post_date'] = isset( $args['order'] ) ? $args['order'] : 'DESC';
					break;
				case 'activity':
					$orderby['post_modified'] = isset( $args['order'] ) ? $args['order'] : 'DESC';
					break;
				default:
					$orderby[ $args['orderby'] ] = isset( $args['order'] ) ? $args['order'] : 'DESC';
					break;
			}
		}

		// Replace the orderby property with the new $orderby array.
		$args['orderby'] = $orderby;

		// Clear unneeded values.
		unset( $args['order'] );

		return parent::build_wp_query_args( $args );
	}

	/**
	 * @param int $id
	 *
	 * @return \ForumWP\Model\Forum|\WP_Post
	 * @throws \InvalidArgumentException
	 */
	public function get_item( $id ) {
		return parent::get_item( $id );
	}

	/**
	 * @param array $args
	 *
	 * @return \ForumWP\Model\Forum[]|\WP_Post[]
	 */
	public function get_items( $args = array() ) {
		return parent::get_items( $args );
	}

	/**
	 * @param array $data
	 *
	 * @return \ForumWP\Model\Forum|\WP_Post
	 * @throws InvalidArgumentException
	 */
	public function create_item( $data ) {
		return parent::create_item( $data );
	}

	/**
	 * @param int   $id
	 * @param array $data
	 *
	 * @return \ForumWP\Model\Forum|\WP_Post
	 * @throws Exception
	 */
	public function update_item( $id, $data ) {
		return parent::update_item( $id, $data );
	}


	/**
	 * Assert that data is valid.
	 *
	 * @param array $data
	 *
	 * @throws InvalidArgumentException
	 */
	protected function assert_data( $data ) {
		// REQUIRED: Implement assert_data() method.
	}
}
