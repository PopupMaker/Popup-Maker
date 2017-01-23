<?php
/**
 * PUM Debug
 *
 * @package     PUM
 * @subpackage  Classes/PUM_Debug
 * @copyright   Copyright (c) 2017, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.5.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Debug
 */
class PUM_Debug {

	/**
	 * Used to test if debug_mode is enabled.
	 *
	 * @var bool
	 */
	public static $enabled = false;

	/**
	 *
	 */
	public static function init() {
		if ( isset( $_GET['pum_debug'] ) || popmake_get_option( 'debug_mode', false ) ) {
			PUM_Debug::$enabled = true;
		}
	}

	/**
	 * @return bool
	 */
	public static function on() {
		return true == PUM_Debug::$enabled;
	}

}

PUM_Debug::init();
