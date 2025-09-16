<?php
/**
 * Functions for Format Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get the filesystem object.
 *
 * @deprecated 1.21.0
 *
 * @return \WP_Filesystem_Base|false
 */
function pum_get_fs() {
	return \PopupMaker\get_fs();
}

/**
 * Get the contents of a file.
 *
 * @deprecated 1.21.0
 *
 * @param string $path The path to the file.
 *
 * @return string
 */
function pum_get_file_contents( $path ) {
	return \PopupMaker\get_file_contents( $path );
}
