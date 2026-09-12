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
	 * @var array<int|string, TPost>
	 */
	protected $items_by_id = [];

	/**
	 * Get the cache key for an item ID.
	 *
	 * @param int|numeric-string $item_id Item ID.
	 *
	 * @return int|string
	 */
	protected function get_item_cache_key( $item_id ) {
		return (int) $item_id;
	}

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
	 * Resolve the model to use for a queried post.
	 *
	 * Defaults to fresh instantiation. Repositories that maintain a canonical
	 * request-local model override this so a query reuses the instance callers
	 * already hold instead of replacing it.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return TPost|null
	 */
	protected function resolve_model_from_post( $post ) {
		return $this->instantiate_model_from_post( $post );
	}

	/**
	 * Cache an item in internal storage.
	 *
	 * @param TPost $item Item to cache by ID for fast retrieval.
	 * @return void
	 */
	protected function cache_item( $item ) {
		$this->items_by_id[ $this->get_item_cache_key( $item->ID ) ] = $item;
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
		/** @var TPost[] $items */
		$items = [];

		foreach ( $this->query_posts( $args ) as $post ) {
			$item = $this->resolve_model_from_post( $post );

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
	 * Query repository records as WordPress post objects without model hydration.
	 *
	 * @param array<string,mixed> $args WP_Query arguments.
	 * @return \WP_Post[] Matching post objects.
	 */
	public function query_posts( $args = [] ) {
		$args = is_array( $args ) ? $args : [];
		unset( $args['post_type'], $args['fields'] );

		$query_args = wp_parse_args(
			$args,
			[
				'posts_per_page' => -1,
			]
		);

		$query_args['post_type'] = $this->post_type;
		$query_args['fields']    = 'all';

		$query = new \WP_Query( $query_args );

		return array_values(
			array_filter(
				$query->posts,
				static function ( $post ) {
					return $post instanceof \WP_Post;
				}
			)
		);
	}

	/**
	 * Query repository record IDs without post-object or model hydration.
	 *
	 * @param array<string,mixed> $args WP_Query arguments.
	 * @return int[] Matching post IDs.
	 */
	public function query_ids( $args = [] ) {
		$args = is_array( $args ) ? $args : [];
		unset( $args['post_type'], $args['fields'] );

		$query_args = wp_parse_args(
			$args,
			[
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		$query_args['post_type'] = $this->post_type;
		$query_args['fields']    = 'ids';

		$query = new \WP_Query( $query_args );

		return wp_parse_id_list( $query->posts );
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

		// Return a validated cache hit when one exists.
		$cached = $this->get_cached_item( $item_id );

		if ( null !== $cached ) {
			return $cached;
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
	 * Get an already-cached model without falling back to a query.
	 *
	 * Answers "has this item been hydrated during this request?" rather than
	 * "fetch it". A cached model that fails {@see self::cached_item_is_fresh()}
	 * is evicted and reported as a miss, so no caller can be served stale data
	 * regardless of which request context invalidation hooks were registered in.
	 *
	 * @param int|numeric-string $item_id Item ID.
	 *
	 * @return TPost|null Cached model, or null when not cached or stale.
	 */
	public function get_cached_item( $item_id ) {
		$item_id = (int) $item_id;

		if ( $item_id <= 0 ) {
			return null;
		}

		$cache_key = $this->get_item_cache_key( $item_id );

		if ( ! isset( $this->items_by_id[ $cache_key ] ) ) {
			return null;
		}

		$cached = $this->items_by_id[ $cache_key ];

		if ( $this->cached_item_is_fresh( $cached, $item_id ) ) {
			return $cached;
		}

		$this->forget_item( $item_id );

		return null;
	}

	/**
	 * Determine whether a cached model still matches its stored post.
	 *
	 * The base implementation trusts the cache. Repositories that must survive
	 * direct writes, or requests where invalidation hooks are not registered,
	 * override this to compare against the current post.
	 *
	 * @param TPost              $item    Cached model.
	 * @param int|numeric-string $item_id Item ID.
	 *
	 * @return bool
	 */
	protected function cached_item_is_fresh( $item, $item_id ) {
		return true;
	}

	/**
	 * Discard a cached model.
	 *
	 * @param int|numeric-string $item_id Item ID.
	 *
	 * @return void
	 */
	public function forget_item( $item_id ) {
		unset( $this->items_by_id[ $this->get_item_cache_key( $item_id ) ] );
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
				// Resolve through the same path as query() so a lookup by slug or
				// meta reuses the model callers already hold instead of replacing
				// it with a second instance.
				$item = $this->resolve_model_from_post( $post );
				if ( $item ) {
					$this->cache_item( $item );
				}
				return $item;
			}
		}

		return null;
	}
}
