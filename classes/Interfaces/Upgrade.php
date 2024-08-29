<?php
/**
 * Plugin upgrade.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Localized controller class.
 */
interface Upgrade {

	/**
	 * Return label for this upgrade.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Return full description for this upgrade.
	 *
	 * @return string
	 */
	public function description();

	/**
	 * Check if this upgrade is required.
	 *
	 * @return bool
	 */
	public function is_required();

	/**
	 * Check if prerequisites are met.
	 *
	 * @return bool
	 */
	public function prerequisites_met();

	/**
	 * Run the upgrade.
	 *
	 * @return void|\WP_Error|false
	 */
	public function run();
}
