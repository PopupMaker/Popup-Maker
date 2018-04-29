<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Popup Maker Extension Activation Handler Class
 *
 * @since       1.0.0
 */
class PUM_Extension_Activator {

	public $extension_class_name;

	/**
	 * @var string
	 */
	public $extension_name;

	/**
	 * @var string
	 */
	public $extension_slug;

	public $required_core_version;

	/**
	 * @var bool
	 */
	public $core_installed = false;

	public $core_path;

	/**
	 * Setup the activator class
	 *
	 * @param  $class_name
	 */
	public function __construct( $class_name ) {
		// We need plugin.php!
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Validate extension class is valid.
		if ( in_array( false, array(
			class_exists( $class_name ),
			property_exists( $class_name, 'REQUIRED_CORE_VER' ),
			property_exists( $class_name, 'NAME' ),
			method_exists( $class_name, 'instance' ),
		) ) ) {
			return;
		}

		$this->extension_class_name  = $class_name;
		$this->extension_name        = $class_name::$NAME;
		$this->required_core_version = $class_name::$REQUIRED_CORE_VER;

		$plugins = get_plugins();

		// Is Popup Maker installed?
		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( $plugin['Name'] == 'Popup Maker' ) {
				$this->core_installed = true;
				$this->core_path      = $plugin_path;
				break;
			}
		}
	}

	/**
	 * @return string
	 */
	public function get_status() {
		if ( $this->core_installed && ! class_exists( 'Popup_Maker' ) ) {
			return 'not_activated';
		} elseif ( $this->core_installed && isset( $this->required_core_version ) && version_compare( Popup_Maker::$VER, $this->required_core_version, '<' ) ) {
			return 'not_updated';
		} elseif ( ! $this->core_installed ) {
			return 'not_installed';
		}

		return 'active';
	}


	/**
	 * Process plugin deactivation
	 *
	 * @access      public
	 */
	public function run() {
		if ( $this->get_status() != 'active' ) {
			// Display notice
			add_action( 'admin_notices', array( $this, 'missing_popmake_notice' ) );
		} else {
			$class_name = $this->extension_class_name;
			$class_name::instance();

			$plugin_slug          = explode( '/', plugin_basename( $class_name::$FILE ), 2 );
			$this->extension_slug = str_replace( array( 'popup-maker-', 'pum-' ), '', $plugin_slug[0] );

			// Handle licensing
			if ( class_exists( 'PUM_Extension_License' ) ) {
				new PUM_Extension_License( $class_name::$FILE, $class_name::$NAME, $class_name::$VER, 'WP Popup Maker', null, null, $class_name::$ID );
			}

			add_filter( 'pum_enabled_extensions', array( $this, 'enabled_extensions' ) );
		}
	}


	/**
	 * Display notice if Popup Maker isn't installed
	 */
	public function missing_popmake_notice() {
		switch ( $this->get_status() ) {
			case 'not_activated':
				$url  = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $this->core_path ), 'activate-plugin_' . $this->core_path ) );
				$link = '<a href="' . $url . '">' . __( 'activate it' ) . '</a>';
				echo '<div class="error"><p>' . $this->extension_name . sprintf( __( ' requires Popup Maker! Please %s to continue!' ), $link ) . '</p></div>';

				break;
			case 'not_updated':
				$url  = esc_url( wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=' . $this->core_path ), 'upgrade-plugin_' . $this->core_path ) );
				$link = '<a href="' . $url . '">' . __( 'update it' ) . '</a>';
				echo '<div class="error"><p>' . $this->extension_name . sprintf( __( ' requires Popup Maker v%s or higher! Please %s to continue!' ), $this->required_core_version, $link ) . '</p></div>';

				break;
			case 'not_installed':
				$url  = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=popup-maker' ), 'install-plugin_popup-maker' ) );
				$link = '<a href="' . $url . '">' . __( 'install it' ) . '</a>';
				echo '<div class="error"><p>' . $this->extension_name . sprintf( __( ' requires Popup Maker! Please %s to continue!' ), $link ) . '</p></div>';

				break;
			case 'active':
			default:
				return;
		}
	}

	/**
	 * @param array $enabled_extensions
	 *
	 * @return array
	 */
	public function enabled_extensions( $enabled_extensions = array() ) {
		$enabled_extensions[ $this->extension_slug ] = $this->extension_class_name;

		return $enabled_extensions;
	}
}
