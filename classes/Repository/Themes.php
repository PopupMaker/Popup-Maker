<?php
/**
 * Repository Themes
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Themes
 */
class PUM_Repository_Themes extends PUM_Abstract_Repository_Posts {

	/**
	 * @var string
	 */
	protected $model = 'PUM_Model_Theme';

	/**
	 * @return string
	 */
	protected function get_post_type() {
		return 'popup_theme';
	}

	/**
	 * Build the args for WP Query.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	protected function build_wp_query_args( $args = [] ) {
		// Ordering
		$orderby = [];

		// Meta Query
		if ( isset( $args['meta_query'] ) && empty( $args['meta_query']['relation'] ) ) {
			$args['meta_query']['relation'] = 'AND';
		}

		if ( isset( $args['themes'] ) ) {
			/**
			 * If Looking for specific themes. No need for filtering.
			 */
			$args['post__in'] = wp_parse_id_list( $args['themes'] );

			unset( $args['themes'] );
		}

		/**
		 * Apply easy ordering options or allow setting it manually.
		 */
		if ( ! isset( $args['orderby'] ) ) {
			$orderby['post_modified'] = isset( $args['order'] ) ? $args['order'] : 'DESC';
		} elseif ( ! empty( $args['post__in'] ) && in_array( $args['orderby'], [ 'post__in', 'user_order' ], true ) ) {
			// This one can't be part of an $orderby array so needs to override.
			$orderby = 'post__in';
		} else {
			switch ( $args['orderby'] ) {
				case 'name':
					$orderby['post_title'] = isset( $args['order'] ) ? $args['order'] : 'ASC';
					break;
				case 'date':
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
	 * @return PUM_Model_Theme|WP_Post
	 * @throws \InvalidArgumentException
	 *
	 * Ignore phpcs because this explictly overrides the parent method return type.
	 */
	public function get_item( $id ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_item( $id );
	}

	/**
	 * @param array $args
	 *
	 * @return PUM_Model_Theme[]|WP_Post[]
	 *
	 * Ignore phpcs because this explictly overrides the parent method return type.
	 */
	public function get_items( $args = [] ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_items( $args );
	}

	/**
	 * @param array $data
	 *
	 * @return PUM_Model_Theme|WP_Post
	 * @throws InvalidArgumentException
	 *
	 * Ignore phpcs because this explictly overrides the parent method return type.
	 */
	public function create_item( $data ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::create_item( $data );
	}

	/**
	 * @param int   $id
	 * @param array $data
	 *
	 * @return PUM_Model_Theme|WP_Post
	 * @throws Exception
	 *
	 * Ignore phpcs because this explictly overrides the parent method return type.
	 */
	public function update_item( $id, $data ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
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
