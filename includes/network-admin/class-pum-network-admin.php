<?php

/**
 * Upgrade Functions
 *
 * @package     PUM
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2016, Daniel Iser
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Upgrades
 */
class PUM_Network_Admin {

	/**
	 * @var array
	 */
	public static $pages = array();

	/**
	 * Initialize the Network Admin
	 */
	public static function init() {
		add_action( 'network_admin_menu', array( __CLASS__, 'network_admin_pages' ) );
		add_action( 'admin_head', array( __CLASS__, 'relabel_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		if ( ! class_exists( 'PUM_Network_Admin_Upgrades' ) ) {
			require_once POPMAKE_DIR . 'includes/network-admin/class-pum-network-admin-upgrades.php';
		}

	}

	/**
	 * Relabels the first menu item as Extend.
	 *
	 * @uses global $submenu
	 */
	public static function relabel_menu() {
		global $submenu;

		if ( isset( $submenu['popup-maker'] ) ) {

			foreach ( $submenu['popup-maker'] as $key => $item ) {
				if ( $item[0] == __( 'Popup Maker', 'popup-maker' ) ) {
					$submenu['popup-maker'][ $key ][0] = __( 'Extend', 'popup-maker' );
				}
			}
		}
	}

	/**
	 * Register Network Admin Menu Items
	 */
	public static function network_admin_pages() {

		static::$pages[] = add_menu_page(
			__( 'Extend Popup Maker', 'popup-maker' ),
			__( 'Popup Maker', 'popup-maker' ),
			null,
			'popup-maker',
			'popmake_extensions_page',
			POPMAKE_URL . '/assets/images/admin/dashboard-icon.png',
			26
		);

		static::$pages[] = add_submenu_page(
			'popup-maker',
			__( 'Tools', 'popup-maker' ),
			__( 'Tools', 'popup-maker' ),
			null,
			'pum-tools',
			'popmake_tools_page'
		);

		// TODO Show this if there are active extensions and list the license key fields here.
		// If this is shown the settings => licenses for regular blogs should not be. Either / Or
		if ( false ) {
			static::$pages[] = add_submenu_page(
				'popup-maker',
				__( 'Licenses', 'popup-maker' ),
				__( 'Licenses', 'popup-maker' ),
				null,
				'pum-licenses',
				''
			);
		}

	}

	/**
	 * Enqueue Scripts & Styles for Network Admin Pages.
	 *
	 * @param string $hook
	 */
	public static function enqueue_scripts( $hook = '' ) {
		$js_dir = POPMAKE_URL . '/assets/js/';
		$css_dir = POPMAKE_URL . '/assets/css/';
		$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		if ( in_array( $hook, static::$pages ) ) {
			wp_enqueue_style( 'popup-maker-admin', $css_dir . 'admin' . $suffix . '.css', false, PUM::VER );
		}
	}

}

/*
 * Initialize the Popup Maker Network Admin.
 */
PUM_Network_Admin::init();
