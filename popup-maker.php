<?php
/**
 * Plugin Name: Popup Maker
 * Plugin URI: https://wppopupmaker.com
 * Description: Easily create & style popups with any content. Theme editor to quickly style your popups. Add forms, social media boxes, videos & more.
 * Author: Daniel Iser
 * Version: 1.3.9
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
			// Unserializing instances of the class is forbiddePOPMAKE_DB_VERSIONn
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
				define( 'POPMAKE_VERSION', '1.4.0' );
			}

			if ( ! defined( 'POPMAKE_DB_VERSION' ) ) {
				define( 'POPMAKE_DB_VERSION', '5' );
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

			// Phasing Out
			require_once POPMAKE_DIR . 'includes/class-popmake-fields.php';
			require_once POPMAKE_DIR . 'includes/class-popmake-popup-fields.php';
			require_once POPMAKE_DIR . 'includes/class-popmake-popup-theme-fields.php';
			require_once POPMAKE_DIR . 'includes/popup-functions.php';

			/**
			 * v1.4 Additions
			 */
			require_once POPMAKE_DIR . 'includes/class-pum.php';
			require_once POPMAKE_DIR . 'includes/class-pum-post.php';
			require_once POPMAKE_DIR . 'includes/class-pum-popup.php';
			require_once POPMAKE_DIR . 'includes/class-pum-popup-query.php';
			require_once POPMAKE_DIR . 'includes/class-pum-fields.php';
			require_once POPMAKE_DIR . 'includes/class-pum-previews.php';
			require_once POPMAKE_DIR . 'includes/class-pum-ajax.php';

			// Functions
			require_once POPMAKE_DIR . 'includes/pum-popup-functions.php';
			require_once POPMAKE_DIR . 'includes/pum-template-functions.php';
			require_once POPMAKE_DIR . 'includes/pum-general-functions.php';
			require_once POPMAKE_DIR . 'includes/pum-misc-functions.php';
			require_once POPMAKE_DIR . 'includes/pum-template-hooks.php';

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
			require_once POPMAKE_DIR . 'includes/class-pum-condition.php';
			require_once POPMAKE_DIR . 'includes/class-pum-conditions.php';
			require_once POPMAKE_DIR . 'includes/class-pum-condition-callbacks.php';
			require_once POPMAKE_DIR . 'includes/pum-condition-functions.php';
			if ( is_admin() ) {
				require_once POPMAKE_DIR . 'includes/admin/popups/class-metabox-conditions.php';
			}

			// Analytics
			require_once POPMAKE_DIR . 'includes/class-pum-analytics.php';
			if ( is_admin() ) {
				require_once POPMAKE_DIR . 'includes/admin/popups/class-metabox-analytics.php';
			}

			// Upgrades
			if ( is_admin() ) {
				require_once POPMAKE_DIR . 'includes/admin/class-pum-admin-upgrades.php';
			}

			require_once POPMAKE_DIR . 'includes/pum-ajax-functions.php';
			require_once POPMAKE_DIR . 'includes/class-pum-helpers.php';
			// Helper Classes
			if ( is_admin() ) {
				require_once POPMAKE_DIR . 'includes/admin/class-pum-admin-helpers.php';
			}


			require_once POPMAKE_DIR . 'includes/actions.php';
			require_once POPMAKE_DIR . 'includes/post-types.php';
			require_once POPMAKE_DIR . 'includes/class-popmake-cron.php';
			require_once POPMAKE_DIR . 'includes/scripts.php';
			require_once POPMAKE_DIR . 'includes/shortcodes.php';
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
				require_once POPMAKE_DIR . 'includes/admin/popups/metabox-themes-fields.php';

				// If not yet upgraded still show and process the old meta boxes.
				if ( ! get_site_option( 'pum_v1.4_triggers_upgraded', false ) || ! get_site_option( 'pum_v1.4_conditions_upgraded', false ) ) {
					require_once POPMAKE_DIR . 'includes/admin/popups/deprecated.php';
				}

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

				require_once POPMAKE_DIR . 'includes/admin/tracking.php';

			}

			require_once POPMAKE_DIR . 'includes/integrations/class-popmake-woocommerce-integration.php';
			require_once POPMAKE_DIR . 'includes/integrations/class-pum-woocommerce-integration.php';

			if ( defined( 'WPB_VC_VERSION' ) || defined( 'FL_BUILDER_VERSION' ) ) {
				require_once POPMAKE_DIR . 'includes/integrations/visual-composer.php';
			}
			require_once POPMAKE_DIR . 'includes/pum-install-functions.php';
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


	}

