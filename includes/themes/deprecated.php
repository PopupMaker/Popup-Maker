<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
* @deprecated use PUM_Model_Theme or pum_get_theme_generated_styles
*
* @param int $popup_theme_id
*
* @return string
*/
function popmake_generate_theme_styles( $popup_theme_id = 0 ) {
	return pum_get_theme_generated_styles( $popup_theme_id );
}
