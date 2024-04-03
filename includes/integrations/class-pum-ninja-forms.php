<?php
/**
 * Integrations for ninja-forms
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NF_PUM
 */
final class NF_PUM {

	const PREFIX = 'NF_PUM';

	/**
	 * @var NF_PUM
	 * @since 3.0
	 */
	private static $instance;

	/**
	 * Plugin Directory
	 *
	 * @since 3.0
	 * @var string $dir
	 */
	public static $dir = '';

	/**
	 * Plugin URL
	 *
	 * @since 3.0
	 * @var string $url
	 */
	public static $url = '';

	/**
	 * Main Plugin Instance
	 *
	 * Insures that only one instance of a plugin class exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 3.0
	 * @static
	 * @static var array $instance
	 * @return NF_PUM Highlander Instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof NF_PUM ) ) {
			spl_autoload_register( [ __CLASS__, 'autoloader' ] );

			self::$instance = new NF_PUM();

			self::$dir = plugin_dir_path( __FILE__ );

			self::$url = plugin_dir_url( __FILE__ );
		}

		return self::$instance;
	}

	public function __construct() {
		$this->register_actions();
		add_filter( 'pum_registered_cookies', [ $this, 'register_cookies' ] );
	}

	/**
	 * Register Actions
	 *
	 * Register custom form actions to integrate with popups.
	 */
	public function register_actions() {
		Ninja_Forms()->actions['closepopup'] = new NF_PUM_Actions_ClosePopup();
		Ninja_Forms()->actions['openpopup']  = new NF_PUM_Actions_OpenPopup();
	}


	/**
	 * Optional. If your extension creates a new field interaction or display template...
	 */
	public function register_cookies( $cookies ) {
		$cookies['ninja_form_success'] = [
			'labels' => [
				'name' => __( 'Ninja Form Success (deprecated. Use Form Submission instead.)', 'popup-maker' ),
			],
			'fields' => pum_get_cookie_fields(),
		];

		return $cookies;
	}



	/*
	 * Optional methods for convenience.
	 */

	public static function autoloader( $class_name ) {
		if ( class_exists( $class_name ) ) {
			return;
		}

		if ( false === strpos( $class_name, self::PREFIX ) ) {
			return;
		}

		$class_name  = str_replace( self::PREFIX, '', $class_name );
		$classes_dir = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'ninja-forms' . DIRECTORY_SEPARATOR;
		$class_file  = str_replace( '_', DIRECTORY_SEPARATOR, $class_name ) . '.php';

		if ( file_exists( $classes_dir . $class_file ) ) {
			require_once $classes_dir . $class_file;
		}
	}

	/**
	 * Template
	 *
	 * @param string $file_name
	 * @param array  $data
	 */
	public static function template( $file_name = '', array $data = [] ) {
		if ( ! $file_name ) {
			return;
		}

		extract( $data );

		include self::$dir . 'includes/Templates/' . $file_name;
	}

	/**
	 * Config
	 *
	 * @param $file_name
	 *
	 * @return mixed
	 */
	public static function config( $file_name ) {
		return include self::$dir . 'includes/Config/' . $file_name . '.php';
	}
}

/**
 * The main function responsible for returning The Highlander Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 3.0
 * @return {class} Highlander Instance
 */
function NF_PUM() {
	return NF_PUM::instance();
}

/**
 *
 */
function pum_nf_should_init() {
	if ( ! class_exists( 'Ninja_Forms' ) ) {
		return;
	}

	if ( version_compare( get_option( 'ninja_forms_version', '0.0.0' ), '3.0', '<' ) || get_option( 'ninja_forms_load_deprecated', false ) ) {
		return;
	}

	NF_PUM();
}

add_action( 'init', 'pum_nf_should_init', 0 );
