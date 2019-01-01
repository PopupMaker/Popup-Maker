<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* PopMake_License Class
*
* @deprecated 1.5.0
*
* Use PUM_Extension_License instead.
*/
class PopMake_License extends PUM_Extension_License {}

/**
 * PopupMaker_Plugin_Updater
 *
 * @deprecated 1.5.0 Use PUM_Extension_Updater.
 */
class PopupMaker_Plugin_Updater  extends PUM_Extension_Updater {}

/**
 * Popmake_Cron Class
 *
 * This class handles scheduled events
 *
 * @since 1.3.0
 * @deprecated 1.8.0
 */
class Popmake_Cron  extends PUM_Utils_Cron {}

