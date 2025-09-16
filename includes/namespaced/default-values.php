<?php
/**
 * Core functions.
 *
 * @since 1.21.0
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;

/**
 * Returns an array of the default settings.
 *
 * @return array<string,mixed> Default settings.
 *
 * @since 1.21.0
 */
function get_default_settings() {
	return [
		// TODO Fill this with default global plugin settings.
	];
}

/**
 * Get default call to action settings.
 *
 * @return array<string,mixed> Default call to action settings.
 *
 * @since 1.21.0
 */
function get_default_call_to_action_settings() {
	return [
		'type' => 'link',
		'url'  => '',
	];
}
