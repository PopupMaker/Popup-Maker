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
 * @since       1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pum_initialize_deprecated() {

	for( $i = 0; $i <= PUM::DB_VER; $i ++ ) {
		$filename = POPMAKE_DIR . 'includes/deprecated/v' . $i . '.php';
		if ( file_exists( $filename ) ) {
			require_once $filename;
		}
	}

	do_action( 'pum_initialize_deprecated' );

}

add_action( 'init', 'pum_initialize_deprecated' );
