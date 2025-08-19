<?php
/**
 * Class for Admin Pages
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Pages
 *
 * @since 1.7.0
 */
class PUM_Admin_Pages {


	/**
	 * @var array
	 */
	public static $pages = [];

	/**
	 *
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_pages' ] );
		add_action( 'admin_head', [ __CLASS__, 'reorder_admin_submenu' ] );
	}

	/**
	 * Returns the requested pages handle.
	 *
	 * @param $key
	 *
	 * @return bool|mixed
	 */
	public static function get_page( $key ) {
		return isset( self::$pages[ $key ] ) ? self::$pages[ $key ] : false;
	}

	/**
	 * Get upgrade menu item based on license status.
	 *
	 * @return array|null Menu item array or null to exclude from menu.
	 */
	private static function get_upgrade_menu_item() {
		try {
			$license_service = \PopupMaker\plugin( 'license' );
			$license_status  = $license_service->get_license_status();
			$license_tier    = $license_service->get_license_tier();

			// Pro Plus license - don't show upgrade menu.
			if ( 'valid' === $license_status && 'pro_plus' === $license_tier ) {
				return null;
			}

			// Pro license (valid) - show "Go Pro+".
			if ( 'valid' === $license_status && 'pro' === $license_tier ) {
				$menu_title = __( 'Go Pro+', 'popup-maker' );
			} else {
				// No license or invalid license - show "Go Pro".
				$menu_title = __( 'Go Pro', 'popup-maker' );
			}

			return [
				'page_title' => $menu_title,
				'menu_slug'  => 'pum-settings#go-pro',
				'capability' => 'edit_posts',
				'callback'   => [ 'PUM_Admin_Settings', 'page' ],
			];
		} catch ( \Exception $e ) {
			// Fallback to default if license service unavailable.
			return [
				'page_title' => __( 'Go Pro', 'popup-maker' ),
				'menu_slug'  => 'pum-settings#go-pro',
				'capability' => 'edit_posts',
				'callback'   => [ 'PUM_Admin_Settings', 'page' ],
			];
		}
	}

	/**
	 * Creates the admin submenu pages under the Popup Maker menu and assigns their
	 * links to global variables
	 */
	public static function register_pages() {

		// Determine upgrade menu item based on license status.
		$upgrade_menu_item = self::get_upgrade_menu_item();

		$admin_pages = apply_filters(
			'pum_admin_pages',
			[
				'subscribers' => [
					'page_title' => __( 'Subscribers', 'popup-maker' ),
					'capability' => 'manage_options',
					'callback'   => [ 'PUM_Admin_Subscribers', 'page' ],
				],
				'settings'    => [
					'page_title' => __( 'Settings', 'popup-maker' ),
					'capability' => 'manage_options',
					'callback'   => [ 'PUM_Admin_Settings', 'page' ],
				],
				'extensions'  => $upgrade_menu_item,
				'support'     => [
					'page_title' => __( 'Help & Support', 'popup-maker' ),
					'capability' => 'edit_posts',
					'callback'   => [ 'PUM_Admin_Support', 'page' ],
				],
				'tools'       => [
					'page_title' => __( 'Tools', 'popup-maker' ),
					'capability' => 'manage_options',
					'callback'   => [ 'PUM_Admin_Tools', 'page' ],
				],
			]
		);

		foreach ( $admin_pages as $key => $page ) {
			// Skip null pages (e.g., upgrade menu for Pro Plus users).
			if ( null === $page ) {
				continue;
			}

			$page = wp_parse_args(
				$page,
				[
					'parent_slug' => 'edit.php?post_type=popup',
					'page_title'  => '',
					'menu_title'  => '',
					'capability'  => 'manage_options',
					'menu_slug'   => '',
					'callback'    => '',
				]
			);

			// Backward compatibility.
			$page['capability'] = apply_filters( 'popmake_admin_submenu_' . $key . '_capability', $page['capability'] );

			if ( empty( $page['menu_slug'] ) ) {
				$page['menu_slug'] = 'pum-' . $key;
			}

			if ( ! empty( $page['page_title'] ) && empty( $page['menu_title'] ) ) {
				$page['menu_title'] = $page['page_title'];
			} elseif ( ! empty( $page['menu_title'] ) && empty( $page['page_title'] ) ) {
				$page['page_title'] = $page['menu_title'];
			}

			self::$pages[ $key ] = add_submenu_page( $page['parent_slug'], $page['page_title'], $page['menu_title'], $page['capability'], $page['menu_slug'], $page['callback'] );
			// For backward compatibility.
			$GLOBALS[ 'popmake_' . $key . '_page' ] = self::$pages[ $key ];
		}

		// Add shortcut to theme editor from Appearance menu.
		add_theme_page( __( 'Popup Themes', 'popup-maker' ), __( 'Popup Themes', 'popup-maker' ), 'edit_posts', 'edit.php?post_type=popup_theme' );
	}


