<?php
/**
 * CTA registry service.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

namespace PopupMaker\Services\Repository;

use PopupMaker\Models\CallToAction;
use PopupMaker\Base\Service\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Call To Action service.
 *
 * @since X.X.X
 * @template-extends Repository<\PopupMaker\Models\CallToAction>
 */
class CallToActions extends Repository {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	protected $post_type_key = 'pum_cta';

	/**
	 * Items by UUID.
	 *
	 * @var array<string,\PopupMaker\Models\CallToAction>
	 */
	protected $items_by_uuid = [];

	/**
	 * Initialize the service.
	 *
	 * @param \PopupMaker\Plugin\Core $container Container.
	 */
	public function __construct( $container ) {
		parent::__construct( $container );
		// Fire action to dependent services to initialize.
		do_action( 'popup_maker/services/repository/ctas/init', $this );
	}

	/**
	 * Instantiate model from post.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return \PopupMaker\Models\CallToAction|null
	 */
	public function instantiate_model_from_post( $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		return new CallToAction( $post );
	}

	/**
	 * Cache an item internally.
	 *
	 * @param \PopupMaker\Models\CallToAction $item Item to cache.
	 *
	 * @return void
	 */
	protected function cache_item( $item ) {
		parent::cache_item( $item );
		$this->items_by_uuid[ $item->get_uuid() ] = $item;
	}

	/**
	 * Get call to action, by UUID.
	 *
	 * @param string $uuid Call to action UUID.
	 *
	 * @return \PopupMaker\Models\CallToAction|null
	 */
	public function get_by_uuid( $uuid = '' ) {
		if ( isset( $this->items_by_uuid[ $uuid ] ) ) {
			return $this->items_by_uuid[ $uuid ];
		}

		$items = $this->query( [
			'meta_key'       => 'cta_uuid', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $uuid, // phpcs:ignore WordPress.DB.SlowDBQuery
			'posts_per_page' => 1,
		] );

		return ! empty( $items ) ? $items[0] : null;
	}

	/**
	 * Generate select list query.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array
	 */
	public function generate_selectlist_query( $args = [] ) {
		$items = $this->query( $args );

		$options = [];
		foreach ( $items as $item ) {
			$options[ $item->ID ] = $item->title;
		}

		return $options;
	}
}
