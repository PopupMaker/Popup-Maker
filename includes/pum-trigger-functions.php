<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Returns the cookie fields used for trigger options.
 *
 * @deprecated v1.7.0
 *
 * @return array
 */
function pum_trigger_cookie_fields() {
	return PUM_Triggers::instance()->cookie_fields();
}

/**
 * Returns the cookie field used for trigger options.
 *
 * @deprecated v1.7.0
 *
 * @return array
 */
function pum_trigger_cookie_field() {
	return PUM_Triggers::instance()->cookie_field();
}

/**
 * Returns an array of section labels for all triggers.
 *
 * @deprecated v1.7.0
 *
 * Use the filter pum_get_trigger_section_labels to add or modify labels.
 *
 * @return array
 */
function pum_get_trigger_section_labels() {
	return PUM_Triggers::instance()->get_tabs();
}