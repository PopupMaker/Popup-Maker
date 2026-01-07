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
	 * Elementor stores forms as widgets in post meta, so we need to query for posts with form widgets.
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

		global $wpdb;

		// Query for posts that contain Elementor form widgets.
		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT DISTINCT p.ID, p.post_title, pm.meta_value
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				AND pm.meta_value LIKE %s
				AND p.post_status = 'publish'",
				'_elementor_data',
				'%"widgetType":"form"%'
			)
		);

		$forms = [];

		foreach ( $results as $result ) {
			$elementor_data = json_decode( $result->meta_value, true );
			if ( is_array( $elementor_data ) ) {
				$forms = array_merge( $forms, $this->extract_forms_from_elementor_data( $elementor_data, $result->ID, $result->post_title ) );
			}
		}

		// Cache the results for 1 hour.
		wp_cache_set( $cache_key, $forms, $cache_group, HOUR_IN_SECONDS );
		set_transient( $cache_key, $forms, HOUR_IN_SECONDS );

		return $forms;
	}

	/**
	 * Recursively extract form widgets from Elementor data structure.
	 *
	 * @param array  $elements Elementor elements array.
	 * @param int    $post_id Post ID containing the form.
	 * @param string $post_title Post title for reference.
	 *
	 * @return array
	 */
	private function extract_forms_from_elementor_data( $elements, $post_id, $post_title ) {
		$forms = [];

		foreach ( $elements as $element ) {
			// Check if this is a form widget.
			if ( isset( $element['widgetType'] ) && 'form' === $element['widgetType'] ) {
				$form_name = '';

				// Extract form_name from settings.
				if ( isset( $element['settings']['form_name'] ) ) {
					$form_name = $element['settings']['form_name'];
				}

				// Fallback to element ID if no form_name.
				if ( empty( $form_name ) && isset( $element['id'] ) ) {
					$form_name = 'Form ' . $element['id'];
				}

				if ( ! empty( $form_name ) ) {
					$forms[ $form_name ] = [
						'id'         => $form_name,
						'name'       => $form_name,
						'post_id'    => $post_id,
						'post_title' => $post_title,
					];
				}
			}

			// Recursively check child elements.
			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$forms = array_merge( $forms, $this->extract_forms_from_elementor_data( $element['elements'], $post_id, $post_title ) );
			}
		}

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
