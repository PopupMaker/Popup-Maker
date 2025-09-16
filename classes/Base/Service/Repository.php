<?php
/**
 * Repository service.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Base\Service;

use PopupMaker\Base\Service;
use PopupMaker\Base\Model\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Repository service for managing Post-based entities.
 *
 * @since 1.21.0
 * @template TPost of Post
 * @template-extends Service<\PopupMaker\Plugin\Core>
 */
abstract class Repository extends Service {

	/**
	 * Post type key for registration.
	 *
	 * @var non-empty-string
	 */
	protected $post_type_key;

	/**
	 * Registered WordPress post type name.
	 *
	 * @var non-empty-string
	 */
	protected $post_type;

	/**
	 * Cache of instantiated items indexed by post ID.
	 *
	 * @var array<int, TPost>
	 */
	protected $items_by_id = [];

	/**
	 * Initialize the service.
	 *
	 * @param \PopupMaker\Plugin\Core $container Plugin container.
	 */
	public function __construct( $container ) {
		parent::__construct( $container );
		$this->post_type = $container->get_controller( 'PostTypes' )->get_type_key( $this->post_type_key );
	}

	/**
	 * Instantiate model from post.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return TPost|null
	 */
	abstract public function instantiate_model_from_post( $post );

	/**
	 * Cache an item in internal storage.
	 *
	 * @param TPost $item Item to cache by ID for fast retrieval.
	 * @return void
	 */
	protected function cache_item( $item ) {
		$this->items_by_id[ $item->ID ] = $item;
	}

	/**
	 * Get a list of all queried items.
	 *
	 * @param array<string, mixed> $args {
	 *     Optional. WP_Query arguments for filtering posts.
	 *
	 *     @type string|string[] $post_type      Post type to query.
	 *     @type int             $posts_per_page Number of posts to retrieve.
	 *     @type string|string[] $post_status    Post status to query.
	 *     @type string          $meta_key       Meta key to query.
	 *     @type mixed           $meta_value     Meta value to query.
	 * }
	 * @return TPost[] Array of instantiated model objects matching the query.
	 */
	public function query( $args = [] ) {
		$query_args = wp_parse_args( $args, [
			'post_type'      => $this->post_type,
			'posts_per_page' => - 1,
		] );

		$query_results = new \WP_Query( $query_args );

		/** @var TPost[] $items */
		$items = [];

		foreach ( $query_results->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$item = $this->instantiate_model_from_post( $post );

			if ( ! $item ) {
				continue;
			}

			// Cache the item.
			$this->cache_item( $item );

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Get item by ID.
	 *
	 * @param int|numeric-string $item_id Item ID to retrieve.
	 * @return TPost|null Model instance if found, null otherwise.
	 */
	public function get_by_id( $item_id = 0 ) {
		// Convert to integer for consistent handling.
		$item_id = (int) $item_id;

		// If item is cached, get the object.
		if ( isset( $this->items_by_id[ $item_id ] ) ) {
			return $this->items_by_id[ $item_id ];
		}

		// Query for a post by ID.
		if ( $item_id > 0 ) {
			$post = get_post( $item_id );

			if ( $post && $post->post_type === $this->post_type ) {
				$item = $this->instantiate_model_from_post( $post );

				if ( $item ) {
					$this->cache_item( $item );
				}

				return $item;
			}
		}

		return null;
	}

	/**
	 * Get item by custom field or column.
	 *
	 * @param non-empty-string $field Field name (post column like 'post_name' or meta key).
	 * @param string|int|float $value Field value to search for.
	 * @param 'column'|'meta'  $type Search type: 'column' for post table columns or 'meta' for post meta fields.
	 * @return TPost|null Model instance if found, null otherwise.
	 */
	public function get_by_field( $field, $value, $type = 'column' ) {
		if ( empty( $field ) || ( empty( $value ) && 0 !== $value && '0' !== $value ) ) {
			return null;
		}

		$query_args = [
			'post_type'      => $this->post_type,
			'posts_per_page' => 1,
			'post_status'    => [ 'publish', 'private', 'draft' ],
		];

		if ( 'meta' === $type ) {
			$query_args['meta_key']   = $field; // phpcs:ignore WordPress.DB.SlowDBQuery
			$query_args['meta_value'] = $value; // phpcs:ignore WordPress.DB.SlowDBQuery
		} else {
			// For post columns like post_name, post_title, etc.
			$query_args[ $field ] = $value;
		}

		$query = new \WP_Query( $query_args );

		if ( $query->have_posts() ) {
			$post = $query->posts[0];
			if ( $post instanceof \WP_Post ) {
				$item = $this->instantiate_model_from_post( $post );
				if ( $item ) {
					$this->cache_item( $item );
				}
				return $item;
			}
		}

		return null;
	}
}
