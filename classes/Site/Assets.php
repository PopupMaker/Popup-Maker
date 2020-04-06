<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
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
		self::$cache_url = PUM_Helpers::get_cache_dir_url();
		self::$debug     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		self::$suffix    = self::$debug ? '' : '.min';
		self::$js_url    = Popup_Maker::$URL . 'assets/js/';
		self::$css_url   = Popup_Maker::$URL . 'assets/css/';

		// Register assets early.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );

		// Localize after popups rendered in PUM_Site_Popups
		add_action( 'wp_footer', array( __CLASS__, 'late_localize_scripts' ), 19 );

		// Checks preloaded popups in the head for which assets to enqueue.
		add_action( 'pum_preload_popup', array( __CLASS__, 'enqueue_popup_assets' ) );
		add_filter( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_page_assets' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'fix_broken_extension_scripts' ), 100 );

		// Allow forcing assets to load.
		add_action( 'wp_head', array( __CLASS__, 'check_force_script_loading' ) );
	}

	public static function fix_broken_extension_scripts() {
		if ( wp_script_is( 'pum_aweber_integration_js' ) && class_exists( 'PUM_Aweber_Integration' ) && defined( 'PUM_AWEBER_INTEGRATION_VER' ) && version_compare( PUM_AWEBER_INTEGRATION_VER, '1.1.0', '<' ) ) {
			wp_dequeue_script( 'pum_aweber_integration_js' );
			wp_dequeue_style( 'pum_aweber_integration_css' );
			wp_dequeue_script( 'pum_newsletter_script' );
			wp_dequeue_style( 'pum-newsletter-styles' );

			wp_enqueue_style( 'pum-newsletter-styles', PUM_AWEBER_INTEGRATION_URL . '/includes/pum-newsletters/newsletter-styles' . self::$suffix . '.css' );
			wp_enqueue_script( 'pum_newsletter_script', PUM_AWEBER_INTEGRATION_URL . '/includes/pum-newsletters/newsletter-scripts' . self::$suffix . '.js', array(
				'jquery',
				'popup-maker-site',
			), false, true );

		}

		$mc_ver_test = in_array( true, array(
			class_exists( 'PUM_MailChimp_Integration' ) && defined( 'PUM_MAILCHIMP_INTEGRATION_VER' ) && PUM_MAILCHIMP_INTEGRATION_VER,
			class_exists( 'PUM_MCI' ) && version_compare( PUM_MCI::$VER, '1.3.0', '<' ),
		) );

		if ( $mc_ver_test ) {
			wp_dequeue_script( 'pum_mailchimp_integration_admin_js' );
			wp_dequeue_style( 'pum_mailchimp_integration_admin_css' );
			wp_dequeue_script( 'pum-mci' );
			wp_dequeue_style( 'pum-mci' );
			wp_dequeue_script( 'pum-newsletter-site' );
			wp_dequeue_style( 'pum-newsletter-site' );

			wp_enqueue_style( 'pum-newsletter-site', PUM_NEWSLETTER_URL . 'assets/css/pum-newsletter-site' . self::$suffix . '.css', null, PUM_NEWSLETTER_VERSION );
			wp_enqueue_script( 'pum-newsletter-site', PUM_NEWSLETTER_URL . 'assets/js/pum-newsletter-site' . self::$suffix . '.js', array( 'jquery' ), PUM_NEWSLETTER_VERSION, true );
			wp_localize_script( 'pum-newsletter-site', 'pum_sub_vars', array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'message_position' => 'top',
			) );
		}

	}

	/**
	 * Checks the current page content for the newsletter shortcode.
	 */
	public static function enqueue_page_assets() {
		global $post;

		if ( ! empty( $post ) && has_shortcode( $post->post_content, 'pum_sub_form' ) ) {
			wp_enqueue_script( 'popup-maker-site' );
			wp_enqueue_style( 'popup-maker-site' );
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

		wp_enqueue_script( 'popup-maker-site' );
		wp_enqueue_style( 'popup-maker-site' );

		if ( $popup->mobile_disabled() || $popup->tablet_disabled() ) {
			wp_enqueue_script( 'mobile-detect' );
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
	}

	/**
	 * Register JS.
	 */
	public static function register_scripts() {
		self::$scripts_registered = true;

		wp_register_script( 'mobile-detect', self::$js_url . 'vendor/mobile-detect.min.js', null, '1.3.3', true );
		wp_register_script( 'iframe-resizer', self::$js_url . 'vendor/iframeResizer.min.js', array( 'jquery' ) );

		if ( PUM_AssetCache::enabled() && false !== self::$cache_url ) {
			$cached = get_option( 'pum-has-cached-js' );

			if ( ! $cached || self::$debug ) {
				PUM_AssetCache::cache_js();
				$cached = get_option( 'pum-has-cached-js' );
			}


			wp_register_script( 'popup-maker-site', self::$cache_url . '/' . PUM_AssetCache::generate_cache_filename( 'pum-site-scripts' ) . '.js?defer&generated=' . $cached, array(
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


		if ( popmake_get_option( 'enable_easy_modal_compatibility_mode', false ) ) {
			wp_register_script( 'popup-maker-easy-modal-importer-site', self::$js_url . 'popup-maker-easy-modal-importer-site' . self::$suffix . '?defer', array( 'popup-maker-site' ), POPMAKE_VERSION, true );
		}

		self::localize_scripts();
	}

	/**
	 * Localize scripts if enqueued.
	 */
	public static function localize_scripts() {
		$site_home_path = parse_url( home_url() );
		$site_home_path = isset( $site_home_path['path'] ) ? $site_home_path['path'] : '/';

		wp_localize_script( 'popup-maker-site', 'pum_vars', apply_filters( 'pum_vars', array(
			'version'                => Popup_Maker::$VER,
			'pm_dir_url'             => Popup_Maker::$URL,
			'ajaxurl'                => admin_url( 'admin-ajax.php' ),
			'restapi'                => function_exists( 'rest_url' ) ? esc_url_raw( rest_url( 'pum/v1' ) ) : false,
			'rest_nonce'             => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : null,
			'default_theme'          => (string) pum_get_default_theme_id(),
			'debug_mode'             => Popup_Maker::debug_mode(),
			'disable_tracking'       => popmake_get_option( 'disable_popup_open_tracking' ),
			'home_url'               => trailingslashit( $site_home_path ),
			'message_position'       => 'top',
			'core_sub_forms_enabled' => ! PUM_Newsletters::$disabled,
			'popups'                 => array(),
		) ) );

		// TODO Remove all trace usages of these in JS so they can be removed.
		// @deprecated 1.4 Use pum_vars instead.
		wp_localize_script( 'popup-maker-site', 'ajaxurl', admin_url( 'admin-ajax.php' ) );

		if ( Popup_Maker::debug_mode() || isset( $_GET['pum_debug'] ) ) {
			wp_localize_script( 'popup-maker-site', 'pum_debug_vars', apply_filters( 'pum_debug_vars', array(
				'debug_mode_enabled'    => __( 'Popup Maker', 'popup-maker' ) . ': ' . __( 'Debug Mode Enabled', 'popup-maker' ),
				'debug_started_at'      => __( 'Debug started at:', 'popup-maker' ),
				'debug_more_info'       => sprintf( __( 'For more information on how to use this information visit %s', 'popup-maker' ), 'https://docs.wppopupmaker.com/?utm_medium=js-debug-info&utm_campaign=ContextualHelp&utm_source=browser-console&utm_content=more-info' ),
				'global_info'           => __( 'Global Information', 'popup-maker' ),
				'localized_vars'        => __( 'Localized variables', 'popup-maker' ),
				'popups_initializing'   => __( 'Popups Initializing', 'popup-maker' ),
				'popups_initialized'    => __( 'Popups Initialized', 'popup-maker' ),
				'single_popup_label'    => __( 'Popup: #', 'popup-maker' ),
				'theme_id'              => __( 'Theme ID: ', 'popup-maker' ),
				'label_method_call'     => __( 'Method Call:', 'popup-maker' ),
				'label_method_args'     => __( 'Method Arguments:', 'popup-maker' ),
				'label_popup_settings'  => __( 'Settings', 'popup-maker' ),
				'label_triggers'        => __( 'Triggers', 'popup-maker' ),
				'label_cookies'         => __( 'Cookies', 'popup-maker' ),
				'label_delay'           => __( 'Delay:', 'popup-maker' ),
				'label_conditions'      => __( 'Conditions', 'popup-maker' ),
				'label_cookie'          => __( 'Cookie:', 'popup-maker' ),
				'label_settings'        => __( 'Settings:', 'popup-maker' ),
				'label_selector'        => __( 'Selector:', 'popup-maker' ),
				'label_mobile_disabled' => __( 'Mobile Disabled:', 'popup-maker' ),
				'label_tablet_disabled' => __( 'Tablet Disabled:', 'popup-maker' ),
				'label_event'           => __( 'Event: %s', 'popup-maker' ),
				'triggers'              => PUM_Triggers::instance()->dropdown_list(),
				'cookies'               => PUM_Cookies::instance()->dropdown_list(),
			) ) );
		}

		/* Here for backward compatibility. */
		wp_localize_script( 'popup-maker-site', 'pum_sub_vars', array(
			'ajaxurl'          => admin_url( 'admin-ajax.php' ),
			'message_position' => 'top',
		) );
	}

	/**
	 * Localize late script vars if enqueued.
	 */
	public static function late_localize_scripts() {
		// If scripts not rendered, localize these vars. Otherwise echo them manually.
		if ( ! wp_script_is( 'popup-maker-site', 'done' ) ) {
			wp_localize_script( 'popup-maker-site', 'pum_popups', self::get_popup_settings() );
		} else {
			echo "<script type='text/javascript'>";
			echo 'window.pum_popups = ' . PUM_Utils_Array::safe_json_encode( self::get_popup_settings() ) . ';';
			// Backward compatibility fill.
			echo 'window.pum_vars.popups = window.pum_popups;';
			echo "</script>";
		}
	}

	/**
	 * Gets public settings for each popup for a global JS variable.
	 *
	 * @return array
	 */
	public static function get_popup_settings() {
		$loaded = PUM_Site_Popups::get_loaded_popups();

		$settings = array();

		$current_popup = pum()->current_popup;

		if ( $loaded->have_posts() ) {
			while ( $loaded->have_posts() ) : $loaded->next_post();
				pum()->current_popup = $loaded->post;
				$popup               = pum_get_popup( $loaded->post->ID );
				// Set the key to the CSS id of this popup for easy lookup.
				$settings[ 'pum-' . $popup->ID ] = $popup->get_public_settings();
			endwhile;

			pum()->current_popup = $current_popup;
		}

		return $settings;
	}

	/**
	 * Register CSS.
	 */
	public static function register_styles() {
		self::$styles_registered = true;

		if ( PUM_AssetCache::enabled() && false !== self::$cache_url ) {
			$cached = get_option( 'pum-has-cached-css' );

			if ( ! $cached || self::$debug ) {
				PUM_AssetCache::cache_css();
				$cached = get_option( 'pum-has-cached-css' );
			}

			wp_register_style( 'popup-maker-site', self::$cache_url . '/' . PUM_AssetCache::generate_cache_filename( 'pum-site-styles' ) . '.css?generated=' . $cached, array(), Popup_Maker::$VER );
		} else {
			wp_register_style( 'popup-maker-site', self::$css_url . 'pum-site' . ( is_rtl() ? '-rtl' : '' ) . self::$suffix . '.css', array(), Popup_Maker::$VER );
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

		wp_add_inline_style( 'popup-maker-site', PUM_AssetCache::inline_css() );
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
			wp_enqueue_script( 'popup-maker-site' );
			wp_enqueue_style( 'popup-maker-site' );
		}
	}
}
