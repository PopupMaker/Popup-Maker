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
		add_action( 'admin_head', [ __CLASS__, 'output_admin_menu_styles' ] );
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
	 * Resolve a submenu capability through the retained compatibility filter.
	 *
	 * @param string $key      Submenu key.
	 * @param string $fallback Default capability.
	 *
	 * @return string
	 */
	public static function get_submenu_capability( $key, $fallback = 'manage_options' ) {
		$capability = apply_filters( 'popmake_admin_submenu_' . $key . '_capability', $fallback );

		return is_string( $capability ) && '' !== $capability ? $capability : $fallback;
	}

	/**
	 * Creates the admin submenu pages under the Popup Maker menu and assigns their
	 * links to global variables
	 */
	public static function register_pages() {

		$admin_pages = apply_filters(
			'pum_admin_pages',
			[
				'subscribers' => self::subscribers_initialized() ? [
					'page_title' => __( 'Subscribers', 'popup-maker' ),
					'capability' => 'manage_options',
					'callback'   => [ 'PUM_Admin_Subscribers', 'page' ],
				] : null,
				'settings'    => [
					'page_title' => __( 'Settings', 'popup-maker' ),
					'capability' => 'manage_options',
					'callback'   => [ 'PUM_Admin_Settings', 'page' ],
				],
				'extensions'  => [
					'page_title' => __( 'Popup Maker Add-ons', 'popup-maker' ),
					'menu_title' => __( 'Extend', 'popup-maker' ),
					'capability' => 'manage_options',
					'menu_slug'  => 'pum-extensions',
					'callback'   => [ 'PUM_Admin_Extend', 'page' ],
				],
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
			// Skip pages removed by an integration.
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
			$page['capability'] = self::get_submenu_capability( $key, $page['capability'] );

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
	 * Whether subscriber storage has been initialized at least once.
	 *
	 * Reading the stored schema version avoids instantiating the database class,
	 * which would create the table merely by building the admin menu.
	 *
	 * @return bool
	 */
	public static function subscribers_initialized() {
		$db_versions = get_option( 'pum_db_versions', [] );

		if ( is_array( $db_versions ) && ! empty( $db_versions['pum_subscribers'] ) ) {
			return true;
		}

		return (bool) get_option( 'pum_subscribers_db_version', false );
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
			// Sort the menu according to your preferences.
			usort( $submenu['edit.php?post_type=popup'], [ __CLASS__, 'reorder_submenu_array' ] );
			self::add_submenu_separator_classes( $submenu['edit.php?post_type=popup'] );
		}
	}

	/**
	 * Mark logical Popup Maker submenu groups without adding fake menu entries.
	 *
	 * @param array<int,array<int,mixed>> $items Popup Maker submenu items.
	 *
	 * @return void
	 */
	public static function add_submenu_separator_classes( &$items ) {
		$separator_pages = apply_filters(
			'pum_admin_submenu_separator_before_pages',
			[
				__( 'Subscribers', 'popup-maker' ),
				__( 'Extend', 'popup-maker' ),
				__( 'Go Pro', 'popup-maker' ),
				__( 'Go Pro+', 'popup-maker' ),
			]
		);

		foreach ( $items as &$item ) {
			$title = isset( $item[0] ) ? strip_tags( $item[0], false ) : '';

			if ( ! in_array( $title, $separator_pages, true ) ) {
				continue;
			}

			$classes   = isset( $item[4] ) && is_string( $item[4] )
				? preg_split( '/\s+/', trim( $item[4] ) )
				: [];
			$classes   = is_array( $classes ) ? $classes : [];
			$classes[] = 'pum-submenu-separator-before';
			$item[4]   = implode( ' ', array_unique( array_filter( $classes ) ) );
		}
		unset( $item );
	}

	/**
	 * Add subtle visual grouping to the Popup Maker submenu.
	 *
	 * @return void
	 */
	public static function output_admin_menu_styles() {
		?>
		<style id="popup-maker-admin-menu-groups">
			#adminmenu #menu-posts-popup .wp-submenu li.pum-submenu-separator-before {
				margin-top: 7px;
				padding-top: 7px;
				border-top: 1px solid rgba(255, 255, 255, 0.14);
			}
		</style>
		<?php
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
				__( 'Add New Popup', 'popup-maker' ),
				__( 'All Themes', 'popup-maker' ),
				__( 'Popup Themes', 'popup-maker' ),
				__( 'Calls to Action', 'popup-maker' ),
				__( 'Categories', 'popup-maker' ),
				__( 'Tags', 'popup-maker' ),
				__( 'Subscribers', 'popup-maker' ),
			]
		);

		$last_pages = apply_filters(
			'pum_admin_submenu_last_pages',
			[
				__( 'Go Pro', 'popup-maker' ),
				__( 'Go Pro+', 'popup-maker' ),
				__( 'Settings', 'popup-maker' ),
				__( 'Tools', 'popup-maker' ),
				__( 'Support Forum', 'popup-maker' ),
				__( 'Account', 'popup-maker' ),
				__( 'Contact Us', 'popup-maker' ),
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