	/**
	 * Submenu filter function. Tested with WordPress 4.1.1
	 * Sort and order submenu positions to match our custom order.
	 *
	 * @since 1.4
	 */
	public static function reorder_admin_submenu() {
		global $submenu;

		if ( isset( $submenu['edit.php?post_type=popup'] ) ) {
			// Sort the menu according to your preferences
			usort( $submenu['edit.php?post_type=popup'], [ __CLASS__, 'reorder_submenu_array' ] );
		}
	}

	/**
	 * Reorders the submenu by title.
	 *
	 * Forces $first_pages to load in order at the beginning of the menu
	 * and $last_pages to load in order at the end. All remaining menu items will
	 * go out in generic order.
	 *
	 * @since 1.4
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public static function reorder_submenu_array( $a, $b ) {
		$first_pages = apply_filters(
			'pum_admin_submenu_first_pages',
			[
				__( 'All Popups', 'popup-maker' ),
				__( 'Add New', 'popup-maker' ),
				__( 'All Themes', 'popup-maker' ),
				__( 'Categories', 'popup-maker' ),
				__( 'Tags', 'popup-maker' ),
			]
		);

		$last_pages = apply_filters(
			'pum_admin_submenu_last_pages',
			[
				__( 'Settings', 'popup-maker' ),
				__( 'Tools', 'popup-maker' ),
				__( 'Support Forum', 'popup-maker' ),
				__( 'Account', 'popup-maker' ),
				__( 'Contact Us', 'popup-maker' ),
				__( 'Go Pro', 'popup-maker' ),
				__( 'Go Pro+', 'popup-maker' ),
				__( 'Help & Support', 'popup-maker' ),
			]
		);

		$a_val = strip_tags( $a[0], false );
		$b_val = strip_tags( $b[0], false );

		// Sort First Page Keys.
		if ( in_array( $a_val, $first_pages, true ) && ! in_array( $b_val, $first_pages, true ) ) {
			return - 1;
		} elseif ( ! in_array( $a_val, $first_pages, true ) && in_array( $b_val, $first_pages, true ) ) {
			return 1;
		} elseif ( in_array( $a_val, $first_pages, true ) && in_array( $b_val, $first_pages, true ) ) {
			$a_key = array_search( $a_val, $first_pages, true );
			$b_key = array_search( $b_val, $first_pages, true );

			return ( $a_key < $b_key ) ? - 1 : 1;
		}

		// Sort Last Page Keys.
		if ( in_array( $a_val, $last_pages, true ) && ! in_array( $b_val, $last_pages, true ) ) {
			return 1;
		} elseif ( ! in_array( $a_val, $last_pages, true ) && in_array( $b_val, $last_pages, true ) ) {
			return - 1;
		} elseif ( in_array( $a_val, $last_pages, true ) && in_array( $b_val, $last_pages, true ) ) {
			$a_key = array_search( $a_val, $last_pages, true );
			$b_key = array_search( $b_val, $last_pages, true );

			return ( $a_key < $b_key ) ? - 1 : 1;
		}

		// Sort remaining keys
		return $a > $b ? 1 : - 1;
	}
}
