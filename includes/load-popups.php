<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * @param $id
 */
function pum_load_popup( $id ) {
	PUM_Site_Popups::load_popup( $id );
};

/**
 * @deprecated 1.7.0 Use pum_load_popup
 *
 * @param $id
 */
function popmake_enqueue_popup( $id ) {
	pum_load_popup( $id );
}
