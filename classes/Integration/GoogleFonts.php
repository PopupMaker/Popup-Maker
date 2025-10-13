<?php
/**
 * Integration for GoogleFonts
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

class PUM_Integration_GoogleFonts {

	/**
	 * In-memory cache for font list to avoid repeated file reads per request.
	 *
	 * @var array|null
	 */
	private static $font_cache = null;

	/**
	 * Initialize the integration.
	 */
	public static function init() {
		add_filter( 'pum_theme_font_family_options', [ __CLASS__, 'font_family_options' ], 20 );
	}

	/**
	 * Fetch list of Google Fonts from local JSON file.
	 *
	 * Uses in-memory cache to avoid repeated file reads per request.
	 * The JSON file is updated periodically via bin/update-google-fonts.js.
	 *
	 * @param string $sort Unused - kept for backward compatibility.
	 *
	 * @return array Font list with structure: ['Family Name' => ['family' => 'Family Name', 'variants' => [...]]]
	 */
	public static function fetch_fonts( $sort = 'alpha' ) {
		// Return cached fonts if already loaded.
		if ( null !== self::$font_cache ) {
			return self::$font_cache;
		}

		// Load fonts from optimized JSON file.
		$json_file = Popup_Maker::$DIR . 'includes/google-fonts.json';

		if ( ! file_exists( $json_file ) ) {
			self::$font_cache = [];
			return self::$font_cache;
		}

		$json_data = file_get_contents( $json_file );
		$font_list = json_decode( $json_data, true );

		// Cache the result for this request.
		self::$font_cache = is_array( $font_list ) ? $font_list : [];

		return self::$font_cache;
	}

	/**
	 * Adds options to the font family dropdowns.
	 *
	 * @param $options
	 *
	 * @return array
	 */
	public static function font_family_options( $options ) {
		// If Google Fonts are disabled, return early preventing font loading.
		if ( pum_get_option( 'disable_google_font_loading', false ) ) {
			return $options;
		}

		$font_list = self::fetch_fonts();

		if ( empty( $font_list ) ) {
			return $options;
		}

		$new_options = [];

		// $options = array_merge( $options, array(
		// '' => __( 'Google Web Fonts', 'popup-maker' ) . ' &#10549;',
		// ) );

		foreach ( $font_list as $font_family => $font ) {
			$new_options[ $font_family ] = $font_family;
		}

		$options[ __( 'Google Web Fonts', 'popup-maker' ) ] = $new_options;

		return $options;
	}
}
