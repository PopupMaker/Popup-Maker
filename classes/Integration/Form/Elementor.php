<?php
/**
 * Integration for Elementor Pro Forms
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_Form_Elementor extends PUM_Abstract_Integration_Form {

	/**
	 * Unique key identifier for this provider.
	 *
	 * @var string
	 */
	public $key = 'elementor';

	/**
	 * Only used to hook in a custom action for non AJAX based submissions.
	 *
	 * Could be used for other initiations as well where needed.
	 */
	public function __construct() {
		add_action( 'elementor_pro/forms/new_record', [ $this, 'on_success' ], 10, 2 );
	}

	/**
	 * Text label that will be used throughout the various options screens.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Elementor Pro', 'popup-maker' );
	}

	/**
	 * Should return true when the required form plugin is active.
	 *
	 * @return bool
	 */
	public function enabled() {
		return did_action( 'elementor_pro/init' ) && class_exists( '\\ElementorPro\\Modules\\Forms\\Module' );
	}

	/**
	 * Get all Elementor forms from the database.
	 * Queries Elementor's submission table for unique form names.
	 *
	 * @param bool $force_refresh Whether to force refresh the cache.
	 *
	 * @return array
	 */
	public function get_forms( $force_refresh = false ) {
		$cache_key   = 'pum_elementor_forms';
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

		// Use Elementor's Query class to get table name.
		if ( ! class_exists( '\\ElementorPro\\Modules\\Forms\\Submissions\\Database\\Query' ) ) {
			return [];
		}

		global $wpdb;

		$query      = \ElementorPro\Modules\Forms\Submissions\Database\Query::get_instance();
		$table_name = $query->get_table_submissions();

		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT DISTINCT form_name, element_id, post_id
			FROM {$table_name}
			WHERE form_name IS NOT NULL AND form_name != ''
			ORDER BY form_name ASC"
		);

		$forms = [];

		foreach ( $results as $result ) {
			$element_id = $result->element_id;
			$form_name  = $result->form_name;

			// Get post title if available.
			$post_title = '';
			if ( ! empty( $result->post_id ) ) {
				$post = get_post( $result->post_id );
				if ( $post ) {
					$post_title = $post->post_title;
				}
			}

			$forms[ $element_id ] = [
				'id'         => $element_id,
				'name'       => $form_name,
				'element_id' => $element_id,
				'post_id'    => $result->post_id,
				'post_title' => $post_title,
			];
		}

		// Cache the results for 1 hour.
		wp_cache_set( $cache_key, $forms, $cache_group, HOUR_IN_SECONDS );
		set_transient( $cache_key, $forms, HOUR_IN_SECONDS );

		return $forms;
	}

	/**
	 * Return a single form by ID.
	 *
	 * @param string $id Form name/ID.
	 *
	 * @return mixed
	 */
	public function get_form( $id ) {
		$forms = $this->get_forms();
		return isset( $forms[ $id ] ) ? $forms[ $id ] : null;
	}

	/**
	 * Returns an array of options for a select list.
	 *
	 * Should be in the format of $formId => $formLabel
	 *
	 * @return array
	 */
	public function get_form_selectlist() {
		$forms           = $this->get_forms();
		$form_selectlist = [ 'any' => __( 'Any Elementor Form', 'popup-maker' ) ];

		foreach ( $forms as $form ) {
			$label                        = $form['name'];
			$form_selectlist[ $form['id'] ] = sprintf(
				'%s (in %s)',
				$label,
				$form['post_title']
			);
		}

		return $form_selectlist;
	}

	/**
	 * Hooks in a success functions specific to this provider for non AJAX submission handling.
	 *
	 * @param object $record
	 * @param object $ajax_handler
	 */
	public function on_success( $record, $ajax_handler ) {
		if ( ! $this->should_process_submission() ) {
			return;
		}

		$form_name = $record->get_form_settings( 'form_name' );
		$popup_id  = $this->get_popup_id();

		$this->increase_conversion( $popup_id );

		pum_integrated_form_submission(
			[
				'popup_id'      => $popup_id,
				'form_provider' => $this->key,
				'form_id'       => $form_name ? $form_name : 'unknown',
			]
		);
	}

	/**
	 * Load a custom script file to handle AJAX based submissions or other integrations with Popup Maker frontend.
	 *
	 * @param array $js
	 *
	 * @return array
	 */
	public function custom_scripts( $js = [] ) {
		return $js;
	}

	/**
	 * Load custom styles for hacking some elements specifically inside popups, such as datepickers.
	 *
	 * @param array $css
	 *
	 * @return array
	 */
	public function custom_styles( $css = [] ) {
		return $css;
	}
}
