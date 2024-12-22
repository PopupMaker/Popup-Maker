<?php
/**
 * Core functions.
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
 *
 * @since X.X.X
 */
function get_default_permissions() {
	return [
		// Settings.
		'edit_ctas'         => 'manage_options',
		'edit_popups'       => 'manage_options',
		'edit_popup_themes' => 'manage_options',
		'manage_settings'   => 'manage_options',
	];
}
