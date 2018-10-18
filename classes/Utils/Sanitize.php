<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class PUM_Utils_Sanitize
 */
class PUM_Utils_Sanitize {

	/**
	 * @param mixed|int  $value
	 * @param array $args
	 *
	 * @return int|null
	 */
	public static function checkbox( $value = null, $args = array() ) {
		if ( intval( $value ) == 1 ) {
			return 1;
		}

		// REVIEW null | 0?
		return null;
	}

}