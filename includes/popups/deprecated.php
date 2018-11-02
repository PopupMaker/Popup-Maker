<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

/**
 * Returns a popup object.
 *
 * @deprecated 1.7
 *
 * @param null $popup_id
 *
 * @return false|PUM_Model_Popup
 */
function pum_popup( $popup_id = null ) {
	return pum_get_popup( $popup_id );
}