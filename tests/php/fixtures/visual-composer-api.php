<?php
/**
 * Visual Composer API doubles.
 *
 * @package Popup_Maker
 */

/**
 * Get a Visual Composer service double.
 *
 * @param string $service Requested service.
 *
 * @return object|null
 */
function vchelper( $service ) {
	return isset( $GLOBALS['pum_visual_composer_helpers'][ $service ] )
		? $GLOBALS['pum_visual_composer_helpers'][ $service ]
		: null;
}

/**
 * Dispatch a Visual Composer event double.
 *
 * @param string $event Event name.
 *
 * @return void
 */
function vcevent( $event ) {
	if ( 'vcv:assets:enqueue:css:list' === $event ) {
		++$GLOBALS['pum_visual_composer_events'];
	}
}
