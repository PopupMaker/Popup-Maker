<?php
/**
 * Plugin controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Localized controller class.
 */
interface Controller extends Service {
	/**
	 * Handle hooks & filters or various other init tasks.
	 *
	 * @return void
	 */
	public function init();

	/**
	 * Check if controller is enabled.
	 *
	 * @return bool
	 */
	public function controller_enabled();
}
