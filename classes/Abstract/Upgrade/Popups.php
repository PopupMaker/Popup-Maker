<?php
/**
 * Abstract for Popup Upgrades
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a batch processor for migrating existing popups to new data structure.
 *
 * @since 1.7.0
 *
 * @see   PUM_Abstract_Upgrade
 * @see   PUM_Interface_Batch_PrefetchProcess
 * @see   PUM_Interface_Upgrade_Posts
 */
abstract class PUM_Abstract_Upgrade_Popups extends PUM_Abstract_Upgrade_Posts implements PUM_Interface_Upgrade_Posts {

	/**
	 * Post type.
	 *
	 * @var    string
	 */
	public $post_type = 'popup';

	/**
	 * Process needed upgrades on each post.
	 *
	 * @param int $post_id
	 */
	public function process_post( $post_id = 0 ) {
		$this->process_popup( $post_id );
	}

	/**
	 * Process needed upgrades on each popup.
	 *
	 * @param int $popup_id
	 */
	abstract public function process_popup( $popup_id = 0 );
}
