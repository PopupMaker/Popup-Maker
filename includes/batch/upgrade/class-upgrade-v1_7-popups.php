<?php
/**
 * Upgrade Popups class for batch
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a batch processor for migrating existing popups to new data structure.
 *
 * @since 1.7.0
 *
 * @see PUM_Abstract_Upgrade_Popups
 */
class PUM_Upgrade_v1_7_Popups extends PUM_Abstract_Upgrade_Popups {

	/**
	 * Batch process ID.
	 *
	 * @var    string
	 */
	public $batch_id = 'core-v1_7-popups';

	/**
	 * Process needed upgrades on each popup.
	 *
	 * @param int $popup_id
	 */
	public function process_popup( $popup_id = 0 ) {

		$popup = pum_get_popup( $popup_id );

		/**
		 * If the popup is already updated, return early.
		 */
		if ( $popup->data_version < 3 ) {

			/**
			 * Processes the popups data through a migration routine.
			 *
			 * $popup is passed by reference.
			 */
			pum_popup_migration_2( $popup );

			/**
			 * Update the popups data version.
			 */
			$popup->update_meta( 'data_version', 3 );
		}
	}

}
