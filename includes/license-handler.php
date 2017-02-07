<?php
/**
 * License handler for Popup Maker
 *
 * This class should simplify the process of adding license information to new Popup Maker extensions.
 *
 * Note for wordpress.org admins. This is not called in the free hosted version and is simply used for hooking in addons to one update system rather than including it in each plugin.
 * @version 1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * PopMake_License Class
 *
 * @deprecated 1.5.0
 *
 * Use PUM_Extension_License instead.
 */
class PopMake_License extends PUM_Extension_License {}