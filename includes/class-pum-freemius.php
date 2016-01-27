<?php


/**
 * TODO LIST
 *
 * 2. Add custom permission hook and content for tracking popup stats.
 * 3. Add call to custom tracking server on successful activation of site for new discount code.
 * 4. Add call to custom tracking server on weekly ping
 * 5. Update tracking server to work with both old and new.
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

	public function init() {

		$this->fs()->add_filter( 'connect_message', array( $this, 'custom_connect_message' ), WP_FS__DEFAULT_PRIORITY, 6 );
		$this->fs()->add_action( 'after_account_connection', array( $this, 'user_opted_in' ) );
		$this->fs()->add_filter('is_submenu_visible', array( $this, 'menu_permissions' ), 10, 2);

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
	 * @param \FS_User $user
	 */
	public function user_opted_in( FS_User $user ) {
		//	$user->email;
		//	$user->get_name();
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




}

// Create a helper function for easy SDK access.
function pum_fs() {
	return PUM_Freemius::instance()->fs();
}

