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
 * Repository service.
 *
 * @since X.X.X
 * @template TPost of Post
 */
abstract class Repository extends Service {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	protected $post_type_key;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type;

	/**
	 * Array of all items by ID.
	 *
	 * @var array<int,TPost>
	 */
	protected $items_by_id = [];

	/**
	 * Initialize the service.
	 *
	 * @param \PopupMaker\Base\Container $container Container.
	 */
	public function __construct( $container ) {
		parent::__construct( $container );
		$this->post_type = $this->container->get( 'PostType' )->get_type_key( $this->post_type_key );
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
	 * Cache an item.
	 *
	 * @param TPost $item Item to cache.
	 *
	 * @return void
	 */
	protected function cache_item( $item ) {
		$this->items_by_id[ $item->id ] = $item;
	}

	/**
	 * Get a list of all queried items.
	 *
	 * @return TPost[]
	 */
	public function query( $args = [] ) {
		$query_args = wp_parse_args( $args, [
			'post_type'      => $this->post_type,
			'posts_per_page' => - 1,
		] );

		$query_results = new \WP_Query( $query_args );

		$items = [];

		foreach ( $query_results->posts as $post ) {
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
	 * @param int $item_id Item ID.
	 *
	 * @return TPost|null
	 */
	public function get_by_id( $item_id = 0 ) {
		// If item is an ID, get the object.
		if ( is_numeric( $item_id ) && isset( $this->items_by_id[ $item_id ] ) ) {
			return $this->items_by_id[ $item_id ];
		}

		// Query for a call to action by post ID.
		if ( is_numeric( $item_id ) ) {
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
}
