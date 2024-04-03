<?php
/**
 * Registry Batch Process
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a batch process registry class.
 *
 * @since 1.7.0
 *
 * @see PUM_Abstract_Registry
 */
class PUM_Batch_Process_Registry extends PUM_Abstract_Registry {

	/**
	 * @var PUM_Batch_Process_Registry
	 */
	public static $instance;

	/**
	 * @return PUM_Batch_Process_Registry
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();

		}

		return self::$instance;
	}

	/**
	 * Initializes the batch registry.
	 */
	public function init() {
		$this->register_core_processes();

		/**
		 * Fires during instantiation of the batch processing registry.
		 *
		 * @param PUM_Batch_Process_Registry $this Registry instance.
		 */
		do_action( 'pum_batch_process_init', $this );
	}

	/**
	 * Registers core batch processes.
	 */
	protected function register_core_processes() {

	}

	/**
	 * Registers a new batch process.
	 *
	 * @param string $batch_id     Unique batch process ID.
	 * @param array  $process_args {
	 *     Arguments for registering a new batch process.
	 *
	 *     @type string $class Batch processor class to use.
	 *     @type string $file  File containing the batch processor class.
	 * }
	 *
	 * @return WP_Error|true True on successful registration, otherwise a WP_Error object.
	 */
	public function register_process( $batch_id, $process_args ) {
		$process_args = wp_parse_args( $process_args, array_fill_keys( [ 'class', 'file' ], '' ) );

		if ( empty( $process_args['class'] ) ) {
			return new WP_Error( 'invalid_batch_class', __( 'A batch process class must be specified.', 'popup-maker' ) );
		}

		if ( empty( $process_args['file'] ) ) {
			return new WP_Error( 'missing_batch_class_file', __( 'No batch class handler file has been supplied.', 'popup-maker' ) );
		}

		// 2 if Windows path.
		if ( ! in_array( validate_file( $process_args['file'] ), [ 0, 2 ], true ) ) {
			return new WP_Error( 'invalid_batch_class_file', __( 'An invalid batch class handler file has been supplied.', 'popup-maker' ) );
		}

		return $this->add_item( $batch_id, $process_args );
	}

	/**
	 * Removes a batch process from the registry by ID.
	 *
	 * @param string $batch_id Batch process ID.
	 */
	public function remove_process( $batch_id ) {
		$this->remove_item( $batch_id );
	}

}
