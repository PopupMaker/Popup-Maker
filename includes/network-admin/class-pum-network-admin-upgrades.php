<?php

/**
 * Upgrade Functions
 *
 * @package     PUM
 * @subpackage  Network/Admin/Upgrades
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Network_Admin_Upgrades
 */
class PUM_Network_Admin_Upgrades extends PUM_Admin_Upgrades {

	/**
	 * @var PUM_Network_Admin_Upgrades The one true PUM_Network_Admin_Upgrades
	 */
	private static $instance;

	/**
	 * Initialize the actions needed to process upgrades.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Network_Admin_Upgrades ) ) {
			self::$instance = new PUM_Network_Admin_Upgrades;
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initialize the actions needed to process upgrades.
	 */
	public function init() {

		$this->update_plugin_version();

		// bail if this plugin data doesn't need updating
		if ( pum_get_db_ver( true ) >= PUM::DB_VER ) {
			return;
		}

		add_action( 'network_admin_menu', array( $this, 'register_pages' ) );
		add_action( 'network_admin_notices', array( $this, 'show_upgrade_notices' ) );

		parent::init();
	}

	public function register_pages() {

		// Hidden Updates Page
		$this->page = add_submenu_page(
			'popup-maker',
			__( 'Update', 'popup-maker' ),
			__( 'Update', 'popup-maker' ),
			null,
			'pum-upgrades',
			array( PUM_Admin_Upgrades::instance(), 'upgrades_screen' )
		);

		PUM_Network_Admin::$pages[] = $this->page;

	}


}

PUM_Network_Admin_Upgrades::instance();
