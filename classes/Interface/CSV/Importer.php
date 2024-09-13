<?php
/**
 * Interface for CSV Importer
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Promise for structuring CSV importers.
 *
 * @since  1.7.0
 *
 * @see PUM_Interface_Batch_Importer
 */
interface PUM_Interface_CSV_Importer extends PUM_Interface_Batch_Importer {

	/**
	 * Maps CSV columns to their corresponding import fields.
	 *
	 * @param array $import_fields Import fields to map.
	 */
	public function map_fields( $import_fields = [] );

	/**
	 * Retrieves the CSV columns.
	 *
	 * @return array The columns in the CSV.
	 */
	public function get_columns();

	/**
	 * Maps a single CSV row to the data passed in via init().
	 *
	 * @param array $csv_row CSV row data.
	 *
	 * @return array CSV row data mapped to form-defined arguments.
	 */
	public function map_row( $csv_row );

	/**
	 * Retrieves the first row of the CSV.
	 *
	 * This is used for showing an example of what the import will look like.
	 *
	 * @return array The first row after the header of the CSV.
	 */
	public function get_first_row();
}
