<?php
/**
 * Extension Activator Handler
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Popup Maker Extension Activation Handler Class
 *
 * @version       2.1
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

	/**
	 * @var int
	 */
	public $extension_id;

	/**
	 * @var string
	 */
	public $extension_version;

	/**
	 * @var bool|string
	 */
	public $extension_wp_repo = false;

	/**
	 * @var string
	 */
	public $extension_file;

	public $required_core_version;

	/**
	 * @var bool
	 */
	public $core_installed = false;

	public $core_path;

	/**
	 * @param $class_name
	 * @param $prop_name
	 *
	 * @return null|mixed
	 */
	public function get_static_prop( $class_name, $prop_name ) {
		if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			try {
				$class = new ReflectionClass( $class_name );

				return $class->getStaticPropertyValue( $prop_name );
			} catch ( ReflectionException $e ) {
				return null;
			}
		}

		return property_exists( $class_name, $prop_name ) ? $class_name::$$prop_name : null;
	}

	/**
	 * Setup the activator class
	 *
	 * @param  $class_name
	 */
	public function __construct( $class_name ) {
		// We need plugin.php!
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Validate extension class is valid.
		if ( in_array(
			false,
			[
				class_exists( $class_name ),
				property_exists( $class_name, 'NAME' ),
				property_exists( $class_name, 'REQUIRED_CORE_VER' ),
				method_exists( $class_name, 'instance' ),
			]
		) ) {
			return;
		}

		$this->extension_class_name  = $class_name;
		$this->extension_id          = $this->get_static_prop( $class_name, 'ID' );
		$this->extension_wp_repo     = $this->get_static_prop( $class_name, 'WP_REPO' );
		$this->extension_name        = $this->get_static_prop( $class_name, 'NAME' );
		$this->extension_version     = $this->get_static_prop( $class_name, 'VER' );
		$this->required_core_version = $this->get_static_prop( $class_name, 'REQUIRED_CORE_VER' );

		$popup_maker_data = get_plugin_data( WP_PLUGIN_DIR . '/popup-maker/popup-maker.php', false, false );

		if ( 'Popup Maker' === $popup_maker_data['Name'] ) {
			$this->core_installed = true;
			$this->core_path      = 'popup-maker/popup-maker.php';
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
		if ( $this->get_status() !== 'active' ) {
			// Display notice
			add_action( 'admin_notices', [ $this, 'missing_popmake_notice' ] );
		} else {
			$class_name = $this->extension_class_name;

			// Generate an instance of the extension class in a PHP 5.2 compatible way.
			call_user_func( [ $class_name, 'instance' ] );

			$this->extension_file = $this->get_static_prop( $class_name, 'FILE' );

			$plugin_slug          = explode( '/', plugin_basename( $this->extension_file ), 2 );
			$this->extension_slug = str_replace( [ 'popup-maker-', 'pum-' ], '', $plugin_slug[0] );

			// Handle licensing for extensions with valid ID & not wp repo extensions.
			if ( $this->extension_id > 0 && ! $this->extension_wp_repo && class_exists( 'PUM_Extension_License' ) ) {
				new PUM_Extension_License( $this->extension_file, $this->extension_name, $this->extension_version, 'Popup Maker', null, null, $this->extension_id );
			}

			add_filter( 'pum_enabled_extensions', [ $this, 'enabled_extensions' ] );
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
				echo '<div class="error"><p>' . sprintf( __( 'The plugin "%1$s" requires %2$s! Please %3$s to continue!' ), $this->extension_name, '<strong>' . __( 'Popup Maker' ) . '</strong>', $link ) . '</p></div>';

				break;
			case 'not_updated':
				$url  = esc_url( wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=' . $this->core_path ), 'upgrade-plugin_' . $this->core_path ) );
				$link = '<a href="' . $url . '">' . __( 'update it' ) . '</a>';
				echo '<div class="error"><p>' . sprintf( __( 'The plugin "%1$s" requires %2$s v%3$s or higher! Please %4$s to continue!' ), $this->extension_name, '<strong>' . __( 'Popup Maker' ) . '</strong>', '<strong>' . $this->required_core_version . '</strong>', $link ) . '</p></div>';

				break;
			case 'not_installed':
				$url  = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=popup-maker' ), 'install-plugin_popup-maker' ) );
				$link = '<a href="' . $url . '">' . __( 'install it' ) . '</a>';
				echo '<div class="error"><p>' . sprintf( __( 'The plugin "%1$s" requires %2$s! Please %3$s to continue!' ), $this->extension_name, '<strong>' . __( 'Popup Maker' ) . '</strong>', $link ) . '</p></div>';

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
	public function enabled_extensions( $enabled_extensions = [] ) {
		$enabled_extensions[ $this->extension_slug ] = $this->extension_class_name;

		return $enabled_extensions;
	}
}
