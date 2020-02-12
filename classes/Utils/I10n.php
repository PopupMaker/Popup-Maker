<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PUM_Utils_I10n {

	/**
	 * Fetches translation status data from WordPress.org API.
	 *
	 * Stores it for 1 week.
	 *
	 * @return array
	 */
	public static function translation_status() {
		$translations = get_transient( 'pum_alerts_translation_status' );

		if ( ! $translations ) {
			$response = wp_remote_get( 'https://api.wordpress.org/translations/plugins/1.0/?slug=popup-maker&version=' . Popup_Maker::$VER );

			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			$translations = $response_body['translations'];

			set_transient( 'pum_alerts_translation_status', $translations, 604800 );
		}

		$ret = array();

		foreach ( $translations as $translation ) {
			$ret[ $translation['language'] ] = $translation;
		}

		return $ret;
	}


	/**
	 * Get locales matching the HTTP accept language header.
	 *
	 * @return array List of locales.
	 */
	public static function get_non_en_accepted_wp_locales_from_header() {
		$res = array();

		$http_locales = self::get_http_locales();

		if ( empty( $http_locales ) ) {
			return $res;
		}

		if ( is_array( $http_locales ) ) {
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
		}

		return $res;
	}

	/**
	 * @return array
	 */
	public static function available_locales() {
		static $available_locales;

		if ( ! isset( $available_locales ) ) {
			if ( ! function_exists( 'wp_get_available_translations' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
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
		$variants = array(
			"$lang-$region",
			"{$lang}_$region",
			"$lang-$uregion",
			"{$lang}_$uregion",
			"{$lang}_$ulang",
			$lang,
		);

		$available_locales = self::available_locales();

		$available_locales = array_keys( $available_locales );

		foreach ( $variants as $variant ) {
			if ( in_array( $variant, $available_locales ) ) {
				return $variant;
			}
		}

		foreach ( $available_locales as $locale ) {
			list( $locale_lang, ) = preg_split( '/[_-]/', $locale );
			if ( $lang === $locale_lang ) {
				return $locale;
			}
		}

		return false;
	}

	/**
	 * Given a HTTP Accept-Language header $header
	 * returns all the locales in it.
	 *
	 * @return array Matched locales.
	 */
	public static function get_http_locales() {
		$locale_part_re = '[a-z]{2,}';
		$locale_re      = "($locale_part_re(\-$locale_part_re)?)";

		if ( preg_match_all( "/$locale_re/i", isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '', $matches ) ) {
			return $matches[0];
		} else {
			return array();
		}
	}


}
