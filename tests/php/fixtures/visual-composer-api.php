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
	return 'AssetsEnqueue' === $service ? $GLOBALS['pum_visual_composer_assets'] : null;
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
