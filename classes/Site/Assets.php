<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

class PUM_Site_Assets {

	/**
	 * @var
	 */
	public static $cache_url;

	/**
	 * @var
	 */
	public static $suffix;

	/**
	 * @var
	 */
	public static $js_url;

	/**
	 * @var
	 */
	public static $css_url;

	/**
	 * @var array
	 */
	public static $enqueued_scripts = array();

	/**
	 * @var array
	 */
	public static $enqueued_styles = array();

	/**
	 * @var bool
	 */
	public static $scripts_registered = false;

	/**
	 * @var bool
	 */
	public static $styles_registered = false;

	/**
	 * @var bool Use minified libraries if SCRIPT_DEBUG is turned off.
	 */
	public static $debug;

	/**
	 * Initialize
	 */
	public static function init() {
		$upload_dir      = wp_upload_dir();
		self::$cache_url = trailingslashit( $upload_dir['baseurl'] ) . 'pum';
		self::$debug     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		self::$suffix    = self::$debug ? '' : '.min';
		self::$js_url    = Popup_Maker::$URL . 'assets/js/';
		self::$css_url   = Popup_Maker::$URL . 'assets/css/';

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_styles' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'popmake_preload_popup', array( __CLASS__, 'enqueue_popup_assets' ) );

		add_action( 'wp_head', array( __CLASS__, 'check_force_script_loading' ) );

		// TODO These are scheduled to be moved / removed.
		add_filter( 'clean_url', array( __CLASS__, 'defer_js_url' ), 11 );
	}

	/**
	 * Enqueue all needed assets.
	 */
	public static function enqueue_assets() {
		foreach ( self::$enqueued_scripts as $script ) {
			if ( wp_script_is( $script, 'registered' ) ) {
				wp_enqueue_script( $script );
			}
		}

		foreach ( self::$enqueued_styles as $style ) {
			if ( wp_style_is( $style, 'registered' ) ) {
				wp_enqueue_style( $style );
			}
		}
	}

	/**
	 * Enqueues a specific script asset for inclusion in asset generation.
	 *
	 * @param string $handler
	 */
	public static function enqueue_script( $handler = '' ) {
		if ( ! in_array( $handler, self::$enqueued_scripts ) ) {
			self::$enqueued_scripts[] = $handler;
		}

		if ( self::$scripts_registered ) {
			wp_enqueue_script( $handler );
		}
	}

	/**
	 * Enqueues a specific script asset for inclusion in asset generation.
	 *
	 * @param string $handler
	 */
	public static function enqueue_style( $handler = '' ) {
		if ( ! in_array( $handler, self::$enqueued_styles ) ) {
			self::$enqueued_styles[] = $handler;
		}

		if ( self::$styles_registered ) {
			wp_enqueue_style( $handler );
		}
	}

	/**
	 * @param int $popup_id
	 */
	public static function enqueue_popup_assets( $popup_id = 0 ) {
		/**
		 * TODO Replace this with a pum_get_popup function after new Popup model is in place.
		 *
		 * $popup = pum_get_popup( $popup_id );
		 *
		 * if ( ! pum_is_popup( $popup ) ) {
		 *        return;
		 * }
		 */

		$popup = new PUM_Popup( $popup_id );

		self::enqueue_script( 'popup-maker-site' );
		self::enqueue_style( 'popup-maker-site' );

		if ( $popup->mobile_disabled() || $popup->tablet_disabled() ) {
			self::enqueue_script( 'mobile-detect' );
		}

		/**
		 * TODO Implement this in core $popup model & advanced targeting conditions.
		 *
		 * if ( $popup->has_condition( array(
		 *    'device_is_mobile',
		 *    'device_is_phone',
		 *    'device_is_tablet',
		 *    'device_is_brand',
		 * ) ) ) {
		 *    self::enqueue_script( 'mobile-detect' );
		 * }
		 */

		// Preprocess the content for shortcodes that need to enqueue their own assets.
		do_shortcode( $popup->post_content );
	}

