<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

class PUM_Integration_GoogleFonts {

	/**
	 * @var string
	 */
	private static $default_api_key = 'AIzaSyA1Q0uFOhEh3zv_Pk31FqlACArFquyBeQU';

	/**
	 * @var string
	 */
	public static $api_key;

	/**
	 *
	 */
	public static function init() {
		// Set the API key based on options first then default second.
		self::$api_key = pum_get_option( 'google_fonts_api_key', self::$default_api_key );

		add_filter( 'pum_theme_font_family_options', array( __CLASS__, 'font_family_options' ), 20 );
	}

	/**
	 * Loads a static backup list of Google Fonts in case the API is not responding.
	 *
	 * @return array|mixed|object
	 */
	public static function load_backup_fonts() {
		$json_data = file_get_contents( Popup_Maker::$DIR . 'includes/google-fonts.json' );

		return json_decode( $json_data, true );
	}

	/**
	 * Fetch list of Google Fonts or fallback to local list.
	 *
	 * @param string $sort
	 *
	 * @return array|mixed
	 */
	public static function fetch_fonts( $sort = 'alpha' ) {
		if ( false !== $font_list = get_site_transient( 'pum_google_fonts_list' ) ) {
			return $font_list;
		}

		$google_api_url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=' . self::$api_key . '&sort=' . $sort;
		$response       = wp_remote_retrieve_body( wp_remote_get( $google_api_url, array( 'sslverify' => false ) ) );

		if ( ! is_wp_error( $response ) ) {
			$data = json_decode( $response, true );
		}

		// Store transient for a long time after fetching from Google to save API key hits.
		$transient_time = self::$api_key == self::$default_api_key ? 8 * WEEK_IN_SECONDS : 1 * WEEK_IN_SECONDS;

		if ( ! empty( $data['errors'] ) || empty( $data['items'] ) ) {
			$data = self::load_backup_fonts();
			// Store transient for short period.
			$transient_time = 1 * DAY_IN_SECONDS;
		}

		$items     = $data['items'];
		$font_list = array();

		if ( count( $items ) ) {
			foreach ( $items as $item ) {
				$font_list[ $item['family'] ] = $item;
			}
		}

		set_site_transient( 'pum_google_fonts_list', $font_list, $transient_time );

		return $font_list;
	}

	/**
	 * Adds options to the font family dropdowns.
	 *
	 * @param $options
	 *
	 * @return array
	 */
	public static function font_family_options( $options ) {
		$font_list = self::fetch_fonts();

		if ( empty( $font_list ) ) {
			return $options;
		}

		$new_options = array(

		);

//		$options = array_merge( $options, array(
//			'' => __( 'Google Web Fonts', 'popup-maker' ) . ' &#10549;',
//		) );

		foreach ( $font_list as $font_family => $font ) {
			$new_options[ $font_family ] = $font_family;
		}

		$options[__( 'Google Web Fonts', 'popup-maker' )] = $new_options;

		return $options;
	}

}