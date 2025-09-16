<?php
/**
 * CTA registry service.
 *
 * @author    Code Atlantic
 * @package   PopupMaker
 * @copyright (c) 2024, Code Atlantic LLC.
 */

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found

namespace PopupMaker\Services\Repository;

use PUM_Model_Popup as Popup;
use PopupMaker\Base\Service\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Popups service.
 *
 * @since 1.21.0
 * @template-extends Repository<Popup>
 */
class Popups extends Repository {

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	protected $post_type_key = 'popup';

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
	 * @return Popup|null
	 */
	public function instantiate_model_from_post( $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		return new Popup( $post );
	}

	/**
	 * Cache an item internally.
	 *
	 * @param Popup $item Item to cache.
	 *
	 * @return void
	 */
	protected function cache_item( $item ) {
		parent::cache_item( $item );
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
			$options[ $item->id ?? $item->ID ] = $item->title;
		}

		return $options;
	}
}