	/**
	 * Register JS.
	 */
	public static function register_scripts() {
		self::$scripts_registered = true;

		wp_register_script( 'mobile-detect', self::$js_url . 'mobile-detect' . self::$suffix, null, '1.3.3', true );

		if ( PUM_AssetCache::writeable() ) {
			$cached = get_option( 'pum-has-cached-js' );

			if ( ! $cached || self::$debug ) {
				PUM_AssetCache::cache_js();
				$cached = get_option( 'pum-has-cached-js' );
			}

			// check for multisite
			global $blog_id;
			$is_multisite = ( is_multisite() ) ? '-' . $blog_id : '';

			wp_register_script( 'popup-maker-site', self::$cache_url . '/pum-site-scripts' . $is_multisite . '.js?defer&generated=' . $cached, array(
				'jquery',
				'jquery-ui-core',
				'jquery-ui-position',
			), Popup_Maker::$VER, true );
		} else {
			wp_register_script( 'popup-maker-site', self::$js_url . 'site' . self::$suffix . '.js?defer', array(
				'jquery',
				'jquery-ui-core',
				'jquery-ui-position',
			), Popup_Maker::$VER, true );
		}

		wp_localize_script( 'popup-maker-site', 'pum_vars', apply_filters( 'pum_vars', array(
			'version'               => Popup_Maker::$VER,
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'restapi'               => function_exists( 'rest_url' ) ? esc_url_raw( rest_url( 'pum/v1' ) ) : false,
			'rest_nonce'            => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : null,
			'default_theme'         => (string) popmake_get_default_popup_theme(),
			'debug_mode'            => Popup_Maker::debug_mode(),
			'disable_open_tracking' => popmake_get_option( 'disable_popup_open_tracking' ),
		) ) );
		wp_localize_script( 'popup-maker-site', 'pum_debug_vars', apply_filters( 'pum_debug_vars', array(
			'debug_mode_enabled'             => _x( 'Popup Maker Debug Mode Enabled', 'debug console text', 'popup-maker' ),
			'debug_started_at'               => _x( 'Debug started at:', 'debug console text', 'popup-maker' ),
			'debug_more_info'                => sprintf( _x( 'For more information on how to use this information visit %s', 'debug console text', 'popup-maker' ), 'http://docs.wppopupmaker.com/?utm_medium=js-debug-info&utm_campaign=ContextualHelp&utm_source=browser-console&utm_content=more-info' ),
			'global_info'                    => _x( 'Global Information', 'debug console text', 'popup-maker' ),
			'localized_vars'                 => _x( 'Localized variables', 'debug console text', 'popup-maker' ),
			'popups_initializing'            => _x( 'Popups Initializing', 'debug console text', 'popup-maker' ),
			'popups_initialized'             => _x( 'Popups Initialized', 'debug console text', 'popup-maker' ),
			'single_popup_label'             => _x( 'Popup: #', 'debug console text', 'popup-maker' ),
			'theme_id'                       => _x( 'Theme ID: ', 'debug console text', 'popup-maker' ),
			'label_method_call'              => _x( 'Method Call:', 'debug console text', 'popup-maker' ),
			'label_method_args'              => _x( 'Method Arguments:', 'debug console text', 'popup-maker' ),
			'label_popup_settings'           => _x( 'Settings', 'debug console text', 'popup-maker' ),
			'label_triggers'                 => _x( 'Triggers', 'debug console text', 'popup-maker' ),
			'label_cookies'                  => _x( 'Cookies', 'debug console text', 'popup-maker' ),
			'label_delay'                    => _x( 'Delay:', 'debug console text', 'popup-maker' ),
			'label_conditions'               => _x( 'Conditions', 'debug console text', 'popup-maker' ),
			'label_cookie'                   => _x( 'Cookie:', 'debug console text', 'popup-maker' ),
			'label_settings'                 => _x( 'Settings:', 'debug console text', 'popup-maker' ),
			'label_selector'                 => _x( 'Selector:', 'debug console text', 'popup-maker' ),
			'label_mobile_disabled'          => _x( 'Mobile Disabled:', 'debug console text', 'popup-maker' ),
			'label_tablet_disabled'          => _x( 'Tablet Disabled:', 'debug console text', 'popup-maker' ),
			'label_display_settings'         => _x( 'Display Settings:', 'debug console text', 'popup-maker' ),
			'label_close_settings'           => _x( 'Close Settings:', 'debug console text', 'popup-maker' ),
			'label_event_before_open'        => _x( 'Event: Before Open', 'debug console text', 'popup-maker' ),
			'label_event_after_open'         => _x( 'Event: After Open', 'debug console text', 'popup-maker' ),
			'label_event_open_prevented'     => _x( 'Event: Open Prevented', 'debug console text', 'popup-maker' ),
			'label_event_setup_close'        => _x( 'Event: Setup Close', 'debug console text', 'popup-maker' ),
			'label_event_close_prevented'    => _x( 'Event: Close Prevented', 'debug console text', 'popup-maker' ),
			'label_event_before_close'       => _x( 'Event: Before Close', 'debug console text', 'popup-maker' ),
			'label_event_after_close'        => _x( 'Event: After Close', 'debug console text', 'popup-maker' ),
			'label_event_before_reposition'  => _x( 'Event: Before Reposition', 'debug console text', 'popup-maker' ),
			'label_event_after_reposition'   => _x( 'Event: After Reposition', 'debug console text', 'popup-maker' ),
			'label_event_checking_condition' => _x( 'Event: Checking Condition', 'debug console text', 'popup-maker' ),
			'triggers'                       => pum_get_trigger_labels(),
			'cookies'                        => pum_get_cookie_labels(),
		) ) );

		if ( popmake_get_option( 'enable_easy_modal_compatibility_mode', false ) ) {
			wp_register_script( 'popup-maker-easy-modal-importer-site', self::$js_url . 'popup-maker-easy-modal-importer-site' . self::$suffix . '?defer', array( 'popup-maker-site' ), POPMAKE_VERSION, true );
		}
	}

