<?php
/**
 * Abstract for themes upgrade
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a batch processor for migrating existing popup themes to new data structure.
 *
 * @since 1.7.0
 *
 * @see PUM_Abstract_Upgrade
 * @see PUM_Interface_Batch_PrefetchProcess
 * @see PUM_Interface_Upgrade_Posts
 */
abstract class PUM_Abstract_Upgrade_Themes extends PUM_Abstract_Upgrade_Posts implements PUM_Interface_Upgrade_Posts {

	/**
	 * Post type.
	 *
	 * @var    string
	 */
	public $post_type = 'popup_theme';

	/**
	 * Process needed upgrades on each post.
	 *
	 * @param int $post_id
	 */
	public function process_post( $post_id = 0 ) {
		$this->process_theme( $post_id );
	}

	/**
	 * Process needed upgrades on each popup theme.
	 *
	 * @param int $theme_id
	 *
	 * @return int $theme_id
	 */
	abstract public function process_theme( $theme_id = 0 );

}
