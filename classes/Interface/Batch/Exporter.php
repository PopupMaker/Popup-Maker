<?php
/**
 * Batch Exporter Handler for Interface
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Promise for structuring exporters.
 *
 * @since  1.7.0
 */
interface PUM_Interface_Batch_Exporter {

	/**
	 * Determines whether the current user can perform an export.
	 *
	 * @return bool Whether the current user can perform an export.
	 */
	public function can_export();

	/**
	 * Handles sending appropriate headers depending on the type of export.
	 *
	 * @return void
	 */
	public function headers();

	/**
	 * Retrieves the data for export.
	 *
	 * @return array[] Multi-dimensional array of data for export.
	 */
	public function get_data();

	/**
	 * Performs the export process.
	 *
	 * @return void
	 */
	public function export();

}
