<?php
/**
 * System functions.
 *
 * @since X.X.X
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2025, Code Atlantic LLC
 */

namespace PopupMaker;

use function wp_get_upload_dir;

defined( 'ABSPATH' ) || exit;

/**
 * Gets the Uploads directory
 *
 * @since X.X.X
 *
 * @return bool|array An associated array with baseurl and basedir or false on failure
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
 * @since X.X.X
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
	 * @since X.X.X
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
 * @since X.X.X
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
	 * @since X.X.X
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
