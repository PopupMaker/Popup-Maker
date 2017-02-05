<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns $_POST key.
 *
 * @since 1.0
 *
 * @param string $name is the key you are looking for. Can use dot notation for arrays such as my_meta.field1 which will resolve to $_POST['my_meta']['field1'].
 *
 * @return mixed results of lookup
 */
function popmake_post( $name, $do_stripslashes = true ) {
	$value = popmake_resolve( $_POST, $name, false );

	return $do_stripslashes ? stripslashes_deep( $value ) : $value;
}


/**
 * Returns cleaned value.
 *
 * @since 1.0
 *
 * @param int $popup_id ID number of the popup to retrieve a name for
 *
 * @return mixed cleaned value.
 */
function popmake_post_clean( $value, $type = 'text' ) {
	return apply_filters( 'popmake_post_clean_' . $type, $value );
}


/**
 * Returns the name of a popup.
 *
 * @since 1.0
 *
 * @param int $popup_id ID number of the popup to retrieve a name for
 *
 * @return mixed string|int Price of the popup
 */
function popmake_is_all_numeric( $array ) {
	if ( ! is_array( $array ) ) {
		return false;
	}
	foreach ( $array as $val ) {
		if ( ! is_numeric( $val ) ) {
			return false;
		}
	}

	return true;
}

function pum_support_assist_args() {
	return array(
		// Forces the dashboard to force logout any users.
		'nouser' => true,
		'fname'  => wp_get_current_user()->first_name,
		'lname'  => wp_get_current_user()->last_name,
		'email'  => wp_get_current_user()->user_email,
		'url'    => home_url(),
	);
}