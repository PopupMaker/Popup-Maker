<?php
/**
 * Integration for Bricks Builder Forms
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_BricksBuilder extends PUM_Abstract_Integration_Form {

	/**
	 * @var string
	 */
	public $key = 'bricksbuilder';

	/**
	 * Constructor - Set up cache invalidation hooks.
	 */
	public function __construct() {
		// Clear cache when Bricks content is updated.
		add_action( 'updated_post_meta', [ $this, 'maybe_clear_cache' ], 10, 4 );
		add_action( 'added_post_meta', [ $this, 'maybe_clear_cache' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'maybe_clear_cache' ], 10, 4 );
		add_action( 'wp_trash_post', [ $this, 'clear_forms_cache' ] );
		add_action( 'untrash_post', [ $this, 'clear_forms_cache' ] );
	}

	/**
	 * @return string
	 */
	public function label() {
		return 'Bricks Builder';
	}

	/**
	 * @return bool
	 */
	public function enabled() {
		return defined( 'BRICKS_VERSION' );
	}

	/**
	 * Get all Bricks Builder forms from the database.
	 * Bricks stores forms as elements in post meta, so we need to query for posts with form elements.
	 *
	 * @param bool $force_refresh Whether to force refresh the cache.
	 *
	 * @return array
	 */
	public function get_forms( $force_refresh = false ) {
		$cache_key   = 'pum_bricks_forms_v2';
		$cache_group = 'popup_maker';

		// Try to get cached forms first.
		if ( ! $force_refresh ) {
			$cached_forms = wp_cache_get( $cache_key, $cache_group );
			if ( false !== $cached_forms ) {
				return $cached_forms;
			}

			// Fallback to transient for persistent caching.
			$cached_forms = get_transient( $cache_key );
			if ( false !== $cached_forms ) {
				// Store in object cache for this request.
				wp_cache_set( $cache_key, $cached_forms, $cache_group, HOUR_IN_SECONDS );
				return $cached_forms;
			}
		}

		global $wpdb;

		// Query for posts that contain Bricks form elements.
		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT DISTINCT p.ID, p.post_title, pm.meta_value
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				AND pm.meta_value LIKE %s
				AND p.post_status = 'publish'",
				'_bricks_page_content_2',
				'%s:4:"name";s:4:"form"%'
			)
		);

		$forms = [];

		foreach ( $results as $result ) {
			$bricks_data = maybe_unserialize( $result->meta_value );
			if ( is_array( $bricks_data ) ) {
				$forms = array_merge( $forms, $this->extract_forms_from_bricks_data( $bricks_data, $result->ID, $result->post_title ) );
			}
		}

		// Cache the results for 1 hour.
		wp_cache_set( $cache_key, $forms, $cache_group, HOUR_IN_SECONDS );
		set_transient( $cache_key, $forms, HOUR_IN_SECONDS );

		return $forms;
	}

	/**
	 * Extract form elements from Bricks data structure.
	 *
	 * @param array  $bricks_data The Bricks data structure.
	 * @param int    $post_id     The post ID.
	 * @param string $post_title  The post title.
	 *
	 * @return array
	 */
	private function extract_forms_from_bricks_data( $bricks_data, $post_id, $post_title ) {
		$forms = [];

		foreach ( $bricks_data as $element ) {
			if ( isset( $element['name'] ) && 'form' === $element['name'] ) {
				$form_title = ! empty( $element['settings']['formTitle'] ) ? $element['settings']['formTitle'] : $post_title;
				$forms[]    = [
					'ID'         => $element['id'],
					'post_title' => $form_title,
					'post_id'    => $post_id,
					'element_id' => $element['id'],
				];
			}

			// Recursively check children.
			if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
				$forms = array_merge( $forms, $this->extract_forms_from_bricks_data( $element['children'], $post_id, $post_title ) );
			}
		}

		return $forms;
	}

	/**
	 * Get a specific form by ID.
	 *
	 * @param int|string $id The form element ID.
	 *
	 * @return array|false
	 */
	public function get_form( $id ) {
		$forms = $this->get_forms();

		foreach ( $forms as $form ) {
			if ( $form['element_id'] === $id ) {
				return $form;
			}
		}

		return false;
	}

	/**
	 * Get a select list of all forms.
	 *
	 * @return array
	 */
	public function get_form_selectlist() {
		$form_selectlist = [];
		$forms           = $this->get_forms();

		foreach ( $forms as $form ) {
			$form_selectlist[ $form['element_id'] ] = $form['post_title'];
		}

		return $form_selectlist;
	}

	/**
	 * Custom scripts for Bricks Builder integration.
	 * Since we're using JavaScript event handling, no additional server-side processing needed.
	 *
	 * @param array $js JavaScript array.
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * Custom styles for Bricks Builder integration.
	 *
	 * @param array $css CSS array.
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}

	/**
	 * Maybe clear forms cache when Bricks content is updated.
	 *
	 * @param int    $meta_id     ID of updated metadata entry.
	 * @param int    $object_id   Post ID.
	 * @param string $meta_key    Metadata key.
	 * @param mixed  $meta_value  Metadata value.
	 */
	public function maybe_clear_cache( $meta_id, $object_id, $meta_key, $meta_value ) {
		// Only clear cache when Bricks content is modified.
		if ( '_bricks_page_content_2' === $meta_key ) {
			$this->clear_forms_cache();
		}
	}

	/**
	 * Clear the forms cache.
	 */
	public function clear_forms_cache() {
		$cache_key   = 'pum_bricks_forms_v2';
		$cache_group = 'popup_maker';

		wp_cache_delete( $cache_key, $cache_group );
		delete_transient( $cache_key );
	}
}
