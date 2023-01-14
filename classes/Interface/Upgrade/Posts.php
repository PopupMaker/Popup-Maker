<?php
/**
 * Interface for Posts Upgrade
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
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
interface PUM_Interface_Upgrade_Posts extends PUM_Interface_Batch_PrefetchProcess {

	/**
	 * Used to filter popup query based on conditional info.
	 *
	 * @return array Returns an array of popup post type query args for this upgrade.
	 */
	public function custom_query_args();

}
