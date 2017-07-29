<?php
/**
 * Plugin Name: Popup Maker
 * Plugin URI: https://wppopupmaker.com/?utm_capmaign=PluginInfo&utm_source=plugin-header&utm_medium=plugin-uri
 * Description: Easily create & style popups with any content. Theme editor to quickly style your popups. Add forms, social media boxes, videos & more.
 * Author: WP Popup Maker
 * Version: 1.6.5
 * Author URI: https://wppopupmaker.com/?utm_capmaign=PluginInfo&utm_source=plugin-header&utm_medium=author-uri
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

	$pum_autoloaders = apply_filters( 'pum_autoloaders', array(
		array(
			'prefix' => 'PUM_',
			'dir'    => __DIR__ . '/classes/',
		),
	) );

	foreach ( $pum_autoloaders as $autoloader ) {
		$autoloader = wp_parse_args( array(
			'prefix'  => 'PUM_',
			'dir'     => __DIR__ . '/classes/',
			'search'  => '_',
			'replace' => '/',
		), $autoloader );

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
	public static $VER = '1.6.5';

	/**
	 * @var int DB Version
	 */
	public static $DB_VER = 6;

	/**
	 * @var string License API URL
	 */
	public static $API_URL = 'https://wppopupmaker.com';

	/**
	 * @var string
	 */
	public static $MIN_PHP_VER = '5.3';

	/**
	 * @var string
	 */
	public static $MIN_WP_VER = '3.5';

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
			define( 'POPMAKE', __FILE__ );
		}

		if ( ! defined( 'POPMAKE_NAME' ) ) {
			define( 'POPMAKE_NAME', self::$NAME );
		}

		if ( ! defined( 'POPMAKE_SLUG' ) ) {
			define( 'POPMAKE_SLUG', trim( dirname( plugin_basename( __FILE__ ) ), '/' ) );
		}

		if ( ! defined( 'POPMAKE_DIR' ) ) {
			define( 'POPMAKE_DIR', WP_PLUGIN_DIR . '/' . POPMAKE_SLUG . '/' );
		}

		if ( ! defined( 'POPMAKE_URL' ) ) {
			define( 'POPMAKE_URL', plugins_url() . '/' . POPMAKE_SLUG );
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
		global $popmake_options;

		require_once POPMAKE_DIR . 'includes/admin/settings/register-settings.php';
		$popmake_options = popmake_get_settings();

		//PUM_Options::init();

		require_once self::$DIR . 'includes/cache.php';
		require_once self::$DIR . 'includes/options.php';

		// TODO Find another place for these admin functions so this can be put in its correct place.
		require_once POPMAKE_DIR . 'includes/admin/admin-pages.php';

		require_once POPMAKE_DIR . 'includes/actions.php';
		require_once POPMAKE_DIR . 'includes/class-popmake-cron.php';
		require_once POPMAKE_DIR . 'includes/defaults.php';
		require_once POPMAKE_DIR . 'includes/google-fonts.php';
		require_once POPMAKE_DIR . 'includes/general-functions.php';
		require_once POPMAKE_DIR . 'includes/extensions-functions.php';
		require_once POPMAKE_DIR . 'includes/input-options.php';
		require_once POPMAKE_DIR . 'includes/theme-functions.php';
		require_once POPMAKE_DIR . 'includes/misc-functions.php';
		require_once POPMAKE_DIR . 'includes/css-functions.php';
		require_once POPMAKE_DIR . 'includes/ajax-calls.php';

		require_once POPMAKE_DIR . 'includes/importer/easy-modal-v2.php';
		require_once POPMAKE_DIR . 'includes/integrations/google-fonts.php';

		require_once POPMAKE_DIR . 'includes/templates.php';
		require_once POPMAKE_DIR . 'includes/load-popups.php';
		require_once POPMAKE_DIR . 'includes/class-pum-extension-license.php';
		require_once POPMAKE_DIR . 'includes/license-handler.php';

		// Phasing Out
		require_once POPMAKE_DIR . 'includes/class-popmake-fields.php';
		require_once POPMAKE_DIR . 'includes/class-popmake-popup-fields.php';
		require_once POPMAKE_DIR . 'includes/class-popmake-popup-theme-fields.php';
		require_once POPMAKE_DIR . 'includes/popup-functions.php';


		/**
		 * v1.4 Additions
		 */
		require_once POPMAKE_DIR . 'includes/class-pum.php';
		require_once POPMAKE_DIR . 'includes/class-pum-popup-query.php';
		require_once POPMAKE_DIR . 'includes/class-pum-fields.php';
		require_once POPMAKE_DIR . 'includes/class-pum-form.php';
		require_once POPMAKE_DIR . 'includes/class-pum-previews.php';
		require_once POPMAKE_DIR . 'includes/class-pum-ajax.php';

		// Functions
		require_once POPMAKE_DIR . 'includes/pum-popup-functions.php';
		require_once POPMAKE_DIR . 'includes/pum-template-functions.php';
		require_once POPMAKE_DIR . 'includes/pum-general-functions.php';
		require_once POPMAKE_DIR . 'includes/pum-misc-functions.php';
		require_once POPMAKE_DIR . 'includes/pum-template-hooks.php';
		require_once POPMAKE_DIR . 'includes/pum-ajax-functions.php';


		// Triggers
		require_once POPMAKE_DIR . 'includes/class-pum-trigger.php';
		require_once POPMAKE_DIR . 'includes/class-pum-triggers.php';
		require_once POPMAKE_DIR . 'includes/pum-trigger-functions.php';
		if ( is_admin() ) {
			require_once POPMAKE_DIR . 'includes/admin/popups/class-metabox-triggers.php';
		}

		// Cookies
		require_once POPMAKE_DIR . 'includes/class-pum-cookie.php';
		require_once POPMAKE_DIR . 'includes/class-pum-cookies.php';
		require_once POPMAKE_DIR . 'includes/pum-cookie-functions.php';
		if ( is_admin() ) {
			require_once POPMAKE_DIR . 'includes/admin/popups/class-metabox-cookies.php';
		}

		// Conditions
		// require_once POPMAKE_DIR . 'includes/class-pum-condition.php';
		// require_once POPMAKE_DIR . 'includes/class-pum-conditions.php';
		// require_once POPMAKE_DIR . 'includes/class-pum-condition-callbacks.php';
		// require_once POPMAKE_DIR . 'includes/pum-condition-functions.php';

		// Modules
		require_once POPMAKE_DIR . 'includes/modules/menus.php';
		require_once POPMAKE_DIR . 'includes/modules/admin-bar.php';
		require_once POPMAKE_DIR . 'includes/modules/reviews.php';

		// Shortcodes
		require_once POPMAKE_DIR . 'includes/class-pum-shortcode.php';
		require_once POPMAKE_DIR . 'includes/class-pum-shortcodes.php';
		require_once POPMAKE_DIR . 'includes/shortcodes/class-pum-shortcode-popup.php';
		require_once POPMAKE_DIR . 'includes/shortcodes/class-pum-shortcode-popup-trigger.php';
		require_once POPMAKE_DIR . 'includes/shortcodes/class-pum-shortcode-popup-close.php';
		if ( is_admin() ) {
			require_once POPMAKE_DIR . 'includes/admin/shortcode-ui/class-pum-admin-shortcode-ui.php';
		}

		// Upgrades
		if ( is_admin() ) {
			require_once POPMAKE_DIR . 'includes/admin/class-pum-admin-upgrades.php';
		}

		// Deprecated Code
		require_once POPMAKE_DIR . 'includes/pum-deprecated.php';

		// Helper Classes
		if ( is_admin() ) {
			require_once POPMAKE_DIR . 'includes/admin/class-pum-admin-helpers.php';
		}


		if ( is_admin() ) {
			require_once POPMAKE_DIR . 'includes/admin/admin-setup.php';
			require_once POPMAKE_DIR . 'includes/admin/admin-functions.php';

			// Deprecated Popup Metaboxes.

			require_once POPMAKE_DIR . 'includes/admin/themes/metabox.php';
			require_once POPMAKE_DIR . 'includes/admin/themes/metabox-close-fields.php';
			require_once POPMAKE_DIR . 'includes/admin/themes/metabox-container-fields.php';
			require_once POPMAKE_DIR . 'includes/admin/themes/metabox-content-fields.php';
			require_once POPMAKE_DIR . 'includes/admin/themes/metabox-overlay-fields.php';
			require_once POPMAKE_DIR . 'includes/admin/themes/metabox-title-fields.php';
			require_once POPMAKE_DIR . 'includes/admin/themes/metabox-preview.php';

			require_once POPMAKE_DIR . 'includes/admin/settings/settings-page.php';

			require_once POPMAKE_DIR . 'includes/admin/tools/tools-page.php';

			require_once POPMAKE_DIR . 'includes/admin/extensions/extensions-page.php';
			require_once POPMAKE_DIR . 'includes/admin/pages/support.php';

			require_once POPMAKE_DIR . 'includes/admin/metabox-support.php';

		}

		if ( pum_is_v1_4_compatible() ) {
			require_once POPMAKE_DIR . 'includes/integrations/class-pum-woocommerce-integration.php';
			require_once POPMAKE_DIR . 'includes/integrations/class-pum-buddypress-integration.php';
		} else {
			require_once POPMAKE_DIR . 'includes/integrations/class-popmake-woocommerce-integration.php';
		}


		if ( defined( 'WPB_VC_VERSION' ) || defined( 'FL_BUILDER_VERSION' ) ) {
			require_once POPMAKE_DIR . 'includes/integrations/visual-composer.php';
		}

		// Ninja Forms Integration
		require_once POPMAKE_DIR . 'includes/integrations/class-pum-ninja-forms.php';
		// CF7 Forms Integration
		require_once POPMAKE_DIR . 'includes/integrations/class-pum-cf7.php';
		// Gravity Forms Integration
		require_once POPMAKE_DIR . 'includes/integrations/class-pum-gravity-forms.php';
		// WPML Integration
		require_once POPMAKE_DIR . 'includes/integrations/class-pum-wpml.php';

		require_once POPMAKE_DIR . 'includes/pum-install-functions.php';
		require_once POPMAKE_DIR . 'includes/install.php';
	}

	public function init() {
		PUM_Types::init();
		PUM_AssetCache::init();
		PUM_Site::init();
		PUM_Admin::init();
	}

	/**
	 * Returns true when debug mode is enabled.
	 *
	 * @return bool
	 */
	public static function debug_mode() {
		return true === self::$DEBUG_MODE;
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

}

#region Freemius
require_once plugin_dir_path( __FILE__ ) . 'includes/class-pum-freemius.php';
pum_fs();

#endregion Freemius

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pum-activator.php
 */
function pum_activate( $network_wide = false ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pum-activator.php';
	PUM_Activator::activate( $network_wide );
}

register_activation_hook( __FILE__, 'pum_activate' );


/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pum-deactivator.php
 */
function pum_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pum-deactivator.php';
	PUM_Deactivator::deactivate();
}

register_deactivation_hook( __FILE__, 'pum_deactivate' );


/**
 * The main function responsible for returning the one true Popup_Maker
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $popmake = PopMake(); ?>
 *
 * @since 1.0
 * @return object The one true Popup_Maker Instance
 */

function PopMake() {
	return Popup_Maker::instance();
}

PopMake();

function popmake_initialize() {
	// Disable Unlimited Themes extension if active.
	remove_action( 'popmake_initialize', 'popmake_ut_initialize' );

	// Initialize old PUM extensions
	do_action( 'pum_initialize' );
	do_action( 'popmake_initialize' );
}

add_action( 'plugins_loaded', 'popmake_initialize' );
