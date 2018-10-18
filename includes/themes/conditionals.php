<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Checks if the $theme is valid.
 *
 * @param mixed|PUM_Model_Theme $theme
 *
 * @return bool
 */
function pum_is_theme( $theme ) {
	return is_object( $theme ) && is_numeric( $theme->ID ) && $theme->is_valid();
}

/**
 * Tests a given value to see if its a valid Theme model.
 *
 * @param mixed|PUM_Model_Theme $theme
 *
 * @return bool
 */
function pum_is_theme_object( $theme ) {
	return is_a( $theme, 'PUM_Model_Theme' );
}
