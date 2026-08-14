<?php
/**
 * Atomic analytics counter storage.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 */

namespace PopupMaker\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Efficiently increment option- and post-meta-backed counters.
 */
class AnalyticsCounter {

	/**
	 * Maximum number of one-second waits for a contended initialization lock.
	 */
	private const LOCK_ATTEMPTS = 3;

	/**
	 * Increment a non-autoloaded option counter.
	 *
	 * @param string $key Option key.
	 * @return int|\WP_Error Updated count or an explicit failure.
	 */
	public static function increment_option( $key ) {
		global $wpdb;

		$rows_affected = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i (option_name, option_value, autoload) VALUES (%s, 1, 'off') ON DUPLICATE KEY UPDATE option_value = option_value + 1, autoload = 'off'",
				$wpdb->options,
				$key
			)
		);

		if ( false === $rows_affected ) {
			return self::database_error( $key );
		}

		wp_cache_delete( $key, 'options' );

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( is_array( $alloptions ) && isset( $alloptions[ $key ] ) ) {
			wp_cache_delete( 'alloptions', 'options' );
		}

		$notoptions = wp_cache_get( 'notoptions', 'options' );
		if ( 1 === $rows_affected || ( is_array( $notoptions ) && isset( $notoptions[ $key ] ) ) ) {
			wp_cache_delete( 'notoptions', 'options' );
		}

		return (int) get_option( $key, 0 );
	}

	/**
	 * Increment a post-meta counter.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @return int|\WP_Error Updated count or an explicit retryable failure.
	 */
	public static function increment_post_meta( $post_id, $key ) {
		global $wpdb;

		$updated = self::update_post_meta_counter( $post_id, $key );

		if ( false === $updated ) {
			return self::database_error( $key, $post_id );
		}

		if ( 0 === $updated ) {
			$initialized = self::initialize_post_meta( $post_id, $key );

			if ( is_wp_error( $initialized ) ) {
				wp_cache_delete( $post_id, 'post_meta' );
				return $initialized;
			}
		}

		wp_cache_delete( $post_id, 'post_meta' );

		return (int) get_post_meta( $post_id, $key, true );
	}

	/**
	 * Surface a counter failure without breaking the tracking request.
	 *
	 * @param \WP_Error $error Counter failure.
	 * @param int       $post_id Popup post ID.
	 * @param array     $context Tracking context.
	 * @return void
	 */
	public static function report_failure( $error, $post_id, $context ) {
		/**
		 * Fires when an analytics counter could not be stored. Consumers can
		 * inspect the error data to decide whether and what to retry; a sibling
		 * counter that was already stored is not rolled back.
		 *
		 * @param \WP_Error $error Counter failure.
		 * @param int       $post_id Popup post ID.
		 * @param array     $context Tracking context.
		 */
		do_action( 'popup_maker/analytics_counter_failed', $error, $post_id, $context );
	}

	/**
	 * Initialize a missing post-meta counter without racing another request.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @return true|\WP_Error True when stored or an explicit failure.
	 */
	private static function initialize_post_meta( $post_id, $key ) {
		global $wpdb;

		$database  = defined( 'DB_NAME' ) ? DB_NAME : '';
		$lock_name = 'pum_counter_' . md5( $database . ':' . $wpdb->postmeta . ':' . $key . ':' . $post_id );

		for ( $attempt = 0; $attempt < self::LOCK_ATTEMPTS; $attempt++ ) {
			// FOR UPDATE keeps named locks on the writer connection for split-read database drop-ins.
			$lock_result = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 1) FOR UPDATE', $lock_name ) );

			if ( 1 === (int) $lock_result ) {
				try {
					$updated = self::update_post_meta_counter( $post_id, $key );

					if ( false === $updated ) {
						return self::database_error( $key, $post_id );
					}

					if ( 0 === $updated ) {
						$added = add_post_meta( $post_id, $key, 1, true );

						if ( false === $added ) {
							$updated = self::update_post_meta_counter( $post_id, $key );

							if ( false === $updated || 0 === $updated ) {
								return self::database_error( $key, $post_id );
							}
						}
					}
				} finally {
					$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s) FOR UPDATE', $lock_name ) );
				}

				return true;
			}

			if ( null === $lock_result ) {
				return self::initialize_post_meta_without_lock( $post_id, $key );
			}

			// The lock owner may have initialized the row while this request waited.
			$updated = self::update_post_meta_counter( $post_id, $key );

			if ( false === $updated ) {
				return self::database_error( $key, $post_id );
			}

			if ( 0 < $updated ) {
				return true;
			}
		}

		return new \WP_Error(
			'pum_analytics_counter_lock_timeout',
			__( 'The analytics counter is busy. Retry this event.', 'popup-maker' ),
			[
				'post_id'     => (int) $post_id,
				'counter_key' => (string) $key,
				'retryable'   => true,
			]
		);
	}

	/**
	 * Preserve baseline behavior when advisory locks are unavailable.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @return true|\WP_Error True when stored or an explicit failure.
	 */
	private static function initialize_post_meta_without_lock( $post_id, $key ) {
		if ( ! get_post_meta( $post_id, $key, true ) ) {
			$added = add_post_meta( $post_id, $key, 1, true );

			if ( false !== $added ) {
				return true;
			}
		}

		$updated = self::update_post_meta_counter( $post_id, $key );

		if ( false === $updated || 0 === $updated ) {
			return self::database_error( $key, $post_id );
		}

		return true;
	}

	/**
	 * Increment an existing post-meta counter row.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @return int|false Number of affected rows or false on error.
	 */
	private static function update_post_meta_counter( $post_id, $key ) {
		global $wpdb;

		return $wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = %s',
				$wpdb->postmeta,
				$post_id,
				$key
			)
		);
	}

	/**
	 * Build a non-retryable database failure for a counter write.
	 *
	 * @param string   $key Counter key.
	 * @param int|null $post_id Optional post ID.
	 * @return \WP_Error Counter database error.
	 */
	private static function database_error( $key, $post_id = null ) {
		$data = [
			'counter_key' => (string) $key,
			'retryable'   => false,
		];

		if ( null !== $post_id ) {
			$data['post_id'] = (int) $post_id;
		}

		return new \WP_Error(
			'pum_analytics_counter_database_error',
			__( 'The analytics counter could not be updated.', 'popup-maker' ),
			$data
		);
	}
}
