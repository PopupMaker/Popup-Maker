<?php
/**
 * Post duplication plugin compatibility controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Controllers\Compatibility\Plugin;

use PopupMaker\Plugin\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Prevent popup analytics and identity metadata from being copied to clones.
 *
 * @since 1.23.1
 */
class PostDuplication extends Controller {

	/**
	 * Popup IDs that need a final metadata cleanup at shutdown.
	 *
	 * Some duplication plugins copy metadata with direct SQL, bypassing the
	 * WordPress metadata filters. The shutdown pass covers those plugins after
	 * their copy operation has completed.
	 *
	 * @var array<int, bool>
	 */
	private $pending_cleanup = [];

	/**
	 * Initialize compatibility hooks.
	 *
	 * @return void
	 */
	public function init() {
		// Yoast Duplicate Post.
		add_filter( 'duplicate_post_excludelist_filter', [ $this, 'add_yoast_excluded_meta_keys' ] );
		add_filter( 'duplicate_post_meta_keys_filter', [ $this, 'remove_excluded_meta_keys' ] );

		// Post Duplicator.
		add_filter( 'mtphr_post_duplicator_excluded_meta_keys', [ $this, 'add_excluded_meta_keys' ] );

		// WP Duplicate Page.
		add_filter( 'wp_duplicate_page_exclude_meta_key', [ $this, 'add_excluded_meta_keys' ] );

		// Orbit Fox post duplicator.
		add_filter( 'obfx_post_duplicator_skip_meta_keys', [ $this, 'add_excluded_meta_keys' ] );

		// Older Post Duplicator releases expose only per-key filters.
		foreach ( $this->get_excluded_meta_keys() as $meta_key ) {
			add_filter( "mtphr_post_duplicator_meta_{$meta_key}_enabled", '__return_false' );
		}

		// Cover plugins without an exclusion API when they use WordPress metadata functions.
		add_filter( 'add_post_metadata', [ $this, 'prevent_excluded_meta_copy' ], 10, 5 );
		add_filter( 'update_post_metadata', [ $this, 'prevent_excluded_meta_copy' ], 10, 5 );

		// Cover plugins that copy metadata directly with SQL.
		add_action( 'wp_after_insert_post', [ $this, 'queue_duplicated_popup_cleanup' ], 10, 4 );
		add_action( 'shutdown', [ $this, 'cleanup_duplicated_popup_meta' ], PHP_INT_MAX );
	}

	/**
	 * Get popup metadata that must remain unique to each popup.
	 *
	 * @return string[]
	 */
	public function get_excluded_meta_keys() {
		$meta_keys = [
			// Core analytics.
			'popup_open_count',
			'popup_open_count_total',
			'popup_last_opened',
			'popup_conversion_count',
			'popup_conversion_count_total',
			'popup_last_conversion',
			'popup_conversion_rate',
			'popup_count_reset',
			'popup_open_count_reset',
			'_pum_form_conversion_count',
			'_pum_link_click_count',

			// Legacy and Pro analytics.
			'popup_analytic_opened_count',
			'popup_analytic_closed_count',
			'popup_analytic_conversion_count',
			'popup_analytic_conversion_rate',
			'popup_analytic_last_opened',
			'popup_analytic_avg_time_open',
			'popup_analytic_avg_conversion_time',
			'popup_analytic_total_conversion_time',
			'popup_analytics_total_opened_count',
			'popup_analytics_total_conversion_count',
			'popup_views',
			'popup_total_views',
			'popup_avg_time_viewed',
			'popup_last_viewed',
			'popup_interactions',
			'popup_total_interactions',
			'popup_avg_interaction_time',
			'popup_interaction_rate',
			'popup_last_interaction',
			'popup_conversions',
			'popup_total_conversions',
			'popup_avg_conversion_time',
			'popup_purchases',
			'popup_total_purchases',
			'popup_purchase_rate',
			'popup_avg_purchase_time',
			'popup_last_purchase',
			'popup_revenue',
			'popup_total_revenue',
			'popup_avg_purchase_value',
			'popup_revenue_per_view',
			'popup_before_event_tracking',

			// Per-popup relationships and origin markers.
			'_pum_split_test_source',
			'_pum_split_test_variant_clone',
			'_pum_demo_popup',
			'_pum_demo_popup_type',
			'pum_example_popup',
			'popup_old_easy_modal_id',
		];

		/**
		 * Filters metadata excluded when a third-party plugin duplicates a popup.
		 *
		 * @param string[] $meta_keys Metadata keys that must not be copied.
		 */
		$filtered_meta_keys = apply_filters( 'popup_maker/post_duplication_excluded_meta_keys', $meta_keys );

		if ( ! is_array( $filtered_meta_keys ) ) {
			return $meta_keys;
		}

		$filtered_meta_keys = array_filter(
			$filtered_meta_keys,
			function ( $meta_key ) {
				return is_string( $meta_key ) && '' !== $meta_key;
			}
		);

		return array_values( array_unique( $filtered_meta_keys ) );
	}

