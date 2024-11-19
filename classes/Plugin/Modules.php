<?php
/**
 * Module management system.
 *
 * @package PopupMaker\Plugin
 * @since X.X.X
 */

namespace PopupMaker\Plugin;

use function PopupMaker\plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles module management.
 */
class Modules {

	/**
	 * Registered modules.
	 *
	 * @var array<string,array>
	 */
	private $modules = [];

	/**
	 * Enabled modules.
	 *
	 * @var array<string,bool>
	 */
	private $enabled_modules = [];

	/**
	 * Initialize the module system.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'plugins_loaded', [ $this, 'register_modules' ], 998 );
		add_action( 'plugins_loaded', [ $this, 'load_enabled_modules' ], 999 );
		add_action( 'plugins_loaded', [ $this, 'run_enabled_modules' ], 1000 );
		add_filter( 'pum_settings_tabs', [ $this, 'register_settings_tabs' ] );
		add_filter( 'pum_settings_tab_sections', [ $this, 'register_settings_tab_sections' ] );
		add_filter( 'pum_settings_fields', [ $this, 'register_settings_fields' ] );
	}

	/**
	 * Register available modules.
	 *
	 * @return void
	 */
	public function register_modules(): void {
		/**
		 * Filter registered module paths.
		 *
		 * @param array<string,array{forced:bool,path:string}> $module_paths Array of module ID => module config pairs.
		 */
		$module_paths = apply_filters( 'popup_maker/registered_modules', [] );

		foreach ( $module_paths as $id => $module ) {
			$module_file = trailingslashit( $module['path'] ) . 'bootstrap.php';

			if ( ! file_exists( $module_file ) ) {
				continue;
			}

			$header = $this->get_module_header( $module['path'] );

			// Skip if required header data is missing.
			if ( empty( $header['id'] ) || empty( $header['name'] ) || empty( $header['class'] ) ) {
				continue;
			}

			// Verify module ID matches the registered key.
			if ( $header['id'] !== $id ) {
				continue;
			}

			$this->modules[ $id ] = wp_parse_args( $header, [
				'id'          => '',
				'name'        => '',
				'description' => '',
				'version'     => '1.0.0',
				'class'       => '',
				'path'        => '',
				'forced'      => $module['forced'],
				'hidden'      => $module['hidden'],
			] );
		}

		/**
		 * Filter registered modules after header parsing.
		 *
		 * @param array<string,array> $modules Array of parsed module data.
		 */
		$this->modules = apply_filters( 'pum_pro_registered_modules', $this->modules );
	}

	/**
	 * Get module header data.
	 *
	 * @param string $module_path Module file path.
	 *
	 * @return array<string,string>|false
	 */
	private function get_module_header( string $module_path ): array|false {
		$default_headers = [
			'id'          => 'Module ID',
			'name'        => 'Module Name',
			'version'     => 'Version',
			'description' => 'Description',
			'class'       => 'Module Class',
			'author'      => 'Author',
			'author_uri'  => 'Author URI',
			'module_uri'  => 'Module URI',
			'license'     => 'License',
			'license_uri' => 'License URI',
		];

		$base_path = wp_unslash( $module_path );
		$headers   = get_file_data( "$base_path/bootstrap.php", $default_headers, 'pum_module' );

		if ( ! empty( $headers['id'] ) ) {
			return $headers;
		}

		return false;
	}

	/**
	 * Determine if a module should be loaded.
	 *
	 * @param array $module Module data.
	 * @return bool
	 */
	public function should_load_module( array $module ): bool {
		if ( isset( $module['forced'] ) && $module['forced'] ) {
			return true;
		}

		return \pum_get_option( "module_{$module['id']}_enabled", false );
	}

	/**
	 * Load enabled modules.
	 *
	 * @return void
	 */
	public function load_enabled_modules(): void {
		foreach ( $this->modules as $id => $module ) {
			if ( ! $this->should_load_module( $module ) ) {
				continue;
			}

			require_once $module['file'];

			if ( ! empty( $module['class'] ) && class_exists( $module['class'] ) ) {
				$instance = new $module['class']();

				if ( method_exists( $instance, 'setup' ) ) {
					$instance->setup();
				}
			}
		}
	}

	/**
	 * Run enabled modules.
	 *
	 * @return void
	 */
	public function run_enabled_modules(): void {
		/**
		 * Action triggered after all enabled modules are loaded.
		 *
		 * @param Modules $this The Modules instance.
		 */
		do_action( 'popup_maker/modules_loaded', $this );
	}

	/**
	 * Add modules tab to Popup Maker settings page.
	 *
	 * @param array $tabs Settings tabs.
	 * @return array
	 */
	public function register_settings_tabs( $tabs ) {
		$tabs['modules'] = __( 'Pro Modules', 'popup-maker' );
		return $tabs;
	}

	/**
	 * Add modules section to Popup Maker settings page.
	 *
	 * @param array $sections Settings sections.
	 * @return array
	 */
	public function register_settings_tab_sections( $sections ) {
		$sections['modules'] = __( 'Module Settings', 'popup-maker' );
		return $sections;
	}

	/**
	 * Add module settings to Popup Maker settings page.
	 *
	 * @param array $fields Settings fields.
	 * @return array
	 */
	public function register_settings_fields( array $fields ): array {
		foreach ( $this->modules as $id => $module ) {
			// Skip forced modules in settings.
			if ( isset( $module['hidden'] ) && $module['hidden'] ) {
				continue;
			}

			$fields['modules']['modules'][ 'module_' . $id . '_enabled' ] = [
				'id'       => 'module_' . $id . '_enabled',
				'type'     => 'checkbox',
				'label'    => $module['name'],
				'desc'     => $module['description'],
				'disabled' => $module['forced'],
				'priority' => 10,
				'checked'  => $module['forced'] ? true : false,
			];
		}

		return $fields;
	}

	/**
	 * Sanitize enabled modules.
	 *
	 * @param array $modules Enabled modules.
	 * @return array
	 */
	public function sanitize_enabled_modules( $modules ): array {
		if ( ! is_array( $modules ) ) {
			return [];
		}

		return array_map( 'boolval', $modules );
	}

	/**
	 * Get all available modules.
	 *
	 * @return array<string,array>
	 */
	public function get_modules(): array {
		return $this->modules;
	}

	/**
	 * Get enabled modules.
	 *
	 * @return array<string,bool>
	 */
	public function get_enabled_modules(): array {
		return $this->enabled_modules;
	}

	/**
	 * Check if a module is enabled.
	 *
	 * @param string $module_id Module ID.
	 * @return bool
	 */
	public function is_module_enabled( string $module_id ): bool {
		return ! empty( $this->enabled_modules[ $module_id ] );
	}

	/**
	 * Get a specific module.
	 *
	 * @param string $module_id Module ID.
	 * @return array
	 * @throws \Exception If module not found.
	 */
	public function get_module( string $module_id ): array {
		if ( ! isset( $this->modules[ $module_id ] ) ) {
			throw new \Exception( sprintf( 'Module "%s" not found.', esc_html( $module_id ) ) );
		}

		return $this->modules[ $module_id ];
	}
}
