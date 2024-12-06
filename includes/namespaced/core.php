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
		'edit_popups'       => 'manage_options',
		'edit_popup_themes' => 'manage_options',
		'manage_settings'   => 'manage_options',
	];
}
