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
 * @deprecated X.X.X
 *
 * @return \WP_Filesystem_Base|false
 */
function pum_get_fs() {
	return \PopupMaker\get_fs();
}

/**
 * Get the contents of a file.
 *
 * @deprecated X.X.X
 *
 * @param string $path The path to the file.
 *
 * @return string
 */
function pum_get_file_contents( $path ) {
	return \PopupMaker\get_file_contents( $path );
}
