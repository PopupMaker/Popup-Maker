<?php
/**
 * Prerequisite handler.
 *
 * @package PUM\Core
 */

/**
 * Prerequisite handler.
 *
 * @version 1.0.0
 */
class PUM_Utils_Prerequisites {

	/**
	 * Cache accessible across instances.
	 *
	 * @var array
	 */
	public static $cache = [];

	/**
	 * Array of checks to perform.
	 *
	 * @var array
	 */
	protected $checks = [];

	/**
	 * Array of detected failures.
	 *
	 * @var array
	 */
	protected $failures = [];

	/**
	 * Instantiate prerequisite checker.
	 *
	 * @param array $requirements Array of requirements.
	 */
	public function __construct( $requirements = [] ) {
		foreach ( $requirements as $arguments ) {
			switch ( $arguments['type'] ) {
				case 'php':
					$this->checks[] = wp_parse_args(
						$arguments,
						[
							'type'    => 'php',
							'version' => '5.6',
						]
					);
					break;
				case 'plugin':
					$this->checks[] = wp_parse_args(
						$arguments,
						[
							'type'            => 'plugin',
							'slug'            => '',
							'name'            => '',
							'version'         => '',
							'check_installed' => false,
							'dep_label'       => '',
						]
					);
					break;
				default:
					break;
			}
		}
	}

	/**
	 * Check requirements.
	 *
	 * @param boolean $return_on_fail Whether it should stop processing if one fails.
	 *
	 * @return bool
	 */
	public function check( $return_on_fail = false ) {
		$end_result = true;

		foreach ( $this->checks as $check ) {
			$result = $this->check_handler( $check );

			if ( false === $result ) {
				if ( true === $return_on_fail ) {
					return false;
				}

				$end_result = false;
			}
		}

		return $end_result;
	}

	/**
	 * Render notices when appropriate.
	 */
	public function setup_notices() {
		add_action( 'admin_notices', [ $this, 'render_notices' ] );
	}

	/**
	 * Handle individual checks by mapping them to methods.
	 *
	 * @param array $check Requirement check arguments.
	 *
	 * @return bool
	 */
	public function check_handler( $check ) {
		return method_exists( $this, 'check_' . $check['type'] ) ? $this->{'check_' . $check['type']}( $check ) : false;
	}

	/**
	 * Report failure notice to the queue.
	 *
	 * @param array $check_args Array of check arguments.
	 */
	public function report_failure( $check_args ) {
		$this->failures[] = $check_args;
	}

	/**
	 * Get a list of failures.
	 *
	 * @return array
	 */
	public function get_failures() {
		return $this->failures;
	}

	/**
	 * Check PHP version against args.
	 *
	 * @param array $check_args Array of args.
	 *
	 * @return bool
	 */
	public function check_php( $check_args ) {
		if ( false === version_compare( phpversion(), $check_args['version'], '>=' ) ) {
			$this->report_failure( $check_args );
			return false;
		}

		return true;
	}

	/**
	 * Check plugin requirements.
	 *
	 * @param array $check_args Array of args.
	 *
	 * @return bool
	 */
	public function check_plugin( $check_args ) {
		$active = $this->plugin_is_active( $check_args['slug'] );

		/**
		 * The following checks are performed in this order for performance reasons.
		 *
		 * We start with most cached option, to least in hopes of a hit early.
		 *
		 * 1. If active and not checking version.
		 * 2. If active and outdated.
		 * 3. If not active and installed.
		 * 4. If not installed
		 */
		if ( true === $active ) {
			// If required version is set & plugin is active, check that first.
			if ( isset( $check_args['version'] ) ) {
				$version = $this->get_plugin_data( $check_args['slug'], 'Version' );

				// If its higher than the required version, we can bail now > true.
				if ( version_compare( $version, $check_args['version'], '>=' ) ) {
					return true;
				} else {
					// If not updated, report the failure and bail > false.
					$this->report_failure(
						array_merge(
							$check_args,
							[
								// Report not_updated status.
								'not_updated' => true,
							]
						)
					);
					return false;
				}
			} else {
				// If the plugin is active, with no required version, were done > true.
				return true;
			}
		}

		if ( $check_args['check_installed'] ) {
			// Check if installed, if so the plugin is not activated.
			if ( $check_args['name'] === $this->get_plugin_data( $check_args['slug'], 'Name' ) ) {
				$this->report_failure(
					array_merge(
						$check_args,
						[
							// Report not_activated status.
							'not_activated' => true,
						]
					)
				);
			} else {
				$this->report_failure(
					array_merge(
						$check_args,
						[
							// Report not_installed status.
							'not_installed' => true,
						]
					)
				);
			}
		}

		return false;
	}

