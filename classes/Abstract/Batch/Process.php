<?php
/**
 * Batch Process Handler.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a basic batch process.
 *
 * @since 1.7.0
 */
abstract class PUM_Abstract_Batch_Process implements PUM_Interface_Batch_Process {

	/**
	 * Batch process ID.
	 *
	 * @var string
	 */
	public $batch_id;

	/**
	 * The current step being processed.
	 *
	 * @var int|string Step number or 'done'.
	 */
	public $step;

	/**
	 * Number of items to process per step.
	 *
	 * @var int
	 */
	public $per_step = 100;

	/**
	 * Capability needed to perform the current batch process.
	 *
	 * @var string
	 */
	public $capability = 'manage_options';

	/**
	 * Sets up the batch process.
	 *
	 * @param int|string $step Step number or 'done'.
	 */
	public function __construct( $step = 1 ) {

		$this->step = $step;

		if ( has_filter( "pum_batch_per_step_{$this->batch_id}" ) ) {
			/**
			 * Filters the number of items to process per step for the given batch process.
			 *
			 * The dynamic portion of the hook name, `$this->export_type` refers to the export
			 * type defined in each sub-class.
			 *
			 * @param int $per_step The number of items to process for each step. Default 100.
			 * @param PUM_Abstract_Batch_Process $process Batch process instance.
			 */
			$this->per_step = apply_filters( "pum_batch_per_step_{$this->batch_id}", $this->per_step, $this );
		}
	}

	/**
	 * Determines if the current user can perform the current batch process.
	 *
	 * @return bool True if the current user has the needed capability, otherwise false.
	 */
	public function can_process() {
		return current_user_can( $this->capability );
	}

	/**
	 * Executes a single step in the batch process.
	 *
	 * @return int|string|WP_Error Next step number, 'done', or a WP_Error object.
	 */
	public function process_step() {
		return 'done';
	}

	/**
	 * Retrieves the calculated completion percentage.
	 *
	 * @return int Percentage completed.
	 */
	public function get_percentage_complete() {
		$percentage = 0;

		$current_count = $this->get_current_count();
		$total_count   = $this->get_total_count();

		if ( $total_count > 0 ) {
			$percentage = ( $current_count / $total_count ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	/**
	 * Retrieves a message based on the given message code.
	 *
	 * @param string $code Message code.
	 *
	 * @return string Message.
	 */
	public function get_message( $code ) {
		switch ( $code ) {
			case 'done':
				$final_count = $this->get_current_count();

				/* translators: 1: Number of items processed. */
				$message = sprintf( _n( '%s item was successfully processed.', '%s items were successfully processed.', $final_count, 'popup-maker' ), number_format_i18n( $final_count ) );
				break;

			default:
				$message = '';
				break;
		}

		return $message;
	}

	/**
	 * Defines logic to execute once batch processing is complete.
	 */
	public function finish() {
		PUM_Utils_DataStorage::delete_by_match( "^{$this->batch_id}[0-9a-z\_]+" );
	}

	/**
	 * Calculates and retrieves the offset for the current step.
	 *
	 * @return int Number of items to offset.
	 */
	public function get_offset() {
		return ( $this->step - 1 ) * $this->per_step;
	}

	/**
	 * Retrieves the current, stored count of processed items.
	 *
	 * @see get_percentage_complete()
	 *
	 * @return int Current number of processed items. Default 0.
	 */
	protected function get_current_count() {
		return PUM_Utils_DataStorage::get( "{$this->batch_id}_current_count", 0 );
	}

	/**
	 * Sets the current count of processed items.
	 *
	 * @param int $count Number of processed items.
	 */
	protected function set_current_count( $count ) {
		PUM_Utils_DataStorage::write( "{$this->batch_id}_current_count", $count );
	}

	/**
	 * Retrieves the total, stored count of items to process.
	 *
	 * @see get_percentage_complete()
	 *
	 * @return int Current number of processed items. Default 0.
	 */
	protected function get_total_count() {
		return PUM_Utils_DataStorage::get( "{$this->batch_id}_total_count", 0 );
	}

	/**
	 * Sets the total count of items to process.
	 *
	 * @param int $count Number of items to process.
	 */
	protected function set_total_count( $count ) {
		PUM_Utils_DataStorage::write( "{$this->batch_id}_total_count", $count );
	}

	/**
	 * Deletes the stored current and total counts of processed items.
	 */
	protected function delete_counts() {
		PUM_Utils_DataStorage::delete( "{$this->batch_id}_current_count" );
		PUM_Utils_DataStorage::delete( "{$this->batch_id}_total_count" );
	}
}
