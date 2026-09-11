<?php
/**
 * Repository Popups
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Repository_Popups
 */
class PUM_Repository_Popups extends PUM_Abstract_Repository_Posts {

	/**
	 * @var string
	 */
	protected $model = 'PUM_Model_Popup';

	/**
	 * Include the current site in cached popup model hashes.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return string
	 */
	protected function get_post_hash( $post ) {
		return md5( get_current_blog_id() . ':' . parent::get_post_hash( $post ) );
	}

	/**
	 * @return string
	 */
	protected function get_post_type() {
		return 'popup';
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

		if ( isset( $args['popups'] ) ) {
			/**
			 * If Looking for specific popups. No need for filtering.
			 */
			$args['post__in'] = wp_parse_id_list( $args['popups'] );

			unset( $args['popups'] );
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
	 * @return PUM_Model_Popup|WP_Post
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
	 * @return PUM_Model_Popup[]|WP_Post[]
	 *
	 * Ignore phpcs because this explictly overrides the parent method return type.
	 */
	public function get_items( $args = [] ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_items( $args );
	}

	/**
	 * Resolve a popup model through the canonical repository.
	 *
	 * This legacy repository no longer owns popup model storage. It delegates to
	 * `PopupMaker\Services\Repository\Popups`, the single request-local cache
	 * shared by frontend, admin, AJAX and modern services, so `pum()->popups`
	 * hands back the same object as every other API.
	 *
	 * Subclasses that override the model class keep the legacy hydration path,
	 * since the canonical repository only produces `PUM_Model_Popup` instances.
	 *
	 * @param int|WP_Post $id Post ID or object.
	 *
	 * @return WP_Post|PUM_Abstract_Model_Post
	 */
	protected function get_model( $id ) {
		if ( 'PUM_Model_Popup' !== $this->model ) {
			return parent::get_model( $id );
		}

		$post_id = is_a( $id, 'WP_Post' ) ? $id->ID : $id;

		if ( ! is_numeric( $post_id ) ) {
			return parent::get_model( $id );
		}

		$canonical = $this->canonical_repository();

		if ( ! $canonical ) {
			return parent::get_model( $id );
		}

		$popup = $canonical->get_canonical_item( $post_id );

		return $popup instanceof PUM_Model_Popup ? $popup : parent::get_model( $id );
	}

	/**
	 * Get the canonical popup repository when the container is available.
	 *
	 * @return \PopupMaker\Services\Repository\Popups|null
	 */
	private function canonical_repository() {
		if ( ! function_exists( '\PopupMaker\plugin' ) ) {
			return null;
		}

		$repository = \PopupMaker\plugin()->get( 'popups' );

		return $repository instanceof \PopupMaker\Services\Repository\Popups ? $repository : null;
	}

	/**
	 * Discard a cached popup model.
	 *
	 * Clears the legacy query/object caches and the canonical model cache so a
	 * single call still fully invalidates the popup for this request.
	 *
	 * @param int|numeric-string $item_id Popup ID.
	 *
	 * @return void
	 */
	public function forget_item( $item_id ) {
		$this->forget_local_item( $item_id );

		$canonical = $this->canonical_repository();

		if ( $canonical ) {
			$canonical->forget_item( $item_id );
		}
	}

	/**
	 * Discard only this repository's local caches for a popup.
	 *
	 * Used when the canonical model must survive — for example a metadata write
	 * that refreshes settings in place — while stale legacy query results still
	 * need clearing.
	 *
	 * @param int|numeric-string $item_id Popup ID.
	 *
	 * @return void
	 */
	public function forget_local_item( $item_id ) {
		unset( $this->cache['objects'][ (int) $item_id ] );
	}

	/**
	 * @param array $data
	 *
	 * @return PUM_Model_Popup|WP_Post
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
	 * @return PUM_Model_Popup|WP_Post
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
