<?php
/**
 * Plugin Name: Popup Maker
 * Plugin URI: https://wppopupmaker.com
 * Description: Easily create & style popups with any content. Theme editor to quickly style your popups. Add forms, social media boxes, videos & more.
 * Author: Daniel Iser
 * Version: 1.3.6
 * Author URI: https://wppopupmaker.com
 * Text Domain: popup-maker
 *
 * @package        POPMAKE
 * @category    Core
 * @author        Daniel Iser
 * @copyright    Copyright (c) 2014, Wizard Internet Solutions
 * @since        1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Popup_Maker' ) ) :

	/**
	 * Main Popup_Maker Class
	 *
	 * @since 1.0
	 */
	final class Popup_Maker {
		/** Singleton *************************************************************/

		/**
		 * @var Popup_Maker The one true Popup_Maker
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * POPMAKE Roles Object
		 *
		 * @var object
		 * @since 1.0
		 */
		public $roles;

		/**
		 * POPMAKE HTML Session Object
		 *
		 * This holds cart items, purchase sessions, and anything else stored in the session
		 *
		 *
		 * @var object
		 * @since 1.0
		 */
		public $session;

		/**
		 * Main Popup_Maker Instance
		 *
		 * Insures that only one instance of Popup_Maker exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0
		 * @static
		 * @staticvar array $instance
		 * @uses Popup_Maker::setup_constants() Setup the constants needed
		 * @uses Popup_Maker::includes() Include the required files
		 * @uses Popup_Maker::load_textdomain() load the language files
		 * @see  PopMake()
		 * @return Popup_Maker
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Popup_Maker ) ) {
				self::$instance = new Popup_Maker;
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();

				register_activation_hook( __FILE__, 'popmake_install' );
			}

			return self::$instance;
		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 1.0
		 * @access protected
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'popup-maker' ), '3' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since 1.0
		 * @access protected
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'popup-maker' ), '3' );
		}

		/**
		 * Setup plugin constants
		 *
		 * @access private
		 * @since 1.0
		 * @return void
		 */
		private function setup_constants() {

			if ( ! defined( 'POPMAKE' ) ) {
				define( 'POPMAKE', __FILE__ );
			}

			if ( ! defined( 'POPMAKE_NAME' ) ) {
				define( 'POPMAKE_NAME', 'Popup Maker' );
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
				define( 'POPMAKE_VERSION', '1.3.6' );
			}

			if ( ! defined( 'POPMAKE_DB_VERSION' ) ) {
				define( 'POPMAKE_DB_VERSION', '1.0' );
			}

			if ( ! defined( 'POPMAKE_API_URL' ) ) {
				define( 'POPMAKE_API_URL', 'https://wppopupmaker.com' );
			}

		}

		/**
		 * Include required files
		 *
		 * @access private
		 * @since 1.0
		 * @return void
		 */
		private function includes() {
			global $popmake_options;

			require_once POPMAKE_DIR . 'includes/admin/settings/register-settings.php';
			$popmake_options = popmake_get_settings();


			require_once POPMAKE_DIR . 'includes/actions.php';
			require_once POPMAKE_DIR . 'includes/post-types.php';
			require_once POPMAKE_DIR . 'includes/class-popmake-fields.php';
			require_once POPMAKE_DIR . 'includes/class-popmake-cron.php';
			require_once POPMAKE_DIR . 'includes/scripts.php';
			require_once POPMAKE_DIR . 'includes/shortcodes.php';
			require_once POPMAKE_DIR . 'includes/defaults.php';
			require_once POPMAKE_DIR . 'includes/google-fonts.php';
			require_once POPMAKE_DIR . 'includes/general-functions.php';
			require_once POPMAKE_DIR . 'includes/extensions-functions.php';
			require_once POPMAKE_DIR . 'includes/input-options.php';
			require_once POPMAKE_DIR . 'includes/popup-functions.php';
			require_once POPMAKE_DIR . 'includes/theme-functions.php';
			require_once POPMAKE_DIR . 'includes/misc-functions.php';
			require_once POPMAKE_DIR . 'includes/css-functions.php';
			require_once POPMAKE_DIR . 'includes/ajax-calls.php';

			require_once POPMAKE_DIR . 'includes/importer/easy-modal-v2.php';
			require_once POPMAKE_DIR . 'includes/integrations/gravityforms.php';
			require_once POPMAKE_DIR . 'includes/integrations/google-fonts.php';

			require_once POPMAKE_DIR . 'includes/templates.php';
			require_once POPMAKE_DIR . 'includes/load-popups.php';
			require_once POPMAKE_DIR . 'includes/license-handler.php';

			if ( is_admin() ) {
				require_once POPMAKE_DIR . 'includes/admin/welcome.php';
				require_once POPMAKE_DIR . 'includes/admin/welcome/about.php';
				require_once POPMAKE_DIR . 'includes/admin/welcome/credits.php';
				require_once POPMAKE_DIR . 'includes/admin/welcome/changelog.php';
				require_once POPMAKE_DIR . 'includes/admin/welcome/getting-started.php';
				require_once POPMAKE_DIR . 'includes/admin/admin-setup.php';
				require_once POPMAKE_DIR . 'includes/admin/admin-functions.php';
				require_once POPMAKE_DIR . 'includes/admin/admin-pages.php';
				require_once POPMAKE_DIR . 'includes/admin/post-editor.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/metabox.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/dashboard-columns.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/metabox-close-fields.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/metabox-display-fields.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/metabox-click-open-fields.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/metabox-themes-fields.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/metabox-targeting-condition-fields.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/metabox-auto-open-popups-fields.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/metabox-admin-debug-fields.php';
				require_once POPMAKE_DIR . 'includes/admin/popups/post-type-item-metaboxes.php';
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
				require_once POPMAKE_DIR . 'includes/admin/help/help-page.php';
				require_once POPMAKE_DIR . 'includes/admin/metabox-support.php';
				require_once POPMAKE_DIR . 'includes/admin/metabox-share.php';
				require_once POPMAKE_DIR . 'includes/admin/tracking.php';

				require_once POPMAKE_DIR . 'includes/admin/upgrades/v1_3.php';
			}

			if ( class_exists( 'WooCommerce' ) ) {
				require_once POPMAKE_DIR . 'includes/integrations/woocommerce.php';
			}

			require_once POPMAKE_DIR . 'includes/install.php';
		}

		/**
		 * Loads the plugin language files
		 *
		 * @access public
		 * @since 1.0
		 * @return void
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

		public function process_upgrades() {
			if ( ! is_admin() ) {
				return;
			}

			// Add Upgraded From Option
			$current_version = get_option( 'popmake_version' );
			if ( $current_version ) {
				update_option( 'popmake_version_upgraded_from', $current_version );
			}

			if ( $current_version != POPMAKE_VERSION ) {
				do_action( "popmake_process_upgrade", POPMAKE_VERSION, $current_version );
			}

			update_option( 'popmake_version', POPMAKE_VERSION );
		}

	}

endif; // End if class_exists check


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


function popmake_initialize() {

	// Disable Unlimited Themes extension if active.
	remove_action( 'popmake_initialize', 'popmake_ut_initialize' );

	// Get Popup Maker Running
	PopMake();
	do_action( 'popmake_initialize' );

	PopMake()->process_upgrades();
}

add_action( 'plugins_loaded', 'popmake_initialize', 0 );