	/**
	 * Internally cached get_plugin_data/get_file_data wrapper.
	 *
	 * @param string $slug Plugins `folder/file.php` slug.
	 * @param string $header Specific plugin header needed.
	 * @return mixed
	 */
	private function get_plugin_data( $slug, $header = null ) {
		if ( ! isset( static::$cache['get_plugin_data'][ $slug ] ) ) {
			$headers = \get_file_data( WP_PLUGIN_DIR . '/' . $slug, [
				'Name'    => 'Plugin Name',
				'Version' => 'Version',
			], 'plugin' );

			static::$cache['get_plugin_data'][ $slug ] = $headers;
		}

		$plugin_data = static::$cache['get_plugin_data'][ $slug ];

		if ( empty( $header ) ) {
			return $plugin_data;
		}

		return isset( $plugin_data[ $header ] ) ? $plugin_data[ $header ] : null;
	}

	/**
	 * Check if plugin is active.
	 *
	 * @param string $slug Slug to check for.
	 *
	 * @return bool
	 */
	protected function plugin_is_active( $slug ) {
		$active_plugins = get_option( 'active_plugins', [] );

		return in_array( $slug, $active_plugins, true );
	}


	/**
	 * Get php error message.
	 *
	 * @param array $failed_check_args Check arguments.
	 *
	 * @return string
	 */
	public function get_php_message( $failed_check_args ) {
		/* translators: 1. PHP Version */
		$message = __( 'This plugin requires <b>PHP %s</b> or higher in order to run.', 'popup-maker' );
		return sprintf( $message, $failed_check_args['version'] );
	}

	/**
	 * Get plugin error message.
	 *
	 * @param array $failed_check_args Get helpful error message.
	 *
	 * @return string
	 */
	public function get_plugin_message( $failed_check_args ) {
		$slug = $failed_check_args['slug'];
		// Without file path.
		$short_slug = explode( '/', $slug );
		$short_slug = $short_slug[0];
		$name       = $failed_check_args['name'];
		$dep_label  = $failed_check_args['dep_label'];

		if ( isset( $failed_check_args['not_activated'] ) ) {
			$url  = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $slug ), 'activate-plugin_' . $slug ) );
			$link = '<a href="' . $url . '">' . __( 'activate it', 'popup-maker' ) . '</a>';

			$text = sprintf(
				/* translators: 1. Plugin Name, 2. Required Plugin Name, 4. `activate it` link. */
				__( 'The plugin "%1$s" requires %2$s! Please %3$s to continue!', 'popup-maker' ),
				$dep_label,
				'<strong>' . $name . '</strong>',
				$link
			);
		} elseif ( isset( $failed_check_args['not_updated'] ) ) {
			$url  = esc_url( wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=' . $slug ), 'upgrade-plugin_' . $slug ) );
			$link = '<a href="' . $url . '">' . __( 'update it', 'popup-maker' ) . '</a>';

			$text = sprintf(
				/* translators: 1. Plugin Name, 2. Required Plugin Name, 3. Version number, 4. `update it` link. */
				__( 'The plugin "%1$s" requires %2$s v%3$s or higher! Please %4$s to continue!', 'popup-maker' ),
				$dep_label,
				'<strong>' . $name . '</strong>',
				'<strong>' . $failed_check_args['version'] . '</strong>',
				$link
			);
		} else {
			$url  = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $short_slug ), 'install-plugin_' . $short_slug ) );
			$link = '<a href="' . $url . '">' . __( 'install it', 'popup-maker' ) . '</a>';

			$text = sprintf(
				/* translators: 1. Plugin Name, 2. Required Plugin Name, 3. `install it` link. */
				__( 'The plugin "%1$s" requires %2$s! Please %3$s to continue!', 'popup-maker' ),
				$dep_label,
				'<strong>' . $name . '</strong>',
				$link
			);
		}

		return $text;
	}

	/**
	 * Render needed admin notices.
	 *
	 * @return void
	 */
	public function render_notices() {
		foreach ( $this->failures as $failure ) {
			$class   = 'notice notice-error';
			$message = method_exists( $this, 'get_' . $failure['type'] . '_message' ) ? $this->{'get_' . $failure['type'] . '_message'}( $failure ) : false;

			/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
	}
}
