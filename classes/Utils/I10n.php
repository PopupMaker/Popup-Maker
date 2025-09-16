<?php
/**
 * Utility for I10n
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Utils_I10n
 */
class PUM_Utils_I10n {

	/**
	 * Fetches translation status data from WordPress.org API.
	 *
	 * Stores it for 1 week. Returns empty array on API failures.
	 *
	 * @return array<string, array{
	 *     language: string,
	 *     native_name: string,
	 *     english_name: string,
	 *     iso: string[],
	 *     version: string,
	 *     updated: string,
	 *     package: string,
	 *     autoupdate: bool
	 * }>|array{} Translation data keyed by language code or empty array on error
	 */
	public static function translation_status() {
		$translations = get_transient( 'pum_alerts_translation_status' );

		if ( ! $translations ) {
			$response = wp_remote_get( 'https://api.wordpress.org/translations/plugins/1.0/?slug=popup-maker&version=' . Popup_Maker::$VER );

			// Check for WP_Error from wp_remote_get().
			if ( is_wp_error( $response ) ) {
				return [];
			}

			// Validate HTTP response code.
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				return [];
			}

			// Get response body safely.
			$response_body_raw = wp_remote_retrieve_body( $response );
			if ( empty( $response_body_raw ) ) {
				return [];
			}

			// Safely decode JSON.
			$response_body = json_decode( $response_body_raw, true );
			if ( null === $response_body || ! is_array( $response_body ) ) {
				return [];
			}

			// Ensure translations key exists and is array.
			if ( ! isset( $response_body['translations'] ) || ! is_array( $response_body['translations'] ) ) {
				return [];
			}

			$translations = $response_body['translations'];

			set_transient( 'pum_alerts_translation_status', $translations, 604800 );
		}

		// Ensure $translations is array before processing.
		if ( ! is_array( $translations ) ) {
			return [];
		}

		$ret = [];

		foreach ( $translations as $translation ) {
			// Validate translation structure before accessing.
			if ( is_array( $translation ) && isset( $translation['language'] ) && is_string( $translation['language'] ) ) {
				$ret[ $translation['language'] ] = $translation;
			}
		}

		return $ret;
	}


	/**
	 * Get locales matching the HTTP accept language header.
	 *
	 * @return string[] List of locales, empty array if none found
	 */
	public static function get_non_en_accepted_wp_locales_from_header() {
		$res = [];

		$http_locales = self::get_http_locales();

		if ( empty( $http_locales ) ) {
			return $res;
		}

		// Process locales if available.
		foreach ( $http_locales as $http_locale ) {
			$http_locale = explode( '-', $http_locale );

			$lang   = $http_locale[0];
			$region = ! empty( $http_locale[1] ) ? $http_locale[1] : null;

			if ( is_null( $region ) ) {
				$region = $lang;
			}

			/*
			 * Discard English -- it's the default for all browsers,
			 * ergo not very reliable information
			 */
			if ( 'en' === $lang ) {
				continue;
			}

			// Region should be uppercase.
			$region = strtoupper( $region );

			$mapped = self::map_locale( $lang, $region );

			if ( $mapped ) {
				$res[] = $mapped;
			}
		}

		$res = array_unique( $res );

		return $res;
	}

	/**
	 * Get available WordPress translations.
	 *
	 * @return array<string, array{
	 *     language: string,
	 *     native_name: string,
	 *     english_name: string,
	 *     iso: string[],
	 *     strings?: array<string, string>
	 * }> Available translations keyed by locale
	 */
	public static function available_locales() {
		static $available_locales;

		if ( ! isset( $available_locales ) ) {
			if ( ! function_exists( 'wp_get_available_translations' ) ) {
				$translation_install_path = ABSPATH . 'wp-admin/includes/translation-install.php';
				if ( file_exists( $translation_install_path ) ) {
					require_once $translation_install_path;
				}
			}

			$available_locales = wp_get_available_translations();
		}

		return $available_locales;
	}

	/**
	 * Tries to map a lang/region pair to one of our locales.
	 *
	 * @param string $lang   Lang part of the HTTP accept header.
	 * @param string $region Region part of the HTTP accept header.
	 *
	 * @return string|false Our locale matching $lang and $region, false otherwise.
	 */
	public static function map_locale( $lang, $region ) {
		$uregion  = strtoupper( $region );
		$ulang    = strtoupper( $lang );
		$variants = [
			"$lang-$region",
			"{$lang}_$region",
			"$lang-$uregion",
			"{$lang}_$uregion",
			"{$lang}_$ulang",
			$lang,
		];

		$available_locales = self::available_locales();

		$available_locales = array_keys( $available_locales );

		foreach ( $variants as $variant ) {
			if ( in_array( $variant, $available_locales, true ) ) {
				return $variant;
			}
		}

		foreach ( $available_locales as $locale ) {
			$locale_parts = preg_split( '/[_-]/', $locale );
			if ( false !== $locale_parts && ! empty( $locale_parts ) ) {
				$locale_lang = $locale_parts[0];
				if ( $lang === $locale_lang ) {
					return $locale;
				}
			}
		}

		return false;
	}

	/**
	 * Given a HTTP Accept-Language header returns all the locales in it.
	 *
	 * @return string[] Matched locales.
	 */
	public static function get_http_locales() {
		$locale_part_re = '[a-z]{2,}';
		$locale_re      = "($locale_part_re(\-$locale_part_re)?)";

		if ( preg_match_all( "/$locale_re/i", isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_key( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '', $matches ) ) {
			return $matches[0];
		} else {
			return [];
		}
	}
}
