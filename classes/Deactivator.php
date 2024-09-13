<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.4
 * @deprecated 1.9.0 Use PUM_Install instead.
 * @package    PUM
 * @subpackage PUM/includes
 * @author     Daniel Iser <danieliser@wizardinternetsolutions.com>
 */
class PUM_Deactivator extends PUM_Install {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.4
	 * @deprecated 1.9.0
	 */
	public static function deactivate( $network_wide = false ) {
		parent::deactivate_plugin( $network_wide );
	}
}
