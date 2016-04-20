<?php
/**
 * AJAX Initialization & Helper Functions
 *
 * @since       1.4
 * @package     PUM
 * @subpackage  PUM/includes
 * @author      Daniel Iser <danieliser@wizardinternetsolutions.com>
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls the basic analytics methods for Popup Maker
 *
 */
class PUM_Ajax {

	/**
	 * Creates and returns a 1x1 tracking gif to the browser.
	 */
	public static function serve_pixel() {
		$gif = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x90\x00\x00\xff\x00\x00\x00\x00\x00\x21\xf9\x04\x05\x10\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x04\x01\x00\x3b";
		header( 'Content-Type: image/gif' );
		header( 'Content-Length: ' . strlen( $gif ) );
		exit( $gif );
	}

	/**
	 * Serves a proper json response.
	 *
	 * @param mixed $data
	 */
	public static function serve_json( $data = 0 ) {
		header( 'Content-Type: application/json' );
		echo json_encode( $data );
		exit;
	}

}
