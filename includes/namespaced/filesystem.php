<?php
/**
 * System functions.
 *
 * @since 1.21.0
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2025, Code Atlantic LLC
 */

namespace PopupMaker;

use function wp_get_upload_dir;

defined( 'ABSPATH' ) || exit;


/**
 * Get the filesystem object.
 *
 * @return \WP_Filesystem_Base|false
 */
function get_fs() {
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
function get_file_contents( $path ) {
	$fs = \PopupMaker\get_fs();

	if ( ! $fs ) {
		// Use WP fallback of file_get_contents.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return \file_get_contents( $path );
	}

	return $fs->get_contents( $path );
}


/**
 * Gets the Uploads directory
 *
 * @since 1.21.0
 *
 * @return array{basedir: string, baseurl: string}|false An associated array with baseurl and basedir or false on failure
 */
function get_upload_dir() {
	$upload_dir = wp_get_upload_dir();

	if ( isset( $upload_dir['error'] ) && false !== $upload_dir['error'] ) {
		pum_log_message( sprintf( 'Getting uploads directory failed. Error given: %s', esc_html( $upload_dir['error'] ) ) );

		return false;
	}

	return $upload_dir;
}

/**
 * Gets the uploads directory URL
 *
 * @since 1.21.0
 *
 * @param string $path A path to append to end of upload directory URL.
 * @return bool|string The uploads directory URL or false on failure
 */
function get_upload_dir_url( $path = '' ) {
	$upload_dir = \PopupMaker\get_upload_dir();

	if ( false === $upload_dir || ! isset( $upload_dir['baseurl'] ) ) {
		return false;
	}

	$url = preg_replace( '/^https?:/', '', $upload_dir['baseurl'] );

	/**
	 * Filter the uploads directory URL.
	 *
	 * @since 1.21.0
	 *
	 * @param string $url The uploads directory URL.
	 * @return string The filtered uploads directory URL.
	 */
	$url = apply_filters( 'popup_maker/get_upload_dir_url', $url );

	if ( null === $url ) {
		return false;
	}

	return ! empty( $path ) ? trailingslashit( $url ) . $path : $url;
}

/**
 * Gets the uploads directory path
 *
 * @since 1.21.0
 *
 * @param string $path A path to append to end of upload directory URL.
 * @return bool|string The uploads directory path or false on failure
 */
function get_upload_dir_path( $path = '' ) {
	$upload_dir = \PopupMaker\get_upload_dir();

	if ( false === $upload_dir || ! isset( $upload_dir['basedir'] ) ) {
		return false;
	}

	$dir = $upload_dir['basedir'];

	/**
	 * Filter the uploads directory path.
	 *
	 * @since 1.21.0
	 *
	 * @param string $dir The uploads directory path.
	 * @return string The filtered uploads directory path.
	 */
	$dir = apply_filters( 'popup_maker/get_upload_dir_path', $dir );

	if ( null === $dir ) {
		return false;
	}

	return ! empty( $path ) ? trailingslashit( $dir ) . $path : $dir;
}
