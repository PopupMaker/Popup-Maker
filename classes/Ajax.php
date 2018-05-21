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
		$gif = PUM_Ajax::get_file( POPMAKE_DIR . 'assets/images/beacon.gif' );
		header( 'Content-Type: image/gif' );
		header( 'Content-Length: ' . strlen( $gif ) );
		exit( $gif );
	}

	public static function get_file( $path ) {

		if ( function_exists( 'realpath' ) ) {
			$path = realpath( $path );
		}

		if ( ! $path || ! @is_file( $path ) ) {
			return '';
		}

		return @file_get_contents( $path );
	}

	/**
	 * Returns a 204 no content header.
	 */
	public static function serve_no_content() {
		header( "HTTP/1.0 204 No Content" );
		header( 'Content-Type: image/gif' );
		header( 'Content-Length: 0' );
		exit;
	}

	/**
	 * Serves a proper json response.
	 *
	 * @param mixed $data
	 */
	public static function serve_json( $data = 0 ) {
		header( 'Content-Type: application/json' );
		echo PUM_Utils_Array::safe_json_encode( $data );
		exit;
	}

}
