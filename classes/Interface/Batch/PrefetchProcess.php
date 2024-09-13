<?php
/**
 * Interface for Batch PrefetchProcess
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Second-level interface for registering a batch process that leverages
 * pre-fetch and data storage.
 *
 * @since  1.7.0
 */
interface PUM_Interface_Batch_PrefetchProcess extends PUM_Interface_Batch_Process {

	/**
	 * Initializes the batch process.
	 *
	 * This is the point where any relevant data should be initialized for use by the processor methods.
	 *
	 * @param null|mixed $data
	 *
	 * @return void
	 */
	public function init( $data = null );

	/**
	 * Pre-fetches data to speed up processing.
	 */
	public function pre_fetch();
}
