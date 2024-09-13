<?php

use function PopupMaker\config;

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
	public static $VER = '1.19.2';

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
	public static $MIN_PHP_VER = '7.2';

	/**
	 * @var string
	 */
	public static $MIN_WP_VER = '5.8';

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
	 * @var Popup_Maker|null The one true Popup_Maker
	 */
	private static $instance;

	/**
	 * Main instance
	 *
	 * @return Popup_Maker
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) || ! ( self::$instance instanceof Popup_Maker ) ) {
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

		/**
		 * Pull from new plugin config.
		 *
		 * @since 1.20.0
		 */
		self::$NAME        = config( 'name' );
		self::$VER         = config( 'version' );
		self::$DIR         = config( 'path' );
		self::$URL         = config( 'url' );
		self::$FILE        = config( 'file' );
		self::$MIN_PHP_VER = config( 'min_php_ver' );
		self::$MIN_WP_VER  = config( 'min_wp_ver' );
		self::$API_URL     = config( 'api_url' );

		// Ignored as we are simply checking for a query var's existence.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
			define( 'POPMAKE_SLUG', trim( dirname( plugin_basename( self::$FILE ) ), '/' ) );
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
