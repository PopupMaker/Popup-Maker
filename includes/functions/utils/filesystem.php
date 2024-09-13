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
 * @return \WP_Filesystem_Base|false
 */
function pum_get_fs() {
	static $fs = null;

	if ( isset( $fs ) ) {
		return $fs;
	}

	global $wp_filesystem;

	require_once ABSPATH . 'wp-admin/includes/file.php';

	// If for some reason the include doesn't work as expected just return false.
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		return false;
	}

	$writable = WP_Filesystem( false, '', true );

	// We consider the directory as writable if it uses the direct transport,
	// otherwise credentials would be needed.
	$fs = ( $writable && 'direct' === $wp_filesystem->method ) ? $wp_filesystem : false;

	return $fs;
}

/**
 * Get the contents of a file.
 *
 * @param string $path The path to the file.
 *
 * @return string
 */
function pum_get_file_contents( $path ) {
	$fs = pum_get_fs();

	if ( ! $fs ) {
		// Use WP fallback of file_get_contents.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return file_get_contents( $path );
	}

	return $fs->get_contents( $path );
}
