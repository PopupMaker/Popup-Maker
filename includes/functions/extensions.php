<?php
/**
 * Functions for extensions
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets an array of active extensions.
 *
 * @since 1.7.0
 *
 * @return mixed
 */
function pum_enabled_extensions() {
	return apply_filters( 'pum_enabled_extensions', [] );
}

/**
 * Checks if a specified extension is currently active.
 *
 * @since 1.7.0
 *
 * @param string $extension
 *
 * @return bool
 */
function pum_extension_enabled( $extension = '' ) {
	$enabled_extensions = pum_enabled_extensions();

	return ! empty( $extension ) && array_key_exists( $extension, $enabled_extensions );
}
