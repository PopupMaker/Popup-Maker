<?php
/**
 * Deprecated functions, filters & hooks.
 *
 * Here we attempt to make all new filter names backward compatible with older existing ones.
 *
 * @package     PUM
 * @subpackage  PUM_Deprecated
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * @see popmake_popup_meta_box_save
 *
 * @param $post_id
 * @param $post
 */
function pum_deprecated_save_popup_action( $post_id, $post ) {
	/**
	 * Calls old save action.
	 *
	 * @deprecated 1.4.0
	 *
	 * @param int   $post_id $post Post ID.
	 * @param array $post    Sanitized $_POST variable.
	 */
	do_action( 'popmake_save_popup', $post_id, $post );
}

add_action( 'pum_save_popup', 'pum_deprecated_save_popup_action', 10, 2 );
