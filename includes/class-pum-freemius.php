<?php


/**
 * TODO LIST
 *
 * 6. Set up cron for tracking server to self clean expired codes
 * 7. The upgrade flow needs to be improved. WP Update Successful -> Notices to opt-in and update. Clicking update must work without optin.
 */

/**
 * Class PUM_Freemius controls the freemius integration.
 */
class PUM_Freemius {

	/**
	 * @var Popup_Maker The one true Popup_Maker
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * @var Popup_Maker The one true Popup_Maker
	 * @since 1.0
	 */
	public $fs = null;

    /**
     * @return \Popup_Maker|\PUM_Freemius
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

		if ( ! $this->fs ) {
			// Include Freemius SDK.
			require_once dirname( __FILE__ ) . '/libraries/freemius/start.php';

			$this->fs = fs_dynamic_init( array(
				'id'                => '147',
				'slug'              => 'popup-maker',
				'public_key'        => 'pk_0a02cbd99443e0ab7211b19222fe3',
				'is_premium'        => false,
				'has_addons'        => false,
				'has_paid_plans'    => false,
				'menu'              => array(
					'slug'       => 'edit.php?post_type=popup',
					'contact'    => true,
					'support'    => true,
				),
				'permissions' => array(
					'newsletter' => true,
				)
			) );
		}

		return $this->fs;
	}

    /**
     *
     */
    public function init() {

		$this->fs()->add_filter( 'is_submenu_visible', array( $this, 'menu_permissions' ), 10, 2 );

		$this->fs()->add_filter( 'connect_message', array( $this, 'custom_connect_message' ), WP_FS__DEFAULT_PRIORITY, 6 );
		$this->fs()->add_action( 'permission_list_bottom', array( $this, 'permission_list' ) );

		$this->fs()->add_action( 'after_account_connection', array( $this, 'account_connection' ), 10, 2 );
		$this->fs()->add_action( 'after_account_plan_sync', array( $this, 'plan_sync' ), 10, 2 );

	}

	/**
	 * Renders the popup maker usage statistics permission notification.
	 */
	public function permission_list() { ?>
		<li>
			<i class="dashicons dashicons-performance"></i>

			<div>
				<span><?php _e( 'Usage Statistics', 'popup-maker' ); ?></span>

				<p><?php _e( 'Popup & Theme Counts, Open Counts', 'popup-maker' ); ?></p>
			</div>
		</li><?php
	}

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
	 */
	public function custom_connect_message( $message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link ) {

		// If the user already opted in before ask them to do it again.
		if ( popmake_get_option( 'allow_tracking', false ) ) {
			$intro = __( 'We have moved our usage tracking to a new platform.', 'popup-maker' ) . '<br/><br/>' .
			         __( 'We appreciate that you chose to opt-in once before and ask that you please continue to help us improve %2$s!', 'popup-maker' ) . '<br/><br/>' .
			         __( 'If you choose to opt-in again:', 'popup-maker' );

		} else {
			$intro = __( 'Please help us improve %2$s and allow us to track plugin usage!', 'popup-maker' ) . '<br/><br/>' .
			         __( 'If you opt-in now:', 'popup-maker' );
		}

		return sprintf(
			__fs( 'hey-x' ) . '<br/><br/>' .
			$intro .
			'</p><ul style="font-size: 14px; padding-left: 18px;list-style:square;">' .
			'<li>' . __( 'Receive a code for 20%% off any purchase in our extension store.', 'popup-maker' ) . '</li>' .
			'<li>' . __( 'Submit support requests from your own dashboard and reply by email. (No more forums)', 'popup-maker' ) . '</li>' .
			'<li>' . __( 'And no sensitive data is tracked.', 'popup-maker' ) . '</li>' .
			'</ul><p>' .
			__( 'If you skip this, that\'s okay! The plugin will still work just fine.', 'popup-maker' ),
			$user_first_name,
			'<strong>' . $plugin_title . '</strong>'
		);

	}

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
                'fs_id'     => ! empty( $user->id ) ? $user->id : $user->email,
                'email'     => $user->email,
                'first'     => $user->first,
                'last'      => $user->last,
                'display'   => $user->get_name(),
                'verified'  => $user->is_verified(),
            ),
        ) );

        $this->api_call( 'new_opt_in', $args );

	}

	/**
	 * User just opted in.
	 *
	 * Forward the request to our server for discount code generation.
	 */
	public function plan_sync() {

        // Send a maximum of once per week
        if ( get_site_transient( 'pum_tracking_last_send' ) ) {
            return;
        }

        $args = $this->setup_data();

        $this->api_call( 'check_in', $args );

        set_site_transient( 'pum_tracking_last_send', 6 * DAY_IN_SECONDS + 12 * HOUR_IN_SECONDS  );
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
	 */
	function menu_permissions( $is_visible, $menu_id ) {
		if ( 'contact' === $menu_id ) {
			return pum_fs()->is_registered();
		}
		if ( 'support' === $menu_id ) {
			return ! pum_fs()->is_registered();
		}
		return $is_visible;
	}

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

        $popups  = 0;
        foreach ( wp_count_posts( 'popup' ) as $status ) {
            $popups += $status;
        }

        $popup_themes  = 0;
        foreach ( wp_count_posts( 'popup_theme' ) as $status ) {
            $popup_themes += $status;
        }


        $user = pum_fs()->get_user();

        $args = array(
            // UID
            'uid' => md5( strtolower( $user->email ) ),

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
            'open_count'       => get_site_option( 'pum_total_open_count', 0 ),
        );

        return $args;
    }


    /**
     * Send the data to the Popup Maker V2 Server
     */
    public function api_call( $action = '', $data = array() ) {
        return wp_remote_post( PUM::API_URL . $action, array(
                'method'      => 'POST',
                'timeout'     => 20,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking'    => true,
                'body'        => json_encode( $data ),
                'user-agent'  => 'POPMAKE/' . PUM::VER . '; ' . get_site_url()
        ) );
    }


}

// Create a helper function for easy SDK access.
/**
 * @return \Freemius
 */
function pum_fs() {
	return PUM_Freemius::instance()->fs();
}

