<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base interface for registering a batch process.
 *
 * @since  1.7.0
 */
interface PUM_Interface_Batch_Process {

	/**
	 * Determines if the current user can perform the current batch process.
	 *
	 * @return bool True if the current user has the needed capability, otherwise false.
	 */
	public function can_process();

	/**
	 * Processes a single step (batch).
	 *
	 * @return int|string|WP_Error Next step number, 'done', or a WP_Error object.
	 */
	public function process_step();

	/**
	 * Retrieves the calculated completion percentage.
	 *
	 * @return int Percentage completed.
	 */
	public function get_percentage_complete();

	/**
	 * Retrieves a message based on the given message code.
	 *
	 * @param string $code Message code.
	 *
	 * @return string Message.
	 */
	public function get_message( $code );

	/**
	 * Defines logic to execute once batch processing is complete.
	 */
	public function finish();

}
