<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Promise for structuring importers.
 *
 * @since  1.7.0
 */
interface PUM_Interface_Batch_Importer {

	/**
	 * Determines whether the current user can perform an import.
	 *
	 * @return bool Whether the current user can perform an import.
	 */
	public function can_import();

	/**
	 * Prepares the data for import.
	 *
	 * @return array[] Multi-dimensional array of data for import.
	 */
	public function get_data();

	/**
	 * Performs the import process.
	 *
	 * @return void
	 */
	public function import();

}