	/**
	 * Register CSS.
	 */
	public static function register_styles() {
		self::$styles_registered = true;

		if ( PUM_AssetCache::writeable() ) {
			$cached = get_option( 'pum-has-cached-css' );

			if ( ! $cached || self::$debug ) {
				PUM_AssetCache::cache_css();
				$cached = get_option( 'pum-has-cached-css' );
			}

			// check for multisite
			global $blog_id;
			$is_multisite = ( is_multisite() ) ? '-' . $blog_id : '';

			wp_register_style( 'popup-maker-site', self::$cache_url . '/pum-site-styles' . $is_multisite . '.css?generated=' . $cached, array(), Popup_Maker::$VER );
		} else {
			wp_register_style( 'popup-maker-site', self::$css_url . 'site' . self::$suffix . '.css', array(), Popup_Maker::$VER );
			self::inline_styles();
		}
	}

	/**
	 * Render popup inline styles.
	 */
	public static function inline_styles() {
		if ( ( current_action() == 'wp_head' && popmake_get_option( 'disable_popup_theme_styles', false ) ) || ( current_action() == 'admin_head' && ! popmake_is_admin_popup_page() ) ) {
			return;
		}

		wp_add_inline_style( 'popup-maker-site', PUM_AssetCache::generate_css() );
	}

	/**
	 * Defers loading of scripts with ?defer parameter in url.
	 *
	 * @param string $url URL being cleaned
	 *
	 * @return string $url
	 */
	public static function defer_js_url( $url ) {
		if ( false === strpos( $url, '.js?defer' ) ) {
			// not our file
			return $url;
		}

		return "$url' defer='defer";
	}

	/**
	 *
	 */
	public static function check_force_script_loading() {
		global $wp_query;
		if ( ! empty( $wp_query->post ) && has_shortcode( $wp_query->post->post_content, 'popup' ) || ( defined( "POPMAKE_FORCE_SCRIPTS" ) && POPMAKE_FORCE_SCRIPTS ) ) {
			self::enqueue_popup_scripts();
		}
	}
}
