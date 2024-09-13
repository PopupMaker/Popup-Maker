<?php
/**
 * Interface for CSV Exporter
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Promise for structuring CSV exporters.
 *
 * @since  1.7.0
 */
interface PUM_Interface_CSV_Exporter extends PUM_Interface_Batch_Exporter {

	/**
	 * Sets the CSV columns.
	 *
	 * @return array<string,string> CSV columns.
	 */
	public function csv_cols();

	/**
	 * Retrieves the CSV columns array.
	 *
	 * Alias for csv_cols(), usually used to implement a filter on the return.
	 *
	 * @return array<string,string> CSV columns.
	 */
	public function get_csv_cols();

	/**
	 * Outputs the CSV columns.
	 *
	 * @return void
	 */
	public function csv_cols_out();

	/**
	 * Outputs the CSV rows.
	 *
	 * @return void
	 */
	public function csv_rows_out();
}
