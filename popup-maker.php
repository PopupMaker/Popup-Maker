<?php
/**
 * Plugin Name:       Popup Maker
 * Plugin URI:        https://wppopupmaker.com/?utm_campaign=plugin-info&utm_source=plugin-header&utm_medium=plugin-uri
 * Description:       Easily create & style popups with any content. Theme editor to quickly style your popups. Add forms, social media boxes, videos & more.
 * Version:           1.19.0
 * Requires PHP:      5.6
 * Requires at least: 4.9
 * Author:            Popup Maker
 * Author URI:        https://wppopupmaker.com/?utm_campaign=plugin-info&utm_source=plugin-header&utm_medium=author-uri
 * License:           GPL2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       popup-maker
 * Domain Path:       /languages/
 *
 * @package     PopupMaker
 * @author      Daniel Iser
 * @copyright   Copyright (c) 2023, Code Atlantic LLC
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Autoloader
 *
 * @param $class
 */
function pum_autoloader( $class ) {

	if ( strncmp( 'PUM_Newsletter_', $class, strlen( 'PUM_Newsletter_' ) ) === 0 && class_exists( 'PUM_MCI' ) && ! empty( PUM_MCI::$VER ) && version_compare( PUM_MCI::$VER, '1.3.0', '<' ) ) {
		return;
	}

	$pum_autoloaders = apply_filters(
		'pum_autoloaders',
		[
			[
				'prefix' => 'PUM_',
				'dir'    => dirname( __FILE__ ) . '/classes/',
			],
		]
	);

	foreach ( $pum_autoloaders as $autoloader ) {
		$autoloader = wp_parse_args(
			$autoloader,
			[
				'prefix'  => 'PUM_',
				'dir'     => dirname( __FILE__ ) . '/classes/',
				'search'  => '_',
				'replace' => '/',
			]
		);

		// project-specific namespace prefix
		$prefix = $autoloader['prefix'];

		// does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			// no, move to the next registered autoloader
			continue;
		}

		// get the relative class name
		$relative_class = substr( $class, $len );

		// replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php
		$file = $autoloader['dir'] . str_replace( $autoloader['search'], $autoloader['replace'], $relative_class ) . '.php';

		// if the file exists, require it
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}

if ( ! function_exists( 'spl_autoload_register' ) ) {
	include 'includes/compat.php';
}

spl_autoload_register( 'pum_autoloader' ); // Register autoloader

/**
 * Main Popup_Maker Class
 *
 * @since 1.0
 */
class Popup_Maker {

	/**
	 * @var string Plugin Name
	 */
	public static $NAME = 'Popup Maker';

	/**
	 * @var string Plugin Version
	 */
	public static $VER = '1.19.0';

	/**
	 * @var int DB Version
	 */
	public static $DB_VER = 8;

	/**
	 * @var string License API URL
	 */
	public static $API_URL = 'https://wppopupmaker.com';

	/**
	 * @var string
	 */
	public static $MIN_PHP_VER = '5.6';

	/**
	 * @var string
	 */
	public static $MIN_WP_VER = '4.9';

	/**
	 * @var string Plugin URL
	 */
	public static $URL;

	/**
	 * @var string Plugin Directory
	 */
	public static $DIR;

	/**
	 * @var string Plugin FILE
	 */
	public static $FILE;

	/**
	 * Used to test if debug_mode is enabled.
	 *
	 * @var bool
	 */
	public static $DEBUG_MODE = false;

	/**
	 * @var PUM_Utils_Cron
	 */
	public $cron;

	/**
	 * @var PUM_Repository_Popups
	 */
	public $popups;

	/**
	 * @var PUM_Repository_Themes
	 */
	public $themes;

	/**
	 * @var null|PUM_Model_Popup
	 */
	public $current_popup;

	/**
	 * @var null|PUM_Model_Theme
	 */
	public $current_theme;

	/**
	 * @var Popup_Maker The one true Popup_Maker
	 */
	private static $instance;

	/**
	 * Main instance
	 *
	 * @return Popup_Maker
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Popup_Maker ) ) {
			self::$instance = new Popup_Maker();
			self::$instance->setup_constants();
			self::$instance->includes();
			add_action( 'init', [ self::$instance, 'load_textdomain' ] );
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants
	 */
	private function setup_constants() {

		self::$DIR  = plugin_dir_path( __FILE__ );
		self::$URL  = plugins_url( '/', __FILE__ );
		self::$FILE = __FILE__;

		if ( isset( $_GET['pum_debug'] ) || PUM_Utils_Options::get( 'debug_mode', false ) ) {
			self::$DEBUG_MODE = true;
		}

		if ( ! defined( 'POPMAKE' ) ) {
			define( 'POPMAKE', self::$FILE );
		}

		if ( ! defined( 'POPMAKE_NAME' ) ) {
			define( 'POPMAKE_NAME', self::$NAME );
		}

		if ( ! defined( 'POPMAKE_SLUG' ) ) {
			define( 'POPMAKE_SLUG', trim( dirname( plugin_basename( __FILE__ ) ), '/' ) );
		}

		if ( ! defined( 'POPMAKE_DIR' ) ) {
			define( 'POPMAKE_DIR', self::$DIR );
		}

		if ( ! defined( 'POPMAKE_URL' ) ) {
			define( 'POPMAKE_URL', self::$URL );
		}

		if ( ! defined( 'POPMAKE_NONCE' ) ) {
			define( 'POPMAKE_NONCE', 'popmake_nonce' );
		}

		if ( ! defined( 'POPMAKE_VERSION' ) ) {
			define( 'POPMAKE_VERSION', self::$VER );
		}

		if ( ! defined( 'POPMAKE_DB_VERSION' ) ) {
			define( 'POPMAKE_DB_VERSION', self::$DB_VER );
		}

		if ( ! defined( 'POPMAKE_API_URL' ) ) {
			define( 'POPMAKE_API_URL', self::$API_URL );
		}
	}

