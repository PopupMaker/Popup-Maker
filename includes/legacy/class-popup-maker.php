<?php
/**
 * Main plugin class - Legacy.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

use function PopupMaker\config;

/**
 * Main Popup_Maker Class
 *
 * @since 1.0
 * @deprecated 1.21.0
 */
class Popup_Maker {

	/**
	 * @var string Plugin Name
	 */
	public static $NAME = 'Popup Maker';

	/**
	 * @var string Plugin Version
	 */
	public static $VER = '1.20.5';

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
	public static $MIN_PHP_VER = '7.4';

	/**
	 * @var string
	 */
	public static $MIN_WP_VER = '6.6';

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
	 *
	 * @deprecated 1.21.0
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

		if ( \PopupMaker\plugin()->is_debug_mode_enabled() ) {
			self::$DEBUG_MODE = true;
		}

		defined( 'POPMAKE' ) || define( 'POPMAKE', self::$FILE );
		defined( 'POPMAKE_NAME' ) || define( 'POPMAKE_NAME', self::$NAME );
		defined( 'POPMAKE_SLUG' ) || define( 'POPMAKE_SLUG', trim( dirname( plugin_basename( self::$FILE ) ), '/' ) );
		defined( 'POPMAKE_DIR' ) || define( 'POPMAKE_DIR', self::$DIR );
		defined( 'POPMAKE_URL' ) || define( 'POPMAKE_URL', self::$URL );
		defined( 'POPMAKE_NONCE' ) || define( 'POPMAKE_NONCE', 'popmake_nonce' );
		defined( 'POPMAKE_VERSION' ) || define( 'POPMAKE_VERSION', self::$VER );
		defined( 'POPMAKE_DB_VERSION' ) || define( 'POPMAKE_DB_VERSION', self::$DB_VER );
		defined( 'POPMAKE_API_URL' ) || define( 'POPMAKE_API_URL', self::$API_URL );
	}

	/**
	 * Include required files
	 */
	private function includes() {
		// Initialize global options
		// TODO Replace this with Options class.
		PUM_Utils_Options::init();
	}

	public function init() {
		$this->cron   = new PUM_Utils_Cron();
		$this->popups = new PUM_Repository_Popups();
		$this->themes = new PUM_Repository_Themes();

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
		PUM_Shortcode_CallToAction::init();

		PUM_Telemetry::init();

		new PUM_Extensions();
	}

	/**
	 * Returns true when debug mode is enabled.
	 *
	 * @return bool
	 *
	 * @deprecated 1.20.0 - Use `\PopupMaker\plugin()->is_debug_mode()` instead.
	 */
	public static function debug_mode() {
		return \PopupMaker\plugin()->is_debug_mode_enabled();
	}
}
