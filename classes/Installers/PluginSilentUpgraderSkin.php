<?php
/**
 * PluginSilentUpgraderSkin class.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 * @since     1.21.0
 */

namespace PopupMaker\Installers;

defined( 'ABSPATH' ) || exit;

/** \WP_Upgrader_Skin class */
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';

/**
 * Class PluginSilentUpgraderSkin.
 *
 * @internal Please do not use this class outside of core plugin development. May be removed at any time.
 *
 * @since 2.0.0
 */
class PluginSilentUpgraderSkin extends \WP_Upgrader_Skin {

	/**
	 * Empty out the header of its HTML content and only check to see if it has
	 * been performed or not.
	 *
	 * @return void
	 */
	public function header() {
	}

	/**
	 * Empty out the footer of its HTML contents.
	 *
	 * @return void
	 */
	public function footer() {
	}

	/**
	 * Instead of outputting HTML for errors, just return them.
	 * Ajax request will just ignore it.
	 *
	 * @param string|\WP_Error $errors Array of errors with the install process.
	 *
	 * @return string|\WP_Error
	 */
	public function error( $errors ) {
		return $errors;
	}

	/**
	 * Empty out JavaScript output that calls function to decrement the update counts.
	 *
	 * @param string $type Type of update count to decrement.
	 *
	 * @return void
	 */
	public function decrement_update_count( $type ) {
	}
}