	/**
	 * Include required files
	 */
	private function includes() {

		require_once self::$DIR . 'includes/compat.php';

		// Initialize global options
		PUM_Utils_Options::init();

		/** Loads most of our core functions */
		require_once self::$DIR . 'includes/functions.php';

		/** Deprecated functionality */
		require_once self::$DIR . 'includes/functions-backcompat.php';
		require_once self::$DIR . 'includes/functions-deprecated.php';
		require_once self::$DIR . 'includes/deprecated-classes.php';
		require_once self::$DIR . 'includes/deprecated-filters.php';
		require_once self::$DIR . 'includes/integrations.php';

		// Old Stuff.
		require_once self::$DIR . 'includes/defaults.php';
		require_once self::$DIR . 'includes/input-options.php';

		require_once self::$DIR . 'includes/importer/easy-modal-v2.php';

		// Phasing Out
		require_once self::$DIR . 'includes/class-popmake-fields.php';
		require_once self::$DIR . 'includes/class-popmake-popup-fields.php';

		/**
		 * v1.4 Additions
		 */
		require_once self::$DIR . 'includes/class-pum-fields.php';
		require_once self::$DIR . 'includes/class-pum-form.php';

		// Modules
		require_once self::$DIR . 'includes/modules/menus.php';
		require_once self::$DIR . 'includes/modules/admin-bar.php';
		require_once self::$DIR . 'includes/modules/reviews.php';

		require_once self::$DIR . 'includes/pum-install-functions.php';
	}

	/**
	 * Loads the plugin language files
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$lang_dir = apply_filters( 'pum_lang_dir', dirname( plugin_basename( POPMAKE ) ) . '/languages/' );
		$lang_dir = apply_filters( 'popmake_languages_directory', $lang_dir );

		// Try to load Langpacks first, if they are not available fallback to local files.
		if ( ! load_plugin_textdomain( 'popup-maker', false, $lang_dir ) ) {
			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'popup-maker' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'popup-maker', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/popup-maker/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/popup-maker folder
				load_textdomain( 'popup-maker', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/popup-maker/languages/ folder
				load_textdomain( 'popup-maker', $mofile_local );
			}
		}
	}

	public function init() {
		$this->cron   = new PUM_Utils_Cron();
		$this->popups = new PUM_Repository_Popups();
		$this->themes = new PUM_Repository_Themes();

		PUM_Types::init();
		PUM_AssetCache::init();
		PUM_Site::init();
		PUM_Admin::init();
		PUM_Utils_Upgrades::instance();
		PUM_Newsletters::init();
		PUM_Previews::init();
		PUM_Integrations::init();
		PUM_Privacy::init();

		PUM_Utils_Alerts::init();

		PUM_Shortcode_Popup::init();
		PUM_Shortcode_PopupTrigger::init();
		PUM_Shortcode_PopupClose::init();
		PUM_Shortcode_PopupCookie::init();

		PUM_Telemetry::init();

		new PUM_Extensions();
	}

	/**
	 * Returns true when debug mode is enabled.
	 *
	 * @return bool
	 */
	public static function debug_mode() {
		return true === self::$DEBUG_MODE;
	}

}

/**
 * The main function responsible for returning the one true Popup_Maker
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return Popup_Maker
 * @since      1.8.0
 */
function pum() {
	return Popup_Maker::instance();
}

/**
 * Initialize Popup Maker if requirements are met.
 */
function pum_init() {
	// TODO Replace this with PUM_Utils_Prerequisites.
	if ( ! PUM_Install::meets_activation_requirements() ) {
		require_once 'includes/failsafes.php';
		add_action( 'admin_notices', [ 'PUM_Install', 'activation_failure_admin_notice' ] );
		return;
	}

	// Get Popup Maker
	pum();

	add_action( 'plugins_loaded', 'popmake_initialize' );
}

// Get Popup Maker running
add_action( 'plugins_loaded', 'pum_init', 9 );

// Ensure plugin & environment compatibility.
register_activation_hook( __FILE__, [ 'PUM_Install', 'activation_check' ] );

// Register activation, deactivation & uninstall hooks.
register_activation_hook( __FILE__, [ 'PUM_Install', 'activate_plugin' ] );
register_deactivation_hook( __FILE__, [ 'PUM_Install', 'deactivate_plugin' ] );
register_uninstall_hook( __FILE__, [ 'PUM_Install', 'uninstall_plugin' ] );

/**
 * @deprecated 1.7.0
 */
function popmake_initialize() {
	// Disable Unlimited Themes extension if active.
	remove_action( 'popmake_initialize', 'popmake_ut_initialize' );

	// Initialize old PUM extensions
	do_action( 'pum_initialize' );
	do_action( 'popmake_initialize' );
}

/**
 * The main function responsible for returning the one true Popup_Maker
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $popmake = PopMake(); ?>
 *
 * @return object The one true Popup_Maker Instance
 * @deprecated 1.7.0
 *
 * @since      1.0
 */
function PopMake() {
	return Popup_Maker::instance();
}