	/**
	 * Add Popup Maker metadata to a plugin's exclusion list.
	 *
	 * @param mixed $meta_keys Third-party plugin exclusion list.
	 *
	 * @return mixed
	 */
	public function add_excluded_meta_keys( $meta_keys ) {
		if ( ! is_array( $meta_keys ) ) {
			return $meta_keys;
		}

		return array_values( array_unique( array_merge( $meta_keys, $this->get_excluded_meta_keys() ) ) );
	}

	/**
	 * Add exclusions supported by Yoast Duplicate Post, including wildcards.
	 *
	 * @param mixed $meta_keys Yoast exclusion list.
	 *
	 * @return mixed
	 */
	public function add_yoast_excluded_meta_keys( $meta_keys ) {
		$meta_keys = $this->add_excluded_meta_keys( $meta_keys );

		if ( ! is_array( $meta_keys ) ) {
			return $meta_keys;
		}

		return array_values(
			array_unique(
				array_merge(
					$meta_keys,
					[
						'popup_analytic_*',
						'popup_analytics_*',
						'_pum_split_test_*',
					]
				)
			)
		);
	}

	/**
	 * Remove Popup Maker metadata from a plugin's copy list.
	 *
	 * @param mixed $meta_keys Third-party plugin metadata copy list.
	 *
	 * @return mixed
	 */
	public function remove_excluded_meta_keys( $meta_keys ) {
		if ( ! is_array( $meta_keys ) ) {
			return $meta_keys;
		}

		$remaining_meta_keys = [];

		foreach ( $meta_keys as $meta_key ) {
			if ( ! is_string( $meta_key ) || ! $this->is_excluded_meta_key( $meta_key ) ) {
				$remaining_meta_keys[] = $meta_key;
			}
		}

		return $remaining_meta_keys;
	}

	/**
	 * Short-circuit excluded metadata writes during known duplication requests.
	 *
	 * @param mixed $check      Existing short-circuit value.
	 * @param mixed $object_id  Destination post ID.
	 * @param mixed $meta_key   Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @param mixed $last_value Unique flag or previous metadata value.
	 *
	 * @return mixed
	 */
	public function prevent_excluded_meta_copy( $check, $object_id, $meta_key, $meta_value = null, $last_value = null ) {
		if ( null !== $check || ! $this->is_post_duplication_request() ) {
			return $check;
		}

		if ( ! is_numeric( $object_id ) || ! is_string( $meta_key ) ) {
			return $check;
		}

		if ( 'popup' !== get_post_type( (int) $object_id ) || ! $this->is_excluded_meta_key( $meta_key ) ) {
			return $check;
		}

		return true;
	}

	/**
	 * Queue a new popup created by a known duplication request for cleanup.
	 *
	 * @param mixed $post_id     Inserted post ID.
	 * @param mixed $post        Inserted post object.
	 * @param mixed $update      Whether WordPress updated an existing post.
	 * @param mixed $post_before Post object before the update.
	 *
	 * @return void
	 */
	public function queue_duplicated_popup_cleanup( $post_id, $post, $update, $post_before = null ) {
		if ( $update || ! is_numeric( $post_id ) || ! is_object( $post ) ) {
			return;
		}

		if ( 'popup' !== ( $post->post_type ?? '' ) || ! $this->is_post_duplication_request() ) {
			return;
		}

		$this->pending_cleanup[ (int) $post_id ] = true;
	}

