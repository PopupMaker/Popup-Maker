<?php
/**
 * Plugin Name: Popup Maker
 * Plugin URI: https://wppopupmaker.com/?utm_campaign=PluginInfo&utm_source=plugin-header&utm_medium=plugin-uri
 * Description: Easily create & style popups with any content. Theme editor to quickly style your popups. Add forms, social media boxes, videos & more.
 * Author: WP Popup Maker
 * Version: 1.7.29
 * Author URI: https://wppopupmaker.com/?utm_campaign=PluginInfo&utm_source=plugin-header&utm_medium=author-uri
 * Text Domain: popup-maker
 *
 * @package     POPMAKE
 * @category    Core
 * @author      Daniel Iser
 * @copyright   Copyright (c) 2016, Wizard Internet Solutions
 * @since       1.0
 */

// Exit if accessed directly
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

	$pum_autoloaders = apply_filters( 'pum_autoloaders', array(
		array(
			'prefix' => 'PUM_',
			'dir'    => dirname( __FILE__ ) . '/classes/',
		),
	) );

	foreach ( $pum_autoloaders as $autoloader ) {
		$autoloader = wp_parse_args( $autoloader, array(
			'prefix'  => 'PUM_',
			'dir'     => dirname( __FILE__ ) . '/classes/',
			'search'  => '_',
			'replace' => '/',
		) );

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
	public static $VER = '1.7.29';

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
	public static $MIN_PHP_VER = '5.2.17';

	/**
	 * @var string
	 */
	public static $MIN_WP_VER = '3.6';

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
			self::$instance = new Popup_Maker;
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->load_textdomain();
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

		if ( isset( $_GET['pum_debug'] ) || PUM_Options::get( 'debug_mode', false ) ) {
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
		PUM_Options::init();

		/** @deprecated 1.7.0 */
		require_once self::$DIR . 'includes/admin/settings/register-settings.php';

		/** General Functions */
		require_once self::$DIR . 'includes/functions/cache.php';
		require_once self::$DIR . 'includes/functions/options.php';
		require_once self::$DIR . 'includes/functions/upgrades.php';
		require_once self::$DIR . 'includes/functions/developers.php';
		require_once self::$DIR . 'includes/migrations.php';

		// TODO Find another place for these admin functions so this can be put in its correct place.
		require_once self::$DIR . 'includes/admin/admin-pages.php';

		require_once self::$DIR . 'includes/actions.php';
		require_once self::$DIR . 'includes/class-popmake-cron.php';
		require_once self::$DIR . 'includes/defaults.php';
		require_once self::$DIR . 'includes/google-fonts.php';
		require_once self::$DIR . 'includes/general-functions.php';
		require_once self::$DIR . 'includes/extensions-functions.php';
		require_once self::$DIR . 'includes/input-options.php';
		require_once self::$DIR . 'includes/theme-functions.php';
		require_once self::$DIR . 'includes/misc-functions.php';
		require_once self::$DIR . 'includes/css-functions.php';
		require_once self::$DIR . 'includes/ajax-calls.php';

		require_once self::$DIR . 'includes/importer/easy-modal-v2.php';
		require_once self::$DIR . 'includes/integrations/google-fonts.php';

		require_once self::$DIR . 'includes/templates.php';
		require_once self::$DIR . 'includes/load-popups.php';
		require_once self::$DIR . 'includes/license-handler.php';

		// Phasing Out
		require_once self::$DIR . 'includes/class-popmake-fields.php';
		require_once self::$DIR . 'includes/class-popmake-popup-fields.php';
		require_once self::$DIR . 'includes/class-popmake-popup-theme-fields.php';
		require_once self::$DIR . 'includes/popup-functions.php';


		/**
		 * v1.4 Additions
		 */
		require_once self::$DIR . 'includes/class-pum.php';
		require_once self::$DIR . 'includes/class-pum-popup-query.php';
		require_once self::$DIR . 'includes/class-pum-fields.php';
		require_once self::$DIR . 'includes/class-pum-form.php';

		// Functions
		require_once self::$DIR . 'includes/pum-popup-functions.php';
		require_once self::$DIR . 'includes/pum-template-functions.php';
		require_once self::$DIR . 'includes/pum-general-functions.php';
		require_once self::$DIR . 'includes/pum-misc-functions.php';
		require_once self::$DIR . 'includes/pum-template-hooks.php';

		// Modules
		require_once self::$DIR . 'includes/modules/menus.php';
		require_once self::$DIR . 'includes/modules/admin-bar.php';
		require_once self::$DIR . 'includes/modules/reviews.php';

		// Upgrades
		if ( is_admin() ) {
			//require_once self::$DIR . 'includes/admin/class-pum-admin-upgrades.php';
		}

		// Deprecated Code
		require_once self::$DIR . 'includes/pum-deprecated.php';
		require_once self::$DIR . 'includes/pum-deprecated-v1.4.php';
		require_once self::$DIR . 'includes/pum-deprecated-v1.7.php';

		if ( is_admin() ) {
			require_once self::$DIR . 'includes/admin/admin-setup.php';
			require_once self::$DIR . 'includes/admin/admin-functions.php';
			require_once self::$DIR . 'includes/admin/themes/metabox.php';
			require_once self::$DIR . 'includes/admin/themes/metabox-close-fields.php';
			require_once self::$DIR . 'includes/admin/themes/metabox-container-fields.php';
			require_once self::$DIR . 'includes/admin/themes/metabox-content-fields.php';
			require_once self::$DIR . 'includes/admin/themes/metabox-overlay-fields.php';
			require_once self::$DIR . 'includes/admin/themes/metabox-title-fields.php';
			require_once self::$DIR . 'includes/admin/themes/metabox-preview.php';
			require_once self::$DIR . 'includes/admin/extensions/extensions-page.php';
			require_once self::$DIR . 'includes/admin/pages/support.php';
			require_once self::$DIR . 'includes/admin/metabox-support.php';
		}

		require_once self::$DIR . 'includes/integrations/class-pum-woocommerce-integration.php';
		require_once self::$DIR . 'includes/integrations/class-pum-buddypress-integration.php';

		// Ninja Forms Integration
		require_once self::$DIR . 'includes/integrations/class-pum-ninja-forms.php';
		// CF7 Forms Integration
		require_once self::$DIR . 'includes/integrations/class-pum-cf7.php';
		// Gravity Forms Integration
		require_once self::$DIR . 'includes/integrations/class-pum-gravity-forms.php';
		// WPML Integration
		require_once self::$DIR . 'includes/integrations/class-pum-wpml.php';

		require_once self::$DIR . 'includes/pum-install-functions.php';
		require_once self::$DIR . 'includes/install.php';
	}

	/**
	 * Loads the plugin language files
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$popmake_lang_dir = dirname( plugin_basename( POPMAKE ) ) . '/languages/';
		$popmake_lang_dir = apply_filters( 'popmake_languages_directory', $popmake_lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'popup-maker' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'popup-maker', $locale );

		// Setup paths to current locale file
		$mofile_local  = $popmake_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/popup-maker/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/popup-maker folder
			load_textdomain( 'popup-maker', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/popup-maker/languages/ folder
			load_textdomain( 'popup-maker', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'popup-maker', false, $popmake_lang_dir );
		}
	}

	public function init() {
		PUM_Types::init();
		PUM_AssetCache::init();
		PUM_Site::init();
		PUM_Admin::init();
		PUM_Upgrades::instance();
		PUM_Newsletters::init();
		PUM_Previews::init();
		PUM_Integrations::init();
		PUM_Privacy::init();

		PUM_Shortcode_Popup::init();
		PUM_Shortcode_PopupTrigger::init();
		PUM_Shortcode_PopupClose::init();
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
 * Initialize the plugin.
 */
Popup_Maker::instance();

/**
 * Initiate Freemius
 */
PUM_Freemius::instance();

/**
 * The code that runs during plugin activation.
 * This action is documented in classes/Activator.php
 */
register_activation_hook( __FILE__, array( 'PUM_Activator', 'activate' ) );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in classes/Deactivator.php
 */
register_deactivation_hook( __FILE__, array( 'PUM_Deactivator', 'deactivate' ) );

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

add_action( 'plugins_loaded', 'popmake_initialize' );

/**
 * The main function responsible for returning the one true Popup_Maker
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $popmake = PopMake(); ?>
 *
 * @since      1.0
 * @deprecated 1.7.0
 *
 * @return object The one true Popup_Maker Instance
 */

function PopMake() {
	return Popup_Maker::instance();
}
