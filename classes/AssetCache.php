<?php
/**
 * AssestCache class
 *
 * @package   PUM
 * @copyright Copyright (c) 2023, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AssetCache class.
 */
class PUM_AssetCache {

	/**
	 * Cache directory.
	 *
	 * @var string
	 */
	public static $cache_dir;

	/**
	 * Suffix for minified assets.
	 *
	 * @var string
	 */
	public static $suffix;

	/**
	 * Asset url.
	 *
	 * @var string
	 */
	public static $asset_url;

	/**
	 * JS Url.
	 *
	 * @var string
	 */
	public static $js_url;

	/**
	 * CSS Url.
	 *
	 * @var string
	 */
	public static $css_url;

	/**
	 * Should we disable asset caching?
	 *
	 * @var bool
	 */
	public static $disabled = true;

	/**
	 * Should we output debug information?
	 *
	 * @var bool
	 */
	public static $debug;

	/**
	 * Check to see if the cache has been initialized.
	 *
	 * @var boolean
	 */
	public static $initialized = false;

	/**
	 * Initialize asset cache.
	 */
	public static function init() {
		if ( ! self::$initialized ) {
			self::$cache_dir = self::get_cache_dir();
			self::$debug     = Popup_Maker::debug_mode() || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
			self::$suffix    = self::$debug ? '' : '.min';
			self::$asset_url = Popup_Maker::$URL . 'assets/';
			self::$js_url    = self::$asset_url . 'js/';
			self::$css_url   = self::$asset_url . 'css/';
			if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
				self::$disabled = true;
			} else {
				self::$disabled = pum_get_option( 'disable_asset_caching', false );
			}

			add_action( 'pum_extension_updated', [ __CLASS__, 'reset_cache' ] );
			add_action( 'pum_extension_deactivated', [ __CLASS__, 'reset_cache' ] );
			add_action( 'pum_extension_activated', [ __CLASS__, 'reset_cache' ] );
			add_action( 'pum_regenerate_asset_cache', [ __CLASS__, 'reset_cache' ] );
			add_action( 'pum_save_settings', [ __CLASS__, 'reset_cache' ] );
			add_action( 'pum_save_popup', [ __CLASS__, 'reset_cache' ] );
			add_action( 'pum_save_theme', [ __CLASS__, 'reset_cache' ] );
			add_action( 'pum_update_core_version', [ __CLASS__, 'reset_cache' ] );

			add_action( 'pum_update_core_version', [ __CLASS__, 'maybe_reset_asset_cache_notices' ] );

			if ( isset( $_GET['flush_popup_cache'] ) && check_admin_referer( 'flush_popup_cache' ) ) {
				add_action( 'init', [ __CLASS__, 'reset_cache' ] );
			}

			add_filter( 'pum_alert_list', [ __CLASS__, 'cache_alert' ] );

			add_action( 'pum_styles', [ __CLASS__, 'global_custom_styles' ] );

			if ( null === get_option( 'pum_files_writeable', null ) ) {
				add_option( 'pum_files_writeable', true );
				add_option( '_pum_writeable_notice_dismissed', true );
				pum_reset_assets();
			}

			if ( is_admin() && current_user_can( 'edit_posts' ) ) {
				add_action( 'init', [ __CLASS__, 'admin_notice_check' ] );
			}

			// Prevent reinitialization.
			self::$initialized = true;
		}
	}

	/**
	 * Checks if Asset caching is possible and enabled.
	 *
	 * @return bool
	 */
	public static function enabled() {
		if ( defined( 'PUM_ASSET_CACHE' ) && ! PUM_ASSET_CACHE ) {
			return false;
		}

		return self::writeable() && ! self::$disabled;
	}

	/**
	 * Is the cache directory writeable?
	 *
	 * @return bool True if directory is writeable
	 */
	public static function writeable() {
		if ( self::$disabled ) {
			return false;
		}

		// If we have already determined files to not be writeable, go ahead and return.
		if ( true !== (bool) get_option( 'pum_files_writeable', true ) ) {
			return false;
		}

		/**
		 * Filesystem.
		 *
		 * @var \WP_Filesystem_Base $wp_filesystem
		 */
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$results = WP_Filesystem();

		if ( true !== $results ) {
			// Prevents this from running again and set to show the admin notice.
			update_option( 'pum_files_writeable', false );
			update_option( '_pum_writeable_notice_dismissed', false );
			if ( ! is_null( $results ) && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
				$error = $wp_filesystem->errors->get_error_message();
				pum_log_message( sprintf( 'Cache directory is not writeable due to filesystem error. Error given: %s', esc_html( $error ) ) );
			} else {
				pum_log_message( 'Cache directory is not writeable due to incorrect filesystem method.' );
			}
			return false;
		}

		// Checks and create cachedir.
		if ( false !== self::$cache_dir && ! is_dir( self::$cache_dir ) ) {
			$wp_filesystem->mkdir( self::$cache_dir );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return false !== self::$cache_dir && $wp_filesystem->is_writable( self::$cache_dir ) && ! isset( $_POST['wp_customize'] );
	}

	/**
	 * Regenerate cache on demand.
	 */
	public static function regenerate_cache() {
		self::cache_js();
		self::cache_css();
	}

	/**
	 * Gets the directory caching should be stored in.
	 *
	 * Accounts for various adblock bypass options.
	 *
	 * @return array|string
	 */
	public static function get_cache_dir() {
		$upload_dir = PUM_Helpers::get_upload_dir_path();
		if ( false === $upload_dir ) {
			return false;
		}

		if ( ! pum_get_option( 'bypass_adblockers', false ) ) {
			return trailingslashit( $upload_dir ) . 'pum';
		}

		return $upload_dir;
	}

	/**
	 * Generates a cache filename based on the current adblock bypass settings.
	 *
	 * @param string $filename Filename.
	 *
	 * @return string
	 */
	public static function generate_cache_filename( $filename ) {
		if ( ! pum_get_option( 'bypass_adblockers', false ) ) {
			global $blog_id;
			$is_multisite = ( is_multisite() ) ? '-' . $blog_id : '';

			return $filename . $is_multisite;
		}

		$site_url = get_site_url();

		switch ( pum_get_option( 'adblock_bypass_url_method', 'random' ) ) {
			case 'random':
				$filename = md5( $site_url . $filename );
				break;
			case 'custom':
				$filename = preg_replace( '/[^a-z0-9]+/', '-', pum_get_option( 'adblock_bypass_custom_filename', 'pm-' . $filename ) );

				break;
		}

		return $filename;
	}

	/**
	 * Generate JS cache file.
	 */
	public static function cache_js() {
		if ( false === self::$cache_dir ) {
			return;
		}
		$js_file = self::generate_cache_filename( 'pum-site-scripts' ) . '.js';

		$js  = "/**\n";
		$js .= " * Do not touch this file! This file created by the Popup Maker plugin using PHP\n";
		$js .= ' * Last modified time: ' . wp_date( 'M d Y, h:i:s' ) . "\n";
		$js .= " */\n\n\n";
		$js .= self::generate_js();

		if ( ! self::cache_file( $js_file, $js ) ) {
			update_option( 'pum-has-cached-js', false );
		} else {
			update_option( 'pum-has-cached-js', strtotime( 'now' ) );
		}
	}

	/**
	 * Generate CSS cache file.
	 */
	public static function cache_css() {
		if ( false === self::$cache_dir ) {
			return;
		}
		$css_file = self::generate_cache_filename( 'pum-site-styles' ) . '.css';

		$css  = "/**\n";
		$css .= " * Do not touch this file! This file created by the Popup Maker plugin using PHP\n";
		$css .= ' * Last modified time: ' . wp_date( 'M d Y, h:i:s' ) . "\n";
		$css .= " */\n\n\n";
		$css .= self::generate_css();

		if ( ! self::cache_file( $css_file, $css ) ) {
			update_option( 'pum-has-cached-css', false );
		} else {
			update_option( 'pum-has-cached-css', strtotime( 'now' ) );
		}
	}

	/**
	 * Generate custom JS
	 *
	 * @return string
	 */
	public static function generate_js() {
		/**
		 * Filesystem.
		 *
		 * @var \WP_Filesystem_Base $wp_filesystem
		 */
		global $wp_filesystem;

		// Load core scripts so we can eliminate another stylesheet.
		$core_js = $wp_filesystem->get_contents( Popup_Maker::$DIR . 'assets/js/site' . self::$suffix . '.js' );

		/**
		 *  0 Core
		 *  5 Extensions
		 *  8 Integrations
		 * 10 Per Popup JS
		 */
		$js = [
			'core' => [
				'content'  => $core_js,
				'priority' => 0,
			],
		];

		$popups = pum_get_all_popups();

		if ( ! empty( $popups ) ) {
			foreach ( $popups as $popup ) {
				// Set this popup as the global $current.
				pum()->current_popup = $popup;

				ob_start();

				// Allow per popup JS additions.
				do_action( 'pum_generate_popup_js', $popup->ID );

				$popup_js = ob_get_clean();

				if ( ! empty( $popup_js ) ) {
					$js[ 'popup-' . $popup->ID ] = [
						'content' => $popup_js,
					];
				}
			}

			// Clear the global $current.
			pum()->current_popup = null;
		}

		$js = apply_filters( 'pum_generated_js', $js );

		foreach ( $js as $key => $code ) {
			$js[ $key ] = wp_parse_args(
				$code,
				[
					'content'  => '',
					'priority' => 10,
				]
			);
		}

		uasort( $js, [ 'PUM_Helpers', 'sort_by_priority' ] );

		$js_code = '';
		foreach ( $js as $key => $code ) {
			if ( ! empty( $code['content'] ) ) {
				$js_code .= $code['content'] . "\n\n";
			}
		}

		return $js_code;
	}

	/**
	 * Cache file contents.
	 *
	 * @param string $filename Filename of file to generate.
	 * @param string $contents Contents to put into file.
	 *
	 * @return bool
	 */
	public static function cache_file( $filename, $contents ) {
		/**
		 * WP Filesystem.
		 *
		 * @var \WP_Filesystem_Base $wp_filesystem
		 */
		global $wp_filesystem;

		if ( false === self::$cache_dir ) {
			return false;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$file = trailingslashit( self::$cache_dir ) . $filename;

		WP_Filesystem();

		$results = $wp_filesystem->put_contents( $file, $contents, defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : false );

		// If the file is generated and is accessible...
		if ( true === $results && self::is_file_accessible( $filename ) ) {
			return true;
		} else {
			// ... else, let's set our flags to prevent cache running again for now.
			update_option( 'pum_files_writeable', false );
			update_option( '_pum_writeable_notice_dismissed', false );
			return false;
		}
	}

	/**
	 * Generate Custom Styles
	 *
	 * @return string
	 */
	public static function generate_css() {
		/**
		 * WP Filesystem.
		 *
		 * @var \WP_Filesystem_Base $wp_filesystem
		 */
		global $wp_filesystem;

		// Include core styles so we can eliminate another stylesheet.
		$core_css = $wp_filesystem->get_contents( Popup_Maker::$DIR . 'assets/css/pum-site' . ( is_rtl() ? '-rtl' : '' ) . self::$suffix . '.css' );

		/**
		 *  0 Core
		 *  1 Popup Themes
		 *  5 Extensions
		 * 10 Per Popup CSS
		 */
		$css = [
			'imports' => [
				'content'  => self::generate_font_imports(),
				'priority' => - 1,
			],
			'core'    => [
				'content'  => $core_css,
				'priority' => 0,
			],
			'themes'  => [
				'content'  => self::generate_popup_theme_styles(),
				'priority' => 1,
			],
			'popups'  => [
				'content'  => self::generate_popup_styles(),
				'priority' => 15,
			],
			'custom'  => [
				'content'  => self::custom_css(),
				'priority' => 20,
			],
		];

		$css = apply_filters( 'pum_generated_css', $css );

		foreach ( $css as $key => $code ) {
			$css[ $key ] = wp_parse_args(
				$code,
				[
					'content'  => '',
					'priority' => 10,
				]
			);
		}

		uasort( $css, [ 'PUM_Helpers', 'sort_by_priority' ] );

		$css_code = '';
		foreach ( $css as $key => $code ) {
			if ( ! empty( $code['content'] ) ) {
				$css_code .= $code['content'] . "\n\n";
			}
		}

		return $css_code;
	}

	/**
	 * Render global custom styles.
	 */
	public static function global_custom_styles() {
		if ( pum_get_option( 'adjust_body_padding' ) ) {
			echo 'html.pum-open.pum-open-overlay.pum-open-scrollable body > *:not([aria-modal="true"]) { padding-right: ' . esc_attr( pum_get_option( 'body_padding_override', '15px' ) ) . '!important; }';
		}
	}

	/**
	 * Generate Popup Styles
	 *
	 * @return string
	 */
	public static function generate_popup_styles() {
		$popup_css = '';

		$popups = pum_get_all_popups();

		if ( ! empty( $popups ) ) {
			foreach ( $popups as $popup ) {
				// Set this popup as the global $current.
				pum()->current_popup = $popup;

				$popup = pum_get_popup( $popup->ID );

				if ( ! pum_is_popup( $popup ) ) {
					continue;
				}

				ob_start();

				if ( $popup->get_setting( 'zindex', false ) ) {
					$zindex = esc_attr( $popup->get_setting( 'zindex' ) );
					echo sprintf( "#pum-%d {z-index: %d}\r\n", esc_attr( $popup->ID ), esc_attr( $zindex ) );
				}

				// Allow per popup CSS additions.
				do_action( 'pum_generate_popup_css', $popup->ID );

				$popup_css .= ob_get_clean();
			}

			// Clear the global $current.
			pum()->current_popup = null;
		}

		return $popup_css;
	}

	/**
	 * Used when asset cache is not enabled.
	 *
	 * @return string
	 */
	public static function inline_css() {
		ob_start();

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::generate_font_imports();
		echo self::generate_popup_theme_styles();

		// Render popup styles.
		echo self::generate_popup_styles();

		// Render any extra styles globally added.
		echo self::custom_css();

		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		return ob_get_clean();
	}

	/**
	 * Generate Popup Theme Styles
	 *
	 * @return string
	 */
	public static function custom_css() {
		// Reset ob.
		ob_start();

		// Render any extra styles globally added.
		if ( ! empty( $GLOBALS['pum_extra_styles'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $GLOBALS['pum_extra_styles'];
		}

		// Allows rendering extra css via action.
		do_action( 'pum_styles' );

		return ob_get_clean();
	}

	/**
	 * Generate Popup Theme Styles
	 *
	 * @return mixed|string
	 */
	public static function generate_font_imports() {
		$imports = '';

		$google_fonts = [];

		foreach ( pum_get_all_themes() as $theme ) {
			$google_fonts = array_merge( $google_fonts, pum_get_theme( $theme->ID )->get_google_fonts_used() );
		}

		if ( ! empty( $google_fonts ) && ! pum_get_option( 'disable_google_font_loading', false ) ) {
			$link = '//fonts.googleapis.com/css?family=';
			foreach ( $google_fonts as $font_family => $variants ) {
				if ( '//fonts.googleapis.com/css?family=' !== $link ) {
					$link .= '|';
				}
				$link .= $font_family;
				if ( is_array( $variants ) ) {
					if ( implode( ',', $variants ) !== '' ) {
						$link .= ':';
						$link .= trim( implode( ',', $variants ), ':' );
					}
				}
			}

			$imports = "/* Popup Google Fonts */\r\n@import url('$link');\r\n\r\n" . $imports;
		}

		$imports = apply_filters( 'pum_generate_font_imports', $imports );

		return $imports;
	}

	/**
	 * Generate Popup Theme Styles
	 *
	 * @return mixed|string
	 */
	public static function generate_popup_theme_styles() {
		$styles = '';

		$themes = pum_get_all_themes();

		foreach ( $themes as $theme ) {
			$theme_styles = pum_get_rendered_theme_styles( $theme->ID );

			if ( '' !== $theme_styles ) {
				$styles .= '/* Popup Theme ' . $theme->ID . ': ' . $theme->post_title . " */\r\n";
				$styles .= $theme_styles . "\r\n";
			}
		}

		$styles = apply_filters( 'popmake_theme_styles', $styles );

		$styles = apply_filters( 'pum_generate_popup_theme_styles', $styles );

		return $styles;
	}


	/**
	 * Reset the cache to force regeneration.
	 */
	public static function reset_cache() {
		update_option( 'pum-has-cached-css', false );
		update_option( 'pum-has-cached-js', false );
	}

	/**
	 * Adds admin notice if the files are not writeable.
	 *
	 * @param array $alerts The alerts currently in the alert system.
	 * @return array Alerts for the alert system.
	 * @since 1.9.0
	 */
	public static function cache_alert( $alerts ) {
		if ( self::should_not_show_alert() ) {
			return $alerts;
		}

		$nonce = wp_create_nonce( 'pum-write-notice-action' );

		$undo_url = add_query_arg( [
			'pum_writeable_notice_check' => 'undo',
			'_wpnonce'                   => $nonce,
		] );

		$dismiss_url = add_query_arg([
			'pum_writeable_notice_check' => 'dismiss',
			'_wpnonce'                   => $nonce,
		] );

		ob_start();
		?>
		<ul>
			<li><a href="<?php echo esc_attr( $undo_url ); ?>"><strong><?php esc_html_e( 'Try to create cache again', 'popup-maker' ); ?></strong></a></li>
			<li><a href="<?php echo esc_attr( $dismiss_url ); ?>" class="pum-dismiss"><?php esc_html_e( 'Keep current method', 'popup-maker' ); ?></a></li>
			<li><a href="https://docs.wppopupmaker.com/article/521-debugging-filesystem-errors?utm_source=filesystem-error-alert&utm_medium=inline-doclink&utm_campaign=filesystem-error" target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'Learn more', 'popup-maker' ); ?></a></li>
		</ul>
		<?php
		$html     = ob_get_clean();
		$alerts[] = [
			'code'        => 'pum_writeable_notice',
			'type'        => 'warning',
			'message'     => esc_html__( "Popup Maker detected an issue with your file system's ability and is unable to create & save cached assets for your popup styling and settings. This may lead to suboptimal performance. Please check your filesystem and contact your hosting provide to ensure Popup Maker can create and write to cache files.", 'popup-maker' ),
			'html'        => $html,
			'priority'    => 1000,
			'dismissible' => '2 weeks',
			'global'      => true,
		];
		return $alerts;
	}

	/**
	 * Checks if any options have been clicked from admin notices.
	 *
	 * @since 1.9.0
	 */
	public static function admin_notice_check() {
		if ( isset( $_GET['pum_writeable_notice_check'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			check_admin_referer( 'pum-write-notice-action' );

			// If either dismiss or try again button is clicked, hide the admin notice.
			update_option( '_pum_writeable_notice_dismissed', true );
			if ( 'undo' === $_GET['pum_writeable_notice_check'] ) {
				// If try again is clicked, remove flag.
				update_option( 'pum_files_writeable', true );
			} else {
				pum_update_option( 'disable_asset_caching', true );
			}
		}
	}

	/**
	 * Whether or not we should show admin notice
	 *
	 * @since 1.9.0
	 * @return bool True if notice should not be shown
	 */
	public static function should_not_show_alert() {
		return true === (bool) get_option( 'pum_files_writeable', true ) || true === (bool) get_option( '_pum_writeable_notice_dismissed', true );
	}

	/**
	 * Tests whether the file is accessible and returns 200 status code
	 *
	 * @param string $filename Filename of cache file to test.
	 * @return bool True if file exists and is accessible
	 */
	private static function is_file_accessible( $filename ) {
		if ( ! $filename || empty( $filename ) || ! is_string( $filename ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			pum_log_message( 'Cannot check if file is accessible. Filename passed: ' . print_r( $filename, true ) );
			return false;
		}
		$cache_url = PUM_Helpers::get_cache_dir_url();
		if ( false === $cache_url ) {
			pum_log_message( 'Cannot access cache file when tested. Cache URL returned false.' );
		}
		$protocol = is_ssl() ? 'https:' : 'http:';
		$file     = $protocol . $cache_url . '/' . $filename;
		$results  = wp_remote_request(
			$file,
			[
				'method'    => 'HEAD',
				'sslverify' => false,
			]
		);

		// If it returned a WP_Error, let's log its error message.
		if ( is_wp_error( $results ) ) {
			$error = $results->get_error_message();
			pum_log_message( sprintf( 'Cannot access cache file when tested. Tested file: %s Error given: %s', esc_html( $file ), esc_html( $error ) ) );
		}

		// If it returned valid array...
		if ( is_array( $results ) && isset( $results['response'] ) ) {
			$status_code = $results['response']['code'];

			// ... then, check if it's a valid status code. Only if it is a valid 2XX code, will this method return true.
			if ( false !== $status_code && ( 200 <= $status_code && 300 > $status_code ) ) {
				return true;
			} else {
				pum_log_message( sprintf( 'Cannot access cache file when tested. Status code received was: %s', esc_html( $status_code ) ) );
			}
		}
		return false;
	}

	/**
	 * Process a change to options conditionally if the user came from a bugged version.
	 *
	 * @param string $upgraded_from The version the user upgraded from.
	 */
	public static function maybe_reset_asset_cache_notices( $upgraded_from ) {
		// If version compoare upgraded from was v1.18.0 exactly delete the notice.
		if ( '1.18.0' === $upgraded_from ) {
			update_option( 'pum_files_writeable', true );
			update_option( '_pum_writeable_notice_dismissed', true );
			pum_update_option( 'disable_asset_caching', false );
		}
	}
}
