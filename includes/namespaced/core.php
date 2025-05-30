<?php
/**
 * Core functions.
 *
 * @since X.X.X
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

/**
 * Returns an array of the default permissions.
 *
 * @return array<string,string> Default permissions.
 */
function get_default_permissions() {
	return [
		// Settings.
		'edit_popups'     => 'manage_options',
		'manage_settings' => 'manage_options',
	];
}

/**
 * Get logging service.
 *
 * @since X.X.X
 *
 * @return \PopupMaker\Services\Logging
 */
function logging() {
	return \PopupMaker\plugin()->get( 'logging' );
}
