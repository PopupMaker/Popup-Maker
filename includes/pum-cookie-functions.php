<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the cookie fields used for cookie options.
 *
 * @return array
 */
function pum_get_cookie_fields() {
	return PUM_Cookies::instance()->cookie_fields();

}



/**
 * Returns an array of args for registering coo0kies.
 *
 * @uses filter pum_get_cookies
 *
 * @return array
 */
function pum_get_cookies() {
	return PUM_Cookies::instance()->get_cookies();
}
