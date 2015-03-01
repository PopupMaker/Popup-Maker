<?php
/**
 * License handler for Popup Maker
 *
 * This class should simplify the process of adding license information to new Popup Maker extensions.
 * 
 * Note for wordpress.org admins. This is not called in the free hosted version and is simply used for hooking in addons to one update system rather than including it in each plugin.
 * @version 1.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PopMake_License' ) ) :

/**
 * PopMake_License Class
 */
class PopMake_License {
	private $file;
	private $license;
	private $item_name;
	private $item_shortname;
	private $version;
	private $author;
	private $api_url = 'https://wppopupmaker.com/edd-sl-api/';

	/**
	 * Class constructor
	 *
	 * @param string  $_file
	 * @param string  $_item_name
	 * @param string  $_version
	 * @param string  $_author
	 * @param string  $_api_url
	 */
	function __construct( $_file, $_item_name, $_version, $_author, $_api_url = null ) {
		$this->file           = $_file;
		$this->item_name      = $_item_name;
		$this->item_shortname = 'popmake_' . preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
		$this->version        = $_version;
		$this->license        = trim( popmake_get_option( $this->item_shortname . '_license_key', '' ) );
		$this->author         = $_author;
		$this->api_url        = is_null( $_api_url ) ? $this->api_url : $_api_url;

		// Setup hooks
		$this->includes();
		$this->hooks();
	}

	/**
	 * Include the updater class
	 *
	 * @access  private
	 * @return  void
	 */
	private function includes() {
		if ( ! class_exists( 'PopumpMaker_Plugin_Updater' ) ) require_once 'plugin-updater.php';
	}

	/**
	 * Setup hooks
	 *
	 * @access  private
	 * @return  void
	 */
	private function hooks() {
		// Register settings
		add_filter( 'popmake_settings_licenses', array( $this, 'settings' ), 1 );

		// Activate license key on settings save
		add_action( 'admin_init', array( $this, 'activate_license' ) );

		// Deactivate license key
		add_action( 'admin_init', array( $this, 'deactivate_license' ) );

		// Updater
		add_action( 'admin_init', array( $this, 'auto_updater' ), 1000 );
	}

	/**
	 * Auto updater
	 *
	 * @access  private
	 * @return  void
	 */
	public function auto_updater() {

		if ( 'valid' !== get_option( $this->item_shortname . '_license_active' ) )
			return;

		// Setup the updater
		$popmake_updater = new PopumpMaker_Plugin_Updater(
			$this->api_url,
			$this->file,
			array(
				'version'   => $this->version,
				'license'   => $this->license,
				'item_name' => $this->item_name,
				'author'    => $this->author
			)
		);
	}


	/**
	 * Add license field to settings
	 *
	 * @access  public
	 * @param array   $settings
	 * @return  array
	 */
	public function settings( $settings ) {
		$popmake_license_settings = array(
			$this->item_shortname . '_license_key' => array(
				'id'      => $this->item_shortname . '_license_key',
				'name'    => sprintf( __( '%1$s License Key', 'popmake' ), $this->item_name ),
				'desc'    => '',
				'type'    => 'license_key',
				'options' => array( 'is_valid_license_option' => $this->item_shortname . '_license_active' ),
				'size'    => 'regular'
			)
		);

		return array_merge( $settings, $popmake_license_settings );
	}


	/**
	 * Activate the license key
	 *
	 * @access  public
	 * @return  void
	 */
	public function activate_license() {

		if ( ! isset( $_POST['popmake_settings'] ) ) {
			return;
		}
		if ( ! isset( $_POST['popmake_settings'][ $this->item_shortname . '_license_key' ] ) ) {
			return;
		}

		foreach( $_POST as $key => $value ) {
			if( false !== strpos( $key, 'license_key_deactivate' ) ) {
				// Don't activate a key when deactivating a different key
				return;
			}
		}

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( 'valid' == get_option( $this->item_shortname . '_license_active' ) ) {
			return;
		}

		$license = sanitize_text_field( $_POST['popmake_settings'][ $this->item_shortname . '_license_key' ] );

		// Data to send to the API
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( $this->item_name ),
			'url'        => home_url()
		);

		// Call the API
		$response = wp_remote_post(
			$this->api_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			)
		);
		//echo '<pre>'; print_R( $response ); echo '</pre>'; exit;

		// Make sure there are no errors
		if ( is_wp_error( $response ) )
			return;

		// Tell WordPress to look for updates
		set_site_transient( 'update_plugins', null );

		// Decode license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		update_option( $this->item_shortname . '_license_active', $license_data->license );
	}


	/**
	 * Deactivate the license key
	 *
	 * @access  public
	 * @return  void
	 */
	public function deactivate_license() {

		if ( ! isset( $_POST['popmake_settings'] ) )
			return;

		if ( ! isset( $_POST['popmake_settings'][ $this->item_shortname . '_license_key' ] ) )
			return;

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Run on deactivate button press
		if ( isset( $_POST[ $this->item_shortname . '_license_key_deactivate' ] ) ) {

			// Data to send to the API
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $this->license,
				'item_name'  => urlencode( $this->item_name ),
				'url'        => home_url()
			);

			// Call the API
			$response = wp_remote_post(
				$this->api_url,
				array(
					'timeout'   => 15,
					'sslverify' => false,
					'body'      => $api_params
				)
			);

			// Make sure there are no errors
			if ( is_wp_error( $response ) )
				return;

			// Decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			delete_option( $this->item_shortname . '_license_active' );
		}
	}
}
endif; // end class_exists check