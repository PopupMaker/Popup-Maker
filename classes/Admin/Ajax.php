<?php
/**
 * Class for Admin Ajax
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handles some of our AJAX requests including post/taxonomy search from conditions
 */
class PUM_Admin_Ajax {

	/**
	 * Hooks our methods into AJAX actions.
	 * Hooks our methods into AJAX actions.
	 */
	public static function init() {
		add_action( 'wp_ajax_pum_object_search', [ __CLASS__, 'object_search' ] );
		add_action( 'wp_ajax_pum_process_batch_request', [ __CLASS__, 'process_batch_request' ] );
		add_action( 'wp_ajax_pum_save_enabled_state', [ __CLASS__, 'save_popup_enabled_state' ] );
	}

	/**
	 * Sets the enabled meta field to on or off
	 *
	 * @since 1.12.0
	 */
	public static function save_popup_enabled_state() {
		$args = wp_parse_args(
			$_REQUEST,
			[
				'popupID' => 0,
				'active'  => 1,
			]
		);

		// Ensures Popup ID is an int and not 0.
		$popup_id = intval( $args['popupID'] );
		if ( 0 === $popup_id ) {
			wp_send_json_error( 'Invalid popup ID provided.' );
		}

		// Ensures active state is 0 or 1.
		$enabled = intval( $args['enabled'] );
		if ( ! in_array( $enabled, [ 0, 1 ], true ) ) {
			wp_send_json_error( 'Invalid enabled state provided.' );
		}

		// Verify the nonce.
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], "pum_save_enabled_state_$popup_id" ) ) {
			wp_send_json_error();
		}

		// Dissallow if user cannot edit this popup.
		if ( ! current_user_can( 'edit_post', $popup_id ) ) {
			wp_send_json_error( 'You do not have permission to edit this popup.' );
		}

		// Get our popup and previous value.
		$popup    = pum_get_popup( $popup_id );
		$previous = $popup->get_meta( 'enabled' );

		// If value is the same, bail now.
		if ( $previous === $enabled ) {
			wp_send_json_success();
		}

		// Update our value.
		$results = $popup->update_meta( 'enabled', $enabled );

		if ( false === $results ) {
			wp_send_json_error( 'Error updating enabled state.' );
			PUM_Utils_Logging::instance()->log( "Error updating enabled state on $popup_id. Previous value: $previous. New value: $enabled" );
		} else {
			wp_send_json_success();
		}
	}

	/**
	 * Searches posts, taxonomies, and users
	 *
	 * Uses passed array with keys of object_type, object_key, include, exclude. Echos our results as JSON.
	 */
	public static function object_search() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'pum_ajax_object_search_nonce' ) ) {
			wp_send_json_error();
		}

		$results = [
			'items'       => [],
			'total_count' => 0,
		];

		$object_type = sanitize_text_field( $_REQUEST['object_type'] );
		$include     = ! empty( $_REQUEST['include'] ) ? wp_parse_id_list( $_REQUEST['include'] ) : [];
		$exclude     = ! empty( $_REQUEST['exclude'] ) ? wp_parse_id_list( $_REQUEST['exclude'] ) : [];

		if ( ! empty( $include ) ) {
			$exclude = array_merge( $include, $exclude );
		}

		switch ( $object_type ) {
			case 'post_type':
				$post_type = ! empty( $_REQUEST['object_key'] ) ? sanitize_text_field( $_REQUEST['object_key'] ) : 'post';

				if ( ! empty( $include ) ) {
					$include_query = PUM_Helpers::post_type_selectlist_query(
						$post_type,
						[
							'post__in'       => $include,
							'posts_per_page' => - 1,
						],
						true
					);

					foreach ( $include_query['items'] as $id => $name ) {
						$results['items'][] = [
							'id'   => $id,
							'text' => "$name (ID: $id)",
						];
					}

					$results['total_count'] += (int) $include_query['total_count'];
				}

				$query = PUM_Helpers::post_type_selectlist_query(
					$post_type,
					[
						's'              => ! empty( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : null,
						'paged'          => ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : null,
						'post__not_in'   => $exclude,
						'posts_per_page' => 10,
					],
					true
				);

				foreach ( $query['items'] as $id => $name ) {
					$results['items'][] = [
						'id'   => $id,
						'text' => "$name (ID: $id)",
					];
				}

				$results['total_count'] += (int) $query['total_count'];
				break;

			case 'taxonomy':
				$taxonomy = ! empty( $_REQUEST['object_key'] ) ? sanitize_text_field( $_REQUEST['object_key'] ) : 'category';

				if ( ! empty( $include ) ) {
					$include_query = PUM_Helpers::taxonomy_selectlist_query(
						$taxonomy,
						[
							'include' => $include,
							'number'  => 0,
						],
						true
					);

					foreach ( $include_query['items'] as $id => $name ) {
						$results['items'][] = [
							'id'   => $id,
							'text' => "$name (ID: $id)",
						];
					}

					$results['total_count'] += (int) $include_query['total_count'];
				}

				$query = PUM_Helpers::taxonomy_selectlist_query(
					$taxonomy,
					[
						'search'  => ! empty( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : null,
						'paged'   => ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : null,
						'exclude' => $exclude,
						'number'  => 10,
					],
					true
				);

				foreach ( $query['items'] as $id => $name ) {
					$results['items'][] = [
						'id'   => $id,
						'text' => "$name (ID: $id)",
					];
				}

				$results['total_count'] += (int) $query['total_count'];
				break;
			case 'user':
				if ( ! current_user_can( 'list_users' ) ) {
					wp_send_json_error();
				}

				$user_role = ! empty( $_REQUEST['object_key'] ) ? $_REQUEST['object_key'] : null;

				if ( ! empty( $include ) ) {
					$include_query = PUM_Helpers::user_selectlist_query(
						[
							'role'    => $user_role,
							'include' => $include,
							'number'  => - 1,
						],
						true
					);

					foreach ( $include_query['items'] as $id => $name ) {
						$results['items'][] = [
							'id'   => $id,
							'text' => "$name (ID: $id)",
						];
					}

					$results['total_count'] += (int) $include_query['total_count'];
				}

				$query = PUM_Helpers::user_selectlist_query(
					[
						'role'    => $user_role,
						'search'  => ! empty( $_REQUEST['s'] ) ? '*' . $_REQUEST['s'] . '*' : null,
						'paged'   => ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : null,
						'exclude' => $exclude,
						'number'  => 10,
					],
					true
				);

				foreach ( $query['items'] as $id => $name ) {
					$results['items'][] = [
						'id'   => $id,
						'text' => "$name (ID: $id)",
					];
				}

				$results['total_count'] += (int) $query['total_count'];
				break;
		}

		// Take out keys which were only used to deduplicate.
		$results['items'] = array_values( $results['items'] );

		echo PUM_Utils_Array::safe_json_encode( $results );
		die();
	}


	/**
	 * Handles Ajax for processing a single batch request.
	 */
	public static function process_batch_request() {
		// Batch ID.
		$batch_id = isset( $_REQUEST['batch_id'] ) ? sanitize_key( $_REQUEST['batch_id'] ) : false;

		if ( ! $batch_id ) {
			wp_send_json_error(
				[
					'error' => __( 'A batch process ID must be present to continue.', 'popup-maker' ),
				]
			);
		}

		// Nonce.
		if ( ! isset( $_REQUEST['nonce'] ) || ( isset( $_REQUEST['nonce'] ) && false === wp_verify_nonce( $_REQUEST['nonce'], "{$batch_id}_step_nonce" ) ) ) {
			wp_send_json_error(
				[
					'error' => __( 'You do not have permission to initiate this request. Contact an administrator for more information.', 'popup-maker' ),
				]
			);
		}

		// Attempt to retrieve the batch attributes from memory.
		$batch = PUM_Batch_Process_Registry::instance()->get( $batch_id );

		if ( false === $batch ) {
			wp_send_json_error(
				[
					'error' => sprintf( __( '%s is an invalid batch process ID.', 'popup-maker' ), esc_html( $_REQUEST['batch_id'] ) ),
				]
			);
		}

		$class      = isset( $batch['class'] ) ? sanitize_text_field( $batch['class'] ) : '';
		$class_file = isset( $batch['file'] ) ? $batch['file'] : '';

		if ( empty( $class_file ) || ! file_exists( $class_file ) ) {
			wp_send_json_error(
				[
					'error' => sprintf( __( 'An invalid file path is registered for the %1$s batch process handler.', 'popup-maker' ), "<code>{$batch_id}</code>" ),
				]
			);
		} else {
			require_once $class_file;
		}

		if ( empty( $class ) || ! class_exists( $class ) ) {
			wp_send_json_error(
				[
					'error' => sprintf( __( '%1$s is an invalid handler for the %2$s batch process. Please try again.', 'popup-maker' ), "<code>{$class}</code>", "<code>{$batch_id}</code>" ),
				]
			);
		}

		$step = sanitize_text_field( $_REQUEST['step'] );

		/**
		 * Instantiate the batch class.
		 *
		 * @var PUM_Interface_Batch_Exporter|PUM_Interface_Batch_Process|PUM_Interface_Batch_PrefetchProcess $process
		 */
		if ( isset( $_REQUEST['data']['upload']['file'] ) ) {

			// If this is an import, instantiate with the file and step.
			$file    = sanitize_text_field( $_REQUEST['data']['upload']['file'] );
			$process = new $class( $file, $step );

		} else {

			// Otherwise just the step.
			$process = new $class( $step );

		}

		// Garbage collect any old temporary data.
		// TODO Should this be here? Likely here to prevent case ajax passes step 1 without resetting process counts?
		if ( $step < 2 ) {
			$process->finish();
		}

		$using_prefetch = ( $process instanceof PUM_Interface_Batch_PrefetchProcess );

		// Handle pre-fetching data.
		if ( $using_prefetch ) {
			// Initialize any data needed to process a step.
			$data = isset( $_REQUEST['form'] ) ? $_REQUEST['form'] : [];

			$process->init( $data );
			$process->pre_fetch();
		}

		/** @var int|string|WP_Error $step */
		$step = $process->process_step();

		if ( is_wp_error( $step ) ) {
			wp_send_json_error( $step );
		} else {
			$response_data = [ 'step' => $step ];

			// Map fields if this is an import.
			if ( isset( $process->field_mapping ) && ( $process instanceof PUM_Interface_CSV_Importer ) ) {
				$response_data['columns'] = $process->get_columns();
				$response_data['mapping'] = $process->field_mapping;
			}

			// Finish and set the status flag if done.
			if ( 'done' === $step ) {
				$response_data['done']    = true;
				$response_data['message'] = $process->get_message( 'done' );

				// If this is an export class and not an empty export, send the download URL.
				if ( method_exists( $process, 'can_export' ) ) {

					if ( ! $process->is_empty ) {
						$response_data['url'] = pum_admin_url(
							'tools',
							[
								'step'       => $step,
								'nonce'      => wp_create_nonce( 'pum-batch-export' ),
								'batch_id'   => $batch_id,
								'pum_action' => 'download_batch_export',
							]
						);
					}
				}

				// Once all calculations have finished, run cleanup.
				$process->finish();
			} else {
				$response_data['done']       = false;
				$response_data['percentage'] = $process->get_percentage_complete();
			}

			wp_send_json_success( $response_data );
		}

	}

}
