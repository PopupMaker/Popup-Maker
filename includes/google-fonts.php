<?php

/**
 * Google Web Font Integrations.
 */

function popmake_get_google_webfonts_list( $key = 'AIzaSyAqXbKCykzxMy2-fnmGBjiwI_-LdfoFxAU', $sort = 'alpha' ) {
	/*
	$key = Web Fonts Developer API
	$sort=
		alpha: Sort the list alphabetically
		date: Sort the list by date added (most recent font added or updated first)
		popularity: Sort the list by popularity (most popular family first)
		style: Sort the list by number of styles available (family with most styles first)
		trending: Sort the list by families seeing growth in usage (family seeing the most growth first)
	*/

	if($font_list = get_transient( 'popmake-google-fonts-list' )) {
		return $font_list;
	}

	$google_api_url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=' . $key . '&sort=' . $sort;
	$response = wp_remote_retrieve_body( wp_remote_get($google_api_url, array('sslverify' => false )) );
	if( !is_wp_error( $response ) ) {
		$data = json_decode($response, true);
		$items = $data['items'];
		$font_list = array();
		foreach($items as $item) {
			$font_list[$item['family']] = $item;
		}
		set_transient( 'popmake-google-fonts-list', $font_list, WEEK_IN_SECONDS );
		return $font_list;
	}

	return array();
}


add_filter('popmake_font_family_options', 'popmake_google_font_font_family_options', 20);
function popmake_google_font_font_family_options( $options ) {
	$options = array_merge($options, array(
		// option => value
		__( 'Google Web Fonts&#10549;', 'popup-maker' )	=> '',
	));
	foreach(popmake_get_google_webfonts_list() as $font_family => $font) {
		$options[ __( $font_family, 'popup-maker' ) ] = $font_family;
	}
	return $options;
}
