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
	 * Get popup titles keyed by popup ID.
	 *
	 * @param array<int,int|string> $ids Popup IDs.
	 *
	 * @return array<int,string> Popup ID => title mapping.
	 */
	public function get_title_choices( $ids ) {
		global $wpdb;

		if ( ! is_array( $ids ) ) {
			return [];
		}

		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		if ( empty( $ids ) ) {
			return [];
		}

		$last_changed = wp_cache_get_last_changed( 'posts' );
		$cache_key    = 'pum_popup_title_choices:' . md5( implode( ',', $ids ) ) . ':' . $last_changed;

		$cached_titles = wp_cache_get( $cache_key, 'popup-maker' );

		if ( is_array( $cached_titles ) ) {
			return $this->filter_title_choices( $cached_titles );
		}

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$query        = "SELECT ID, post_title FROM %i WHERE post_type = %s AND ID IN ($placeholders)";
		$query_args   = array_merge( [ $wpdb->posts, $this->post_type ], $ids );

		// Fetch only the ID and title fields needed by popup title consumers.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $query, $query_args ), ARRAY_A );
		$rows = is_array( $rows ) ? $rows : [];

		$title_choices = [];

		foreach ( $rows as $row ) {
			$popup_id = isset( $row['ID'] ) ? absint( $row['ID'] ) : 0;

			if ( $popup_id > 0 ) {
				$title_choices[ $popup_id ] = isset( $row['post_title'] ) ? (string) $row['post_title'] : '';
			}
		}

		wp_cache_set( $cache_key, $title_choices, 'popup-maker' );

		return $this->filter_title_choices( $title_choices );
	}

	/**
	 * Allow per-request filtering of a raw ID => title map.
	 *
	 * Titles stay raw because WordPress title formatting entity-encodes plain-text
	 * select labels. Multilingual plugins and extensions can remap the uncached
	 * result through this dedicated filter.
	 *
	 * @param array<int,string> $title_choices Raw titles keyed by popup ID.
	 *
	 * @return array<int,string>
	 */
	protected function filter_title_choices( $title_choices ) {
		$filtered = apply_filters( 'popup_maker/popup_title_choices', $title_choices );

		return is_array( $filtered ) ? $filtered : $title_choices;
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