	/**
	 * Remove excluded metadata after third-party duplication has completed.
	 *
	 * @return void
	 */
	public function cleanup_duplicated_popup_meta() {
		foreach ( array_keys( $this->pending_cleanup ) as $post_id ) {
			unset( $this->pending_cleanup[ $post_id ] );

			if ( 'popup' !== get_post_type( $post_id ) ) {
				continue;
			}

			// Direct SQL copies do not invalidate the post metadata cache.
			wp_cache_delete( $post_id, 'post_meta' );

			$stored_meta_keys = get_post_custom_keys( $post_id );
			$stored_meta_keys = is_array( $stored_meta_keys ) ? $stored_meta_keys : [];
			$removed_keys     = [];

			foreach ( array_unique( array_merge( $this->get_excluded_meta_keys(), $stored_meta_keys ) ) as $meta_key ) {
				if ( ! is_string( $meta_key ) || ! $this->is_excluded_meta_key( $meta_key ) ) {
					continue;
				}

				if ( metadata_exists( 'post', $post_id, $meta_key ) ) {
					delete_post_meta( $post_id, $meta_key );
					$removed_keys[] = $meta_key;
				}
			}

			if ( ! empty( $removed_keys ) ) {
				/**
				 * Fires after copied Popup Maker metadata is removed from a clone.
				 *
				 * @param int      $post_id      Duplicated popup ID.
				 * @param string[] $removed_keys Removed metadata keys.
				 */
				do_action( 'popup_maker/post_duplication_meta_cleaned', $post_id, $removed_keys );
			}
		}
	}

	/**
	 * Check whether a metadata key belongs only to one popup instance.
	 *
	 * @param string $meta_key Metadata key.
	 *
	 * @return bool
	 */
	private function is_excluded_meta_key( $meta_key ) {
		$is_excluded = in_array( $meta_key, $this->get_excluded_meta_keys(), true );

		if ( ! $is_excluded ) {
			$is_excluded = 0 === strpos( $meta_key, 'popup_analytic_' )
				|| 0 === strpos( $meta_key, 'popup_analytics_' )
				|| 0 === strpos( $meta_key, '_pum_split_test_' )
				|| (bool) preg_match( '/^popup_[a-z0-9_]+_count(?:_total)?$/', $meta_key )
				|| (bool) preg_match( '/^popup_last_[a-z0-9_]+$/', $meta_key );
		}

		/**
		 * Filters whether a popup metadata key is unique and must not be copied.
		 *
		 * @param bool   $is_excluded Whether the key must not be copied.
		 * @param string $meta_key    Metadata key being checked.
		 */
		return (bool) apply_filters( 'popup_maker/is_post_duplication_excluded_meta_key', $is_excluded, $meta_key );
	}

	/**
	 * Check whether the current request is from a supported duplication plugin.
	 *
	 * @return bool
	 */
	private function is_post_duplication_request() {
		$actions = [
			// Yoast Duplicate Post.
			'duplicate_post_clone',
			'duplicate_post_new_draft',
			'duplicate_post_rewrite',
			'duplicate_post_bulk_clone',
			'duplicate_post_bulk_rewrite_republish',

			// Duplicate Page and WP Duplicate Page.
			'dt_duplicate_post_as_draft',
			'njt_duplicate_page_save_as_new_post',
			'wp_duplicate_page_bulk_action',

			// Copy & Delete Posts and Post Duplicator-style plugins.
			'cdp_action_handling',
			'wp_post_page_clone',
			'dt_dpp_post_as_draft',
			'wpdevart_duplicate_post_page',
			'content_clone',
			'clone-single',
			'clone',
			'ig_duplicate_post',

			// Orbit Fox and Admin and Site Enhancements.
			'duplicate_post',
			'duplicate_content',
		];

		/**
		 * Filters request action names used by supported post duplication plugins.
		 *
		 * @param string[] $actions Duplication request action names.
		 */
		$actions = apply_filters( 'popup_maker/post_duplication_request_actions', $actions );

		if ( ! is_array( $actions ) ) {
			return false;
		}

		foreach ( [ 'action', 'action2' ] as $request_key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request routing check.
			if ( ! isset( $_REQUEST[ $request_key ] ) || ! is_scalar( $_REQUEST[ $request_key ] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request routing check.
			$action = sanitize_key( wp_unslash( (string) $_REQUEST[ $request_key ] ) );

			if ( in_array( $action, $actions, true ) ) {
				return true;
			}
		}

		return false;
	}
}
