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
	 * Partition cached popup models by site.
	 *
	 * @param int|numeric-string $item_id Popup ID.
	 *
	 * @return string
	 */
	protected function get_item_cache_key( $item_id ) {
		return get_current_blog_id() . ':' . (int) $item_id;
	}

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
	 * Replace the cached popup model for the current site.
	 *
	 * This public operation intentionally delegates to the protected cache hook
	 * so extension subclasses can retain protected cache_item() overrides.
	 *
	 * @param Popup $item Popup model to cache.
	 *
	 * @return void
	 */
	public function replace_cached_item( $item ) {
		$this->cache_item( $item );
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
	 * Get aggregate analytics for the WordPress Dashboard widget.
	 *
	 * @return array{
	 *     total_views: int,
	 *     total_conversions: int,
	 *     conversion_rate: float,
	 *     top_performer: \WP_Post|null,
	 *     top_performer_rate: float
	 * }
	 */
	public function get_dashboard_stats() {
		global $wpdb;

		$query = new \WP_Query(
			[
				'post_type'              => $this->post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// Restrict the ID-only query before filling the shared meta cache.
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'             => [
					'relation' => 'AND',
					[
						'key'     => 'enabled',
						'value'   => 1,
						'compare' => '=',
					],
					[
						'key'     => 'popup_open_count',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					],
				],
			]
		);

		$popup_ids = array_map( 'absint', $query->posts );

		if ( empty( $popup_ids ) ) {
			return [
				'total_views'        => 0,
				'total_conversions'  => 0,
				'conversion_rate'    => 0.0,
				'top_performer'      => null,
				'top_performer_rate' => 0.0,
			];
		}

		$meta_keys        = [ 'popup_open_count', 'popup_conversion_count', 'popup_conversion_rate' ];
		$id_placeholders  = implode( ', ', array_fill( 0, count( $popup_ids ), '%d' ) );
		$key_placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
		$meta_query       = "SELECT post_id, meta_key, meta_value FROM %i WHERE post_id IN ($id_placeholders) AND meta_key IN ($key_placeholders) ORDER BY meta_id ASC";
		$meta_query_args  = array_merge( [ $wpdb->postmeta ], $popup_ids, $meta_keys );

		// Read only the counters used by the dashboard instead of priming all popup metadata.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$meta_rows = $wpdb->get_results( $wpdb->prepare( $meta_query, $meta_query_args ), ARRAY_A );
		$meta_rows = is_array( $meta_rows ) ? $meta_rows : [];
		$meta_map  = [];

		foreach ( $meta_rows as $meta_row ) {
			$popup_id = isset( $meta_row['post_id'] ) ? absint( $meta_row['post_id'] ) : 0;
			$meta_key = isset( $meta_row['meta_key'] ) ? (string) $meta_row['meta_key'] : '';

			// Match get_post_meta( ..., true ) by retaining the first stored value.
			if ( 0 === $popup_id || ! in_array( $meta_key, $meta_keys, true ) || isset( $meta_map[ $popup_id ][ $meta_key ] ) ) {
				continue;
			}

			$meta_map[ $popup_id ][ $meta_key ] = isset( $meta_row['meta_value'] ) ? $meta_row['meta_value'] : '';
		}

		$total_views       = 0;
		$total_conversions = 0;
		$top_id            = 0;
		$top_stored_rate   = 0.0;
		$top_conversions   = 0;
		$top_views         = 0;

		foreach ( $popup_ids as $popup_id ) {
			$popup_meta  = isset( $meta_map[ $popup_id ] ) ? $meta_map[ $popup_id ] : [];
			$views       = (int) $this->get_dashboard_meta_value( $popup_id, 'popup_open_count', $popup_meta );
			$conversions = (int) $this->get_dashboard_meta_value( $popup_id, 'popup_conversion_count', $popup_meta );
			$stored_rate = (float) $this->get_dashboard_meta_value( $popup_id, 'popup_conversion_rate', $popup_meta );

			$total_views       += $views;
			$total_conversions += $conversions;

			if (
				0 === $top_id
				|| $stored_rate > $top_stored_rate
				|| ( $stored_rate === $top_stored_rate && $conversions > $top_conversions )
				|| ( $stored_rate === $top_stored_rate && $conversions === $top_conversions && $views > $top_views )
			) {
				$top_id          = $popup_id;
				$top_stored_rate = $stored_rate;
				$top_conversions = $conversions;
				$top_views       = $views;
			}
		}

		return [
			'total_views'        => $total_views,
			'total_conversions'  => $total_conversions,
			'conversion_rate'    => $total_views > 0 ? ( $total_conversions / $total_views ) * 100 : 0.0,
			'top_performer'      => get_post( $top_id ),
			'top_performer_rate' => $top_views > 0 ? ( $top_conversions / $top_views ) * 100 : 0.0,
		];
	}

	/**
	 * Apply WordPress metadata filters to a projected Dashboard value.
	 *
	 * @param int                 $popup_id  Popup ID.
	 * @param string              $meta_key  Analytics metadata key.
	 * @param array<string,mixed> $meta_map  Projected raw metadata for the popup.
	 *
	 * @return mixed
	 */
	private function get_dashboard_meta_value( $popup_id, $meta_key, $meta_map ) {
		$filtered_value = apply_filters( 'get_post_metadata', null, $popup_id, $meta_key, true, 'post' );

		if ( null !== $filtered_value ) {
			return is_array( $filtered_value ) ? ( isset( $filtered_value[0] ) ? $filtered_value[0] : null ) : $filtered_value;
		}

		if ( array_key_exists( $meta_key, $meta_map ) ) {
			return maybe_unserialize( $meta_map[ $meta_key ] );
		}

		return apply_filters( 'default_post_metadata', '', $popup_id, $meta_key, true, 'post' );
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
	 * Discard a cached popup model for the current site.
	 *
	 * @param int|numeric-string $item_id Popup ID.
	 *
	 * @return void
	 */
	public function forget_item( $item_id ) {
		unset( $this->items_by_id[ $this->get_item_cache_key( $item_id ) ] );
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
