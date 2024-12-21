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
 * @template-extends Repository<CallToAction>
 */
class CallToActions extends Repository {

	/**
	 * Items by UUID.
	 *
	 * @var array<string,CallToAction>
	 */
	protected $items_by_uuid = [];

	/**
	 * Initialize the service.
	 */
	public function __construct() {
		parent::__construct();
		// Fire action to dependent services to initialize.
		do_action( 'popup_maker/services/call_to_actions/init', $this );
	}

	/**
	 * Instantiate model from post.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return CallToAction|null
	 */
	public function instantiate_model_from_post( $post ): ?CallToAction {
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		return new CallToAction( $post );
	}

	protected function cache_item( $item ) {
		parent::cache_item( $item );
		$this->items_by_uuid[ $item->uuid ] = $item;
	}

	/**
	 * Get call to action, by UUID.
	 *
	 * @param string $uuid Call to action UUID.
	 *
	 * @return CallToAction|null
	 */
	public function get_by_uuid( $uuid = '' ): ?CallToAction {
		if ( isset( $this->items_by_uuid[ $uuid ] ) ) {
			return $this->items_by_uuid[ $uuid ];
		}

		$query = new \WP_Query( [
			'post_type'      => $this->post_type,
			'meta_key'       => 'uuid', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $uuid, // phpcs:ignore WordPress.DB.SlowDBQuery
			'posts_per_page' => 1,
		] );

		return $query->have_posts() ? $this->instantiate_model_from_post( $query->posts[0] ) : null;
	}
}