endif; // End if class_exists check

#region Freemius

// Create a helper function for easy SDK access.
function pum_fs() {
	global $pum_fs;

	if ( ! isset( $pum_fs ) ) {
		// Include Freemius SDK.
		require_once dirname( __FILE__ ) . '/includes/libraries/freemius/start.php';

		$pum_fs = fs_dynamic_init( array(
			'id'                => '147',
			'slug'              => 'popup-maker',
			'public_key'        => 'pk_0a02cbd99443e0ab7211b19222fe3',
			'is_premium'        => false,
			'has_addons'        => false,
			'has_paid_plans'    => false,
			'menu'              => array(
				'slug'       => 'edit.php?post_type=popup',
				'account'    => false,
				'contact'    => true,
				'support'    => true,
			),
		) );
	}

	return $pum_fs;
}

if ( pum_fs()->is_plugin_update() ) {
	function pum_fs_custom_connect_message( $message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link ) {

		// TODO LEFT OFF HERE. Optimize these messages and the upgrade flow.

		// TODO The upgrade flow needs to be improved. WP Update Successful -> Notices to opt in and update. Clicking update must work without optin.

		// 1. User has already opted in and is upgrading.
		if ( popmake_get_option( 'allow_tracking', false ) ) {
			return sprintf(
				__fs( 'hey-x' ) . '<br>' .
				__( 'Please help us improve %s!', 'popup-maker' ) . '<br>' .
				__( 'If you opt-in, some data about your usage of %s will be captured. If you skip this, that\'s okay! The plugin will still work just fine.', 'popup-maker' ),
				$user_first_name,
				'<b>' . $plugin_title . '</b>',
				'<b>' . $plugin_title . '</b>'
			);
		// 2. User hasn't opted in and is upgrading.
		} else {
			return sprintf(
				__fs( 'hey-x' ) . '<br>' .
				__( 'Please help us improve %s!', 'popup-maker' ) . '<br>' .
				__( 'If you opt-in, some data about your usage of %s will be captured. If you skip this, that\'s okay! The plugin will still work just fine.', 'popup-maker' ),
				$user_first_name,
				'<b>' . $plugin_title . '</b>',
				'<b>' . $plugin_title . '</b>'
			);
		}

	}
// 3. User is freshly installing.
} else {
	function pum_fs_custom_connect_message( $message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link 	) {
		return sprintf(
			__fs( 'hey-x' ) . '<br>' .
			__( 'Allow %s to track plugin usage?', 'popup-maker' ) . ' ' .
			__( 'Opt-in to tracking and our newsletter and we will immediately e-mail you a 20%% discount which you can use on any of our extensions.', 'popup-maker' ),
			$user_first_name,
			'<b>' . $plugin_title . '</b>'
		);
	}
}

pum_fs()->add_filter( 'connect_message', 'pum_fs_custom_connect_message', WP_FS__DEFAULT_PRIORITY, 6 );

function pum_fs_user_opted_in( FS_User $user ) {
//	$user->email;
//	$user->get_name();
}

pum_fs()->add_action( 'after_account_connection', 'pum_fs_user_opted_in' );

#endregion Freemius


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pum-activator.php
 */
function activate_pum() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pum-activator.php';
	PUM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pum-deactivator.php
 */
function deactivate_pum() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pum-deactivator.php';
	PUM_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pum' );
register_deactivation_hook( __FILE__, 'deactivate_pum' );


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

	// Get Popup Maker Running
	PopMake();
	do_action( 'pum_initialize' );
	do_action( 'popmake_initialize' );
}

add_action( 'plugins_loaded', 'popmake_initialize' );
