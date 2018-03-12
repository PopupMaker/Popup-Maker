<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Freemius controls the freemius integration.
 */
class PUM_Freemius {

	/**
	 * @var \PUM_Freemius
	 */
	private static $instance;

	/**
	 * @var \Freemius $fs
	 */
	public $fs;

	/**
	 * @return \PUM_Freemius
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof PUM_Freemius ) ) {
			self::$instance = new PUM_Freemius;

			// Initialize Freemius
			self::$instance->fs();

			// Add customizations.
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Returns the Popup Maker instance of Freemius.
	 *
	 * @return \Freemius
	 */
	public function fs() {

		if ( ! isset( $this->fs ) ) {
			// Include Freemius SDK.
			require_once Popup_Maker::$DIR . 'includes/pum-sdk/freemius/start.php';

			$this->fs = fs_dynamic_init( array(
				'id'             => '147',
				'slug'           => 'popup-maker',
				'public_key'     => 'pk_0a02cbd99443e0ab7211b19222fe3',
				'is_premium'     => false,
				'has_addons'     => false,
				'has_paid_plans' => false,
				'permissions'    => array(
					'newsletter' => true,
				),
				'menu'           => array(
					'slug'    => 'edit.php?post_type=popup',
					'contact' => false,
					'account' => false,
					'support' => false,
				),
			) );
		}


		return $this->fs;
	}

	/**
	 *
	 */
	public function init() {
		//$this->fs()->add_filter( 'is_submenu_visible', array( $this, 'menu_permissions' ), 10, 2 );
		//$this->fs()->add_filter( 'connect_message', array( $this, 'custom_connect_message' ), WP_FS__DEFAULT_PRIORITY, 6 );
		//$this->fs()->add_filter( 'permission_list', array( $this, 'permission_list' ) );
		// API Synchronization.
		$this->fs()->add_action( 'after_account_connection', array( $this, 'account_connection' ), 10, 2 );
		$this->fs()->add_action( 'after_sync_cron', array( $this, 'plan_sync' ), 10, 2 );
	}


	/**
	 * Renders the popup maker usage statistics permission notification.
	 *
	public function permission_list( $permissions = array() ) {
		$permissions['metrics'] = array(
			'icon-class'    => 'dashicons dashicons-performance',
			'label'         => __( 'Usage Statistics', 'popup-maker' ),
			'desc'          => __( 'Popup & Theme Counts, Open Counts', 'popup-maker' ),
			'priority'      => 25,
		);

		return $permissions;
	}
	*/

	/**
	 * Filters the optin activation screen messaging.
	 *
	 * @param $message
	 * @param $user_first_name
	 * @param $plugin_title
	 * @param $user_login
	 * @param $site_link
	 * @param $freemius_link
	 *
	 * @return string
	 *
	public function custom_connect_message( $message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link ) {

		$intro = __fs( 'hey-x' ) . '<br/><br/>';

		$intro .= __( 'Allow %3$sPopup Maker%4$s​ to collect some usage data with %2$s to make the plugin even more awesome. If you skip this, that\'s okay! ​%3$sPopup Maker%4$s will still work just fine.', 'popup-maker' );

		return sprintf( $intro, $user_first_name, '<a href="https://freemius.com/wordpress/insights/">Freemius</a>', '<strong>', '</strong>' );

	}
	*/

	/**
	 * User just opted in.
	 *
	 * Forward the request to our server for discount code generation.
	 *
	 * @see https://github.com/PopupMaker/tracking-server
	 *
	 * @param \FS_User $user
	 */
	public function account_connection( FS_User $user ) {

		$args = array_merge( $this->setup_data(), array(
			/*
			 * Opt-in Info.
			 *
			 * Privacy Notices: None of this user info is
			 * stored. It is passed directly to our mailing
			 * list provider and then disposed.
			 *
			 * Our server side tracking API is open source.
			 * @see https://github.com/PopupMaker/tracking-server
			 */
			'user' => array(
				'fs_id'    => ! empty( $user->id ) ? $user->id : $user->email,
				'email'    => $user->email,
				'first'    => $user->first,
				'last'     => $user->last,
				'display'  => $user->get_name(),
				'verified' => $user->is_verified(),
			),
		) );

		$this->api_call( 'new_opt_in', $args );

		set_transient( 'pum_tracking_last_send', true, 3 * DAY_IN_SECONDS + 12 * HOUR_IN_SECONDS );
	}

