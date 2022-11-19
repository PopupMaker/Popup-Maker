<?php
/**
 * Config utility.
 *
 * @package     PUM
 * @copyright   Copyright (c) 2022, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_Config
 */
class PUM_Utils_Config {

	/**
	 * Config
	 *
	 * @param $file_name Name of file.
	 *
	 * @return mixed
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
