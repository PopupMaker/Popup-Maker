<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/


class PUM_AssetCache {

	/**
	 * @var
	 */
	public static $cache_dir;

	/**
	 * @var
	 */
	public static $suffix;

	/**
	 * @var
	 */
	public static $asset_url;

	/**
	 * @var
	 */
	public static $js_url;

	/**
	 * @var
	 */
	public static $css_url;

	/**
	 * @var bool
	 */
	public static $disabled = true;

	/**
	 * @var
	 */
	public static $debug;

	public static $initialized = false;

	/**
	 *
	 */
	public static function init() {
		if ( ! self::$initialized ) {
			$upload_dir      = wp_upload_dir();
			self::$cache_dir = trailingslashit( $upload_dir['basedir'] ) . 'pum';
			self::$debug     = Popup_Maker::debug_mode() || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
			self::$suffix    = self::$debug ? '' : '.min';
			self::$asset_url = Popup_Maker::$URL . 'assets/';
			self::$js_url    = self::$asset_url . 'js/';
			self::$css_url   = self::$asset_url . 'css/';
			self::$disabled  = pum_get_option( 'disable_asset_caching', false );

			add_action( 'pum_extension_updated', array( __CLASS__, 'reset_cache' ) );
			add_action( 'pum_extension_deactivated', array( __CLASS__, 'reset_cache' ) );
			add_action( 'pum_extension_activated', array( __CLASS__, 'reset_cache' ) );
			add_action( 'pum_regenerate_asset_cache', array( __CLASS__, 'reset_cache' ) );
			add_action( 'pum_save_settings', array( __CLASS__, 'reset_cache' ) );
			add_action( 'pum_save_popup', array( __CLASS__, 'reset_cache' ) );
			add_action( 'popmake_save_popup_theme', array( __CLASS__, 'reset_cache' ) );
			add_action( 'pum_update_core_version', array( __CLASS__, 'reset_cache' ) );

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
		return self::writeable() && ! self::$disabled;
	}

	/**
	 * Is the cache directory writeable?
	 *
	 * @return bool
	 */
	public static function writeable() {
		// TODO Remove this once all extensions have been thoroughly updated with time to get them to users.
		if ( self::$disabled ) {
			return false;
		}

		// Check and create cachedir
		if ( ! is_dir( self::$cache_dir ) ) {

			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			WP_Filesystem();

			global $wp_filesystem;

			/** @var WP_Filesystem_Base $wp_filesystem */
			$wp_filesystem->mkdir( self::$cache_dir );
		}

		return is_writable( self::$cache_dir ) && ! isset( $_POST['wp_customize'] );
	}

	/**
	 * Regenerate cache on demand.
	 */
	public static function regenerate_cache() {
		self::cache_js();
		self::cache_css();
	}

	/**
	 * Generate JS cache file.
	 */
	public static function cache_js() {
		global $blog_id;
		$is_multisite = ( is_multisite() ) ? '-' . $blog_id : '';

		$js_file = self::$cache_dir . '/pum-site-scripts' . $is_multisite . '.js';

		$js = "/**\n";
		$js .= " * Do not touch this file! This file created by PHP\n";
		$js .= " * Last modifiyed time: " . date( 'M d Y, h:s:i' ) . "\n";
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
		global $blog_id;
		$is_multisite = ( is_multisite() ) ? '-' . $blog_id : '';

		$css_file = self::$cache_dir . '/pum-site-styles' . $is_multisite . '.css';

		$css = "/**\n";
		$css .= " * Do not touch this file! This file created by PHP\n";
		$css .= " * Last modifiyed time: " . date( 'M d Y, h:s:i' ) . "\n";
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
		// Load core scripts so we can eliminate another stylesheet.
		$core_js = file_get_contents( Popup_Maker::$DIR . 'assets/js/site' . self::$suffix . '.js' );

		/**
		 *  0 Core
		 *  5 Extensions
		 * 10 Per Popup JS
		 */
		$js = array(
			'core' => array(
				'content'  => $core_js,
				'priority' => 0,
			),
		);

		$query = PUM_Popups::get_all();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : $query->next_post();
				// Set this popup as the global $current.
				PUM_Site_Popups::current_popup( $query->post );

				// Preprocess the content for shortcodes that need to enqueue their own assets.
				PUM_Helpers::do_shortcode( $query->post->post_content );

				ob_start();

				// Allow per popup JS additions.
				do_action( 'pum_generate_popup_js', $query->post->ID );

				$popup_js = ob_get_clean();

				if ( ! empty( $popup_js ) ) {
					$js[ 'popup-' . $query->post->ID ] = array(
						'content' => $popup_js,
					);
				}
			endwhile;

			// Clear the global $current.
			PUM_Site_Popups::current_popup( null );
		}

		$js = apply_filters( 'pum_generated_js', $js );

		foreach ( $js as $key => $code ) {
			$js[ $key ] = wp_parse_args( $code, array(
				'content'  => '',
				'priority' => 10,
			) );
		}

		uasort( $js, array( 'PUM_Helpers', 'sort_by_priority' ) );

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
	 * @param $file
	 * @param $contents
	 *
	 * @return bool
	 */
	public static function cache_file( $file, $contents ) {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		WP_Filesystem();

		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		return $wp_filesystem->put_contents( $file, $contents, defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : false );
	}

	/**
	 * Generate Custom Styles
	 *
	 * @return string
	 */
	public static function generate_css() {
		// Include core styles so we can eliminate another stylesheet.
		$core_css = file_get_contents( Popup_Maker::$DIR . 'assets/css/site' . self::$suffix . '.css' );

		/**
		 *  0 Core
		 *  1 Popup Themes
		 *  5 Extensions
		 * 10 Per Popup CSS
		 */
		$css = array(
			'imports' => array(
				'content'  => self::generate_font_imports(),
				'priority' => - 1,
			),
			'core'    => array(
				'content'  => $core_css,
				'priority' => 0,
			),
			'themes'  => array(
				'content'  => self::generate_popup_theme_styles(),
				'priority' => 1,
			),
			'popups'  => array(
				'content'  => self::generate_popup_styles(),
				'priority' => 15,
			),
			'custom'  => array(
				'content'  => self::custom_css(),
				'priority' => 20,
			),
		);

		$css = apply_filters( 'pum_generated_css', $css );

		foreach ( $css as $key => $code ) {
			$css[ $key ] = wp_parse_args( $code, array(
				'content'  => '',
				'priority' => 10,
			) );
		}

		uasort( $css, array( 'PUM_Helpers', 'sort_by_priority' ) );

		$css_code = '';
		foreach ( $css as $key => $code ) {
			if ( ! empty( $code['content'] ) ) {
				$css_code .= $code['content'] . "\n\n";
			}
		}

		return $css_code;
	}

	/**
	 * @return string
	 */
	public static function generate_popup_styles() {
		$query = PUM_Popups::get_all();

		$popup_css = '';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : $query->next_post();
				// Set this popup as the global $current.
				PUM_Site_Popups::current_popup( $query->post );

				// Preprocess the content for shortcodes that need to enqueue their own assets.
				PUM_Helpers::do_shortcode( $query->post->post_content );

				$popup = pum_get_popup( $query->post->ID );

				if ( ! pum_is_popup( $popup ) ) {
					continue;
				}

				ob_start();

				if ( $popup->get_setting( 'zindex', false ) ) {
					$zindex = absint( $popup->get_setting( 'zindex' ) );
					echo "#pum-{$popup->ID} {z-index: $zindex}\r\n";
				}

				// Allow per popup CSS additions.
				do_action( 'pum_generate_popup_css', $popup->ID );

				$popup_css .= ob_get_clean();

			endwhile;

			// Clear the global $current.
			PUM_Site_Popups::current_popup( null );
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

		echo self::generate_font_imports();
		echo self::generate_popup_theme_styles();

		echo self::generate_popup_styles();

		// Render any extra styles globally added.
		if ( ! empty( $GLOBALS['pum_extra_styles'] ) ) {
			echo $GLOBALS['pum_extra_styles'];
		}

		// Allows rendering extra css via action.
		do_action( 'pum_styles' );

		return ob_get_clean();
	}

	/**
	 * @return string
	 */
	public static function custom_css() {
		// Reset ob.
		ob_start();

		// Render any extra styles globally added.
		if ( ! empty( $GLOBALS['pum_extra_styles'] ) ) {
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

		$google_fonts = array();

		foreach ( popmake_get_all_popup_themes() as $theme ) {
			$google_fonts = array_merge( $google_fonts, popmake_get_popup_theme_google_fonts( $theme->ID ) );
		}

		if ( ! empty( $google_fonts ) && ! pum_get_option( 'disable_google_font_loading', false ) ) {
			$link = "//fonts.googleapis.com/css?family=";
			foreach ( $google_fonts as $font_family => $variants ) {
				if ( $link != "//fonts.googleapis.com/css?family=" ) {
					$link .= "|";
				}
				$link .= $font_family;
				if ( is_array( $variants ) ) {
					if ( implode( ',', $variants ) != '' ) {
						$link .= ":";
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

		foreach ( popmake_get_all_popup_themes() as $theme ) {
			$theme_styles = pum_render_theme_styles( $theme->ID );

			if ( $theme_styles != '' ) {
				$styles .= "/* Popup Theme " . $theme->ID . ": " . $theme->post_title . " */\r\n";
				$styles .= $theme_styles . "\r\n";
			}
		}

		$styles = apply_filters( 'popmake_theme_styles', $styles );

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
	 * @param $theme_id
	 */
	public static function generate_popup_theme_style( $theme_id ) {
	}


}