	/**
	 * Sync tracking data with freemius.
	 */
	public function plan_sync() {

		// Send a maximum of once per week
		if ( get_transient( 'pum_tracking_last_send' ) ) {
			return;
		}

		$args = $this->setup_data();

		$this->api_call( 'check_in', $args );

		// Twice per week.
		set_transient( 'pum_tracking_last_send', true, 3 * DAY_IN_SECONDS + 12 * HOUR_IN_SECONDS );
	}

	/**
	 * @return bool
	 */
	public function is_localhost() {

		if ( defined( 'WP_FS__IS_LOCALHOST_FOR_SERVER' ) ) {
			return WP_FS__IS_LOCALHOST_FOR_SERVER;
		}

		$url = network_site_url( '/' );

		return stristr( $url, 'dev' ) !== false || stristr( $url, 'localhost' ) !== false || stristr( $url, ':8888' ) !== false;

	}



	/**
	 * Determine which freemius menu items appear.
	 *
	 * If the user is registered they can submit support requests.
	 * Otherwise they can use the support forums.
	 *
	 * @param $is_visible
	 * @param $menu_id
	 *
	 * @return bool
	 *
	public function menu_permissions( $is_visible, $menu_id ) {
		if ( 'contact' === $menu_id ) {
			return $this->fs->is_registered();
		}
		if ( 'support' === $menu_id ) {
			return ! $this->fs->is_registered();
		}
		return $is_visible;
	}
	*/

	/**
	 * @return array
	 */
	public function setup_data() {
		global $wpdb;

		// Retrieve current theme info
		if ( get_bloginfo( 'version' ) < '3.4' ) {
			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
		} else {
			$theme_data = wp_get_theme();
			$theme      = $theme_data->Name . ' ' . $theme_data->Version;
		}

		// Retrieve current plugin information
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        = array_keys( get_plugins() );
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $key => $plugin ) {
			if ( in_array( $plugin, $active_plugins ) ) {
				// Remove active plugins from list so we can show active and inactive separately
				unset( $plugins[ $key ] );
			}
		}

		$popups = 0;
		foreach ( wp_count_posts( 'popup' ) as $status ) {
			$popups += $status;
		}

		$popup_themes = 0;
		foreach ( wp_count_posts( 'popup_theme' ) as $status ) {
			$popup_themes += $status;
		}

		$user = PUM_Freemius::instance()->fs->get_user();

		$args = array(
			// UID
			'uid'              => md5( strtolower( ! empty( $user->email ) ? $user->email : '' ) ),

			// Language Info
			'language'         => get_bloginfo( 'language' ), // Language
			'charset'          => get_bloginfo( 'charset' ), // Character Set

			// Server Info
			'php_version'      => phpversion(),
			'mysql_version'    => $wpdb->db_version(),
			'is_localhost'     => $this->is_localhost(),

			// WP Install Info
			'url'              => get_site_url(),
			'version'          => PUM::VER, // Plugin Version
			'wp_version'       => get_bloginfo( 'version' ), // WP Version
			'theme'            => $theme,
			'active_plugins'   => $active_plugins,
			'inactive_plugins' => array_values( $plugins ),

			// Popup Metrics
			'popups'           => $popups,
			'popup_themes'     => $popup_themes,
			'open_count'       => get_option( 'pum_total_open_count', 0 ),
		);

		return $args;
	}


	/**
	 * Send the data to the Popup Maker V2 Server
	 *
	 * @param string $action
	 * @param array $data
	 *
	 * @return array|WP_Error
	 */
	public function api_call( $action = '', $data = array() ) {

		$response = wp_remote_post( 'https://api.wppopupmaker.com/wp-json/pmapi/v1/' . $action, array(
			'method'      => 'POST',
			'timeout'     => 20,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => false,
			'body'        => $data,
			'user-agent'  => 'POPMAKE/' . PUM::VER . '; ' . get_site_url(),
		) );

		return $response;
	}


}
