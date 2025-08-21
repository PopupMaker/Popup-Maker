<?php
/**
 * Config Utility
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Config
 */
class PUM_Utils_Config {

	/**
	 * Load configuration file
	 *
	 * @param string $file_name Configuration file name (without .php extension)
	 * @return array<string, mixed> Configuration array or empty array if file not found
	 */
	public static function load( $file_name ) {

		$file_name = str_replace( '\\', DIRECTORY_SEPARATOR, $file_name );

		$file = plugin_dir_path( __DIR__ ) . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . $file_name . '.php';

		if ( ! file_exists( $file ) ) {
			return [];
		}

		return include $file;
	}
}
