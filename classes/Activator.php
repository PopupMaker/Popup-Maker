<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.4
 * @deprecated 1.9.0 Use PUM_Install instead.
 * @package    PUM
 * @subpackage PUM/includes
 * @author     Daniel Iser <danieliser@wizardinternetsolutions.com>
 */
class PUM_Activator extends PUM_Install {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.4
	 * @deprecated 1.9.0
	 *
	 * @param bool $network_wide
	 */
	public static function activate( $network_wide = false ) {
		parent::activate_plugin( $network_wide );
	}

}
