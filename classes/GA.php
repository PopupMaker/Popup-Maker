<?php
/**
 * Google Analytics helpers
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_GA
 *
 * @package Ahoy
 */
class PUM_GA {

	/**
	 * Get PUM_GA uuid.
	 *
	 * @return mixed|string
	 */
	public static function get_uuid() {
		static $uuid;

		if ( ! isset( $uuid ) ) {
			$cookie = self::parse_cookie();

			if ( is_array( $cookie ) && ! empty( $cookie['cid'] ) ) {
				$uuid = $cookie['cid'];
			} else {
				$uuid = self::generate_uuid();
			}
		}

		return $uuid;
	}

	/**
	 * Handle the parsing of the _ga cookie or setting it to a unique identifier
	 */
	public static function parse_cookie() {
		static $cookie = false;

		if ( ! $cookie && isset( $_COOKIE['_ga'] ) ) {
			list( $version, $domain_depth, $cid1, $cid2 ) = preg_split( '[\.]', sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) ), 4 );
			$cookie                                       = [
				'version'     => $version,
				'domainDepth' => $domain_depth,
				'cid'         => $cid1 . '.' . $cid2,
			];
		}

		return $cookie;
	}

	/**
	 * Generate UUID v4 function - needed to generate a CID when one isn't available
	 */
	public static function generate_uuid() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x', // 32 bits for "time_low"
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			// 16 bits for "time_mid"
			wp_rand( 0, 0xffff ),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			wp_rand( 0, 0x0fff ) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			wp_rand( 0, 0x3fff ) | 0x8000,
			// 48 bits for "node"
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff )
		);
	}


	/**
	 * Fire a hit to the google analytis collection api.
	 *
	 * See https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
	 *
	 * @param null $data
	 *
	 * @return array|bool|WP_Error
	 */
	public static function fire_hit( $data = null ) {
		if ( $data ) {
			$get_string  = 'https://ssl.google-analytics.com/collect';
			$get_string .= '?payload_data&';
			$get_string .= http_build_query( $data );
			$result      = wp_remote_get( $get_string );

			return $result;
		}

		return false;
	}
}
