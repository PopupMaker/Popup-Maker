<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class PUM_Admin_Ajax {

	public static function init() {
		add_action( 'wp_ajax_pum_object_search', array( __CLASS__, 'object_search' ) );
		add_action( 'wp_ajax_pum_process_batch_request', array( __CLASS__, 'process_batch_request' ) );
		// add_action( 'wp_ajax_pum_process_batch_import', array( __CLASS__, 'process_batch_import' ) );
	}

	public static function object_search() {
		$results = array(
			'items'       => array(),
			'total_count' => 0,
		);

		$object_type = sanitize_text_field( $_REQUEST['object_type'] );

		switch ( $object_type ) {
			case 'post_type':
				$post_type = ! empty( $_REQUEST['object_key'] ) ? sanitize_text_field( $_REQUEST['object_key'] ) : 'post';

				$include = ! empty( $_REQUEST['include'] ) ? wp_parse_id_list( $_REQUEST['include'] ) : null;
				$exclude = ! empty( $_REQUEST['exclude'] ) ? wp_parse_id_list( $_REQUEST['exclude'] ) : null;

				if ( ! empty( $include ) && ! empty( $exclude ) ) {
					$exclude = array_merge( $include, $exclude );
				}

				if ( $include ) {
					$include_query = PUM_Helpers::post_type_selectlist_query( $post_type, array(
						'post__in' => $include,
					), true );

					foreach ( $include_query['items'] as $id => $name ) {
						$results['items'][] = array(
							'id'   => $id,
							'text' => $name,
						);
					}

					$results['total_count'] += $include_query['total_count'];
				}

				$query = PUM_Helpers::post_type_selectlist_query( $post_type, array(
					's'              => ! empty( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : null,
					'paged'          => ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : null,
					'post__not_in'   => $exclude,
					'posts_per_page' => 10,
				), true );

				foreach ( $query['items'] as $id => $name ) {
					$results['items'][] = array(
						'id'   => $id,
						'text' => $name,
					);
				}

				$results['total_count'] += $query['total_count'];

				break;
			case 'taxonomy':
				$taxonomy = ! empty( $_REQUEST['object_key'] ) ? sanitize_text_field( $_REQUEST['object_key'] ) : 'category';

				$include = ! empty( $_REQUEST['include'] ) ? wp_parse_id_list( $_REQUEST['include'] ) : null;
				$exclude = ! empty( $_REQUEST['exclude'] ) ? wp_parse_id_list( $_REQUEST['exclude'] ) : null;

				if ( ! empty( $include ) && ! empty( $exclude ) ) {
					$exclude = array_merge( $include, $exclude );
				}

				if ( $include ) {
					$include_query = PUM_Helpers::taxonomy_selectlist_query( $taxonomy, array(
						'include' => $include,
					), true );

					foreach ( $include_query['items'] as $id => $name ) {
						$results['items'][] = array(
							'id'   => $id,
							'text' => $name,
						);
					}

					$results['total_count'] += $include_query['total_count'];
				}

				$query = PUM_Helpers::taxonomy_selectlist_query( $taxonomy, array(
					'search'  => ! empty( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : null,
					'paged'   => ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : null,
					'exclude' => $exclude,
					'number'  => 10,
				), true );

				foreach ( $query['items'] as $id => $name ) {
					$results['items'][] = array(
						'id'   => $id,
						'text' => $name,
					);
				}

				$results['total_count'] += $query['total_count'];
				break;
		}
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
			wp_send_json_error( array(
				'error' => __( 'A batch process ID must be present to continue.', 'popup-maker' ),
			) );
		}

		// Nonce.
		if ( ! isset( $_REQUEST['nonce'] ) || ( isset( $_REQUEST['nonce'] ) && false === wp_verify_nonce( $_REQUEST['nonce'], "{$batch_id}_step_nonce" ) ) ) {
			wp_send_json_error( array(
				'error' => __( 'You do not have permission to initiate this request. Contact an administrator for more information.', 'popup-maker' ),
			) );
		}

		// Attempt to retrieve the batch attributes from memory.
		$batch = PUM_Batch_Process_Registry::instance()->get( $batch_id );

		if ( $batch === false ) {
			wp_send_json_error( array(
				'error' => sprintf( __( '%s is an invalid batch process ID.', 'popup-maker' ), esc_html( $_REQUEST['batch_id'] ) ),
			) );
		}

		$class      = isset( $batch['class'] ) ? sanitize_text_field( $batch['class'] ) : '';
		$class_file = isset( $batch['file'] ) ? $batch['file'] : '';

		if ( empty( $class_file ) || ! file_exists( $class_file ) ) {
			wp_send_json_error( array(
				'error' => sprintf( __( 'An invalid file path is registered for the %1$s batch process handler.', 'popup-maker' ), "<code>{$batch_id}</code>" ),
			) );
		} else {
			require_once $class_file;
		}

		if ( empty( $class ) || ! class_exists( $class ) ) {
			wp_send_json_error( array(
				'error' => sprintf( __( '%1$s is an invalid handler for the %2$s batch process. Please try again.', 'popup-maker' ), "<code>{$class}</code>", "<code>{$batch_id}</code>" ),
			) );
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
			$data = isset( $_REQUEST['form'] ) ? $_REQUEST['form'] : array();

			$process->init( $data );
			$process->pre_fetch();
		}

		/** @var int|string|WP_Error $step */
		$step = $process->process_step();

		if ( is_wp_error( $step ) ) {
			wp_send_json_error( $step );
		} else {
			$response_data = array( 'step' => $step );

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
						$response_data['url'] = pum_admin_url( 'tools', array(
							'step'       => $step,
							'nonce'      => wp_create_nonce( 'pum-batch-export' ),
							'batch_id'   => $batch_id,
							'pum_action' => 'download_batch_export',
						) );
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

	/**
	 * Handles Ajax for processing the upload step in single batch import request.
	 *
	 * public static function process_batch_import() {
	 * if ( ! function_exists( 'wp_handle_upload' ) ) {
	 * require_once( ABSPATH . 'wp-admin/includes/file.php' );
	 * }
	 *
	 * require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/import/class-batch-import-csv.php';
	 *
	 * if ( ! wp_verify_nonce( $_REQUEST['pum_import_nonce'], 'pum_import_nonce' ) ) {
	 * wp_send_json_error( array( 'error' => __( 'Nonce verification failed', 'popup-maker' ) ) );
	 * }
	 *
	 * if ( empty( $_FILES['pum-import-file'] ) ) {
	 * wp_send_json_error( array( 'error' => __( 'Missing import file. Please provide an import file.', 'popup-maker' ), 'request' => $_REQUEST ) );
	 * }
	 *
	 * $accepted_mime_types = array(
	 * 'text/csv',
	 * 'text/comma-separated-values',
	 * 'text/plain',
	 * 'text/anytext',
	 * 'text/*',
	 * 'text/plain',
	 * 'text/anytext',
	 * 'text/*',
	 * 'application/csv',
	 * 'application/excel',
	 * 'application/vnd.ms-excel',
	 * 'application/vnd.msexcel',
	 * );
	 *
	 * if ( empty( $_FILES['pum-import-file']['type'] ) || ! in_array( strtolower( $_FILES['pum-import-file']['type'] ), $accepted_mime_types ) ) {
	 * wp_send_json_error( array( 'error' => __( 'The file you uploaded does not appear to be a CSV file.', 'popup-maker' ), 'request' => $_REQUEST ) );
	 * }
	 *
	 * if ( ! file_exists( $_FILES['pum-import-file']['tmp_name'] ) ) {
	 * wp_send_json_error( array( 'error' => __( 'Something went wrong during the upload process, please try again.', 'popup-maker' ), 'request' => $_REQUEST ) );
	 * }
	 *
	 * // Let WordPress import the file. We will remove it after import is complete
	 * $import_file = wp_handle_upload( $_FILES['pum-import-file'], array( 'test_form' => false ) );
	 *
	 * if ( $import_file && empty( $import_file['error'] ) ) {
	 *
	 * // Batch ID.
	 * if ( ! isset( $_REQUEST['batch_id'] ) ) {
	 * wp_send_json_error( array(
	 * 'error' => __( 'A batch process ID must be present to continue.', 'popup-maker' ),
	 * ) );
	 * } else {
	 * $batch_id = sanitize_key( $_REQUEST['batch_id'] );
	 * }
	 *
	 * // Attempt to retrieve the batch attributes from memory.
	 * if ( $batch_id && false === $batch = affiliate_wp()->utils->batch->get( $batch_id ) ) {
	 * wp_send_json_error( array(
	 * 'error' => sprintf( __( '%s is an invalid batch process ID.', 'popup-maker' ), esc_html( $_REQUEST['batch_id'] ) ),
	 * ) );
	 * }
	 *
	 * $class      = isset( $batch['class'] ) ? sanitize_text_field( $batch['class'] ) : '';
	 * $class_file = isset( $batch['file'] ) ? $batch['file'] : '';
	 *
	 * if ( empty( $class_file ) ) {
	 * wp_send_json_error( array(
	 * 'error' => sprintf( __( 'An invalid file path is registered for the %1$s batch process handler.', 'popup-maker' ), "<code>{$batch_id}</code>" ),
	 * ) );
	 * } else {
	 * require_once $class_file;
	 * }
	 *
	 * if ( ! class_exists( $class ) ) {
	 * wp_send_json_error( array(
	 * 'error' => sprintf( __( '%1$s is an invalid handler for the %2$s batch process. Please try again.', 'popup-maker' ), "<code>{$class}</code>", "<code>{$batch_id}</code>" ),
	 * ) );
	 * }
	 *
	 *
	 * $import = new $class( $import_file['file'] );
	 *
	 *
	 * if ( ! $import->can_import() ) {
	 * wp_send_json_error( array( 'error' => __( 'You do not have permission to import data', 'popup-maker' ) ) );
	 * }
	 *
	 * wp_send_json_success( array(
	 * 'batch_id'  => $batch_id,
	 * 'upload'    => $import_file,
	 * 'first_row' => $import->get_first_row(),
	 * 'columns'   => $import->get_columns(),
	 * 'nonce'     => wp_create_nonce( "{$batch_id}_step_nonce" ),
	 * ) );
	 *
	 * } else {
	 *
	 * /**
	 * Error generated by _wp_handle_upload()
	 *
	 * @see _wp_handle_upload() in wp-admin/includes/file.php
	 *
	 *
	 * wp_send_json_error( array( 'error' => $import_file['error'] ) );
	 * }
	 *
	 * exit;
	 * }
	 */


}
