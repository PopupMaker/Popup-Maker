<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all onboarding throughout site admin areas.
 *
 * @since 1.11.0
 */
class PUM_Admin_Onboarding {

	/**
	 * Enqueues and sets up pointers across our admin pages.
	 */
	public static function init() {
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			add_filter( 'pum_alert_list', array( __CLASS__, 'tips_alert' ) );
			add_action( 'pum_alert_dismissed', array( __CLASS__, 'alert_handler' ), 10, 2 );
		}
		add_filter( 'pum_admin_pointers-popup', array( __CLASS__, 'popup_editor_main_tour' ) );
		add_filter( 'pum_admin_pointers-edit-popup', array( __CLASS__, 'all_popups_main_tour' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'set_up_pointers' ) );

		add_action( 'admin_init', array( __CLASS__, 'welcome_redirect' ) );
		if ( ! empty( $_GET['page'] ) && 'pum-welcome' === $_GET['page'] ) {
			add_action( 'admin_menu', array( __CLASS__, 'set_up_welcome_page' ) );
		}
	}

	/**
	 * Adds a 'tip' alert occasionally inside PM's admin area
	 *
	 * @param array $alerts The alerts currently in the alert system.
	 * @return array Alerts for the alert system.
	 * @since 1.13.0
	 */
	public static function tips_alert( $alerts ) {
		if ( ! self::should_show_tip() ) {
			return $alerts;
		}

		$tip = self::get_random_tip();

		$alerts[] = array(
			'code'        => 'pum_tip_alert',
			'type'        => 'info',
			'message'     => $tip['msg'],
			'priority'    => 10,
			'dismissible' => '1 month',
			'global'      => false,
			'actions'     => array(
				array(
					'primary' => true,
					'type'    => 'link',
					'action'  => '',
					'href'    => $tip['link'],
					'text'    => __( 'Learn more', 'popup-maker' ),
				),
				array(
					'primary' => false,
					'type'    => 'action',
					'action'  => 'dismiss',
					'text'    => __( 'Dismiss', 'popup-maker' ),
				),
				array(
					'primary' => false,
					'type'    => 'action',
					'action'  => 'disable_tips',
					'text'    => __( 'Turn off these occasional tips', 'popup-maker' ),
				),
			),
		);

		return $alerts;
	}

	/**
	 * Checks if any options have been clicked from admin notices.
	 *
	 * @param string $code The code for the alert.
	 * @param string $action Action taken on the alert.
	 * @since 1.13.0
	 */
	public static function alert_handler( $code, $action ) {
		if ( 'pum_tip_alert' === $code ) {
			if ( 'disable_tips' === $action ) {
				pum_update_option( 'disable_tips', true );
			}
		}
	}

	/**
	 * Sets up all guided tours for Popup Maker
	 *
	 * @since 1.11.0
	 */
	public static function set_up_pointers() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pointers = self::get_pointers_by_screen();

		// Get dismissed pointers.
		$dismissed      = self::get_dismissed_pointers();
		$valid_pointers = array();

		// Cycles through pointers and only add valid ones.
		foreach ( $pointers as $pointer_id => $pointer ) {

			// Skip if pointer isn't an array.
			if ( ! is_array( $pointer ) ) {
				continue;
			}

			$pointer['pointer_id'] = $pointer_id;

			// Skip if pointer is not valid.
			if ( ! self::is_pointer_valid( $pointer ) ) {
				continue;
			}

			// Skip if pointer has already been dismissed.
			if ( in_array( $pointer_id, $dismissed ) )
				continue;

			// Add the pointer to $valid_pointers array.
			$valid_pointers['pointers'][] = $pointer;
		}

		// Bail out if there are no pointers to display.
		if ( empty( $valid_pointers ) ) {
			return;
		}

		// Add pointers style to queue.
		wp_enqueue_style( 'wp-pointer' );

		// Add pointers script to queue. Add custom script.
		wp_enqueue_script( 'pum-pointer', Popup_Maker::$URL . 'assets/js/admin-pointer.js', array( 'wp-pointer' ), Popup_Maker::$VER, true );

		// Add pointer options to script.
		wp_localize_script( 'pum-pointer', 'pumPointers', $valid_pointers );
	}

	/**
	 * Retrieves the pointers for the given screen or current screen
	 *
	 * @param bool|WP_Screen $screen Pass false for current screen.
	 * @return array
	 * @since 1.11.0
	 */
	public static function get_pointers_by_screen( $screen = false ) {
		if ( false === $screen || ! is_a( $screen, 'WP_Screen' ) ) {
			$screen = get_current_screen();
		}
		$screen_id = $screen->id;
		$pointers  = apply_filters( 'pum_admin_pointers-' . $screen_id, array() );

		if ( ! $pointers || ! is_array( $pointers ) ) {
			return array();
		}

		return $pointers;
	}

	/**
	 * Appends our main tour for the popup editor to pointers.
	 *
	 * @param array $pointers The pointers added to the screen.
	 * @return array $pointers The updated pointers array.
	 * @since 1.11.0
	 */
	public static function popup_editor_main_tour( $pointers ) {
		/**
		 * For the position, the 'edge' is used as the second parameter
		 * in jQuery's "at" with the opposite in jQuery's "my".
		 * The optional align is used as the first parameter in both "at" and "my".
		 *
		 * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/js/wp-pointer.js#L295
		 * @see https://jqueryui.com/position/
		 */

		$pointers['popup-editor-1'] = array(
			'target'  => '#title',
			'options' => array(
				'content'  => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Name' ,'popup-maker'),
					__( 'Name your popup so you can find it later. Site visitors will not see this.','popup-maker')
				),
				'position' => array( 'edge' => 'top', 'align' => 'center' ),
			)
		);
		$pointers['popup-editor-2'] = array(
			'target'  => '#wp-content-editor-container',
			'options' => array(
				'content'  => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Content' ,'popup-maker'),
					__( 'Add content for your popup here.','popup-maker')
				),
				'position' => array( 'edge' => 'bottom', 'align' => 'center' ),
			)
		);
		$pointers['popup-editor-3'] = array(
			'target'  => 'a[href="#pum-popup-settings_triggers"]',
			'options' => array(
				'content'  => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Triggers' ,'popup-maker'),
					__( 'Use triggers to choose  what causes the popup to open.','popup-maker')
				),
				'position' => array( 'edge' => 'left', 'align' => 'center' ),
			)
		);
		$pointers['popup-editor-4'] = array(
			'target'  => 'a[href="#pum-popup-settings_targeting"]',
			'options' => array(
				'content'  => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Targeting' ,'popup-maker'),
					__( 'Use targeting to choose where on your site the popup should load and who to show the popup to.','popup-maker')
				),
				'position' => array( 'edge' => 'left', 'align' => 'center'  ),
			)
		);
		$pointers['popup-editor-5'] = array(
			'target'  => 'a[href="#pum-popup-settings_display"]',
			'options' => array(
				'content'  => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Display' ,'popup-maker'),
					__( 'Use display settings to choose where on the screen the popup appears and what it looks like.','popup-maker')
				),
				'position' => array( 'edge' => 'left', 'align' => 'center'  ),
			)
		);
		$pointers['popup-editor-6'] = array(
			'target'  => 'select#theme_id',
			'options' => array(
				'content'  => sprintf( '<h3> %s </h3> <p> %s </p>',
					__( 'Popup Theme' ,'popup-maker'),
					__( 'Choose the popup theme which controls the visual appearance of your popup including; colors, spacing, and fonts.','popup-maker')
				),
				'position' => array( 'edge' => 'bottom', 'align' => 'left'  ),
			),
			'pre'     => array(
				'clicks' => array(
					'a[href="#pum-popup-settings_display"]',
					'a[href="#pum-popup-settings-display-subtabs_main"]',
				),
			),
		);
		return $pointers;
	}

	/**
	 * Appends our main tour for the All Popups page.
	 *
	 * @param array $pointers The pointers added to the screen.
	 * @return array $pointers The updated pointers array.
	 * @since 1.11.0
	 */
	public static function all_popups_main_tour( $pointers ) {
		$pointers['all-popups-1'] = array(
			'target'  => 'nav.nav-tab-wrapper a:nth-child(4)',
			'options' => array(
				'content'  => sprintf(
					'<h3> %s </h3> <p> %s </p>',
					__( 'Welcome to Popup Maker!', 'popup-maker' ),
					__( 'Click the "Create New Popup" button to create your first popup.', 'popup-maker' )
				),
				'position' => array( 'edge' => 'top' ),
			),
		);
		$pointers['all-popups-2'] = array(
			'target'  => '.wp-list-table #the-list tr:first-child .column-enabled',
			'options' => array(
				'content'  => sprintf(
					'<h3> %s </h3> <p> %s </p>',
					__( 'Enable Popups', 'popup-maker' ),
					__( 'You can enable or disable your popups at any time using this toggle.', 'popup-maker' )
				),
				'position' => array(
					'edge'  => 'top',
					'align' => 'left',
				),
			),
		);
		$pointers['all-popups-3'] = array(
			'target'  => '.wp-list-table #the-list tr:first-child .column-conversions',
			'options' => array(
				'content'  => sprintf(
					'<h3> %s </h3> <p> %s </p>',
					__( 'Review Popup Metrics', 'popup-maker' ),
					__( 'Popup Maker will automatically track opens and conversions so you can easily see which popups convert the best.', 'popup-maker' )
				),
				'position' => array(
					'edge'  => 'top',
					'align' => 'left',
				),
			),
		);
		$pointers['all-popups-4'] = array(
			'target'  => '#screen-options-link-wrap #show-settings-link',
			'options' => array(
				'content'  => sprintf(
					'<h3> %s </h3> <p> %s </p>',
					__( 'Adjust Columns', 'popup-maker' ),
					__( 'You can show or hide columns from the table on this page using the Screen Options. Popup Heading and Published Date are hidden by default.', 'popup-maker' )
				),
				'position' => array(
					'edge'  => 'top',
					'align' => 'center',
				),
			),
		);

		return $pointers;
	}

	/**
	 * Retrieves a random tip
	 *
	 * @return array An array containing tip
	 * @since 1.13.0
	 */
	public static function get_random_tip() {
		$tips = array(
			array(
				'msg'  => 'Did you know: Popup Maker has a setting to let you try to bypass adblockers? Enabling it randomizes cache filenames and other endpoints to try to get around adblockers.',
				'link' => admin_url( 'edit.php?post_type=popup&page=pum-settings&tab=pum-settings_misc' ),
			),
			array(
				'msg'  => "Want to use the block editor to create your popups? Enable it over on Popup Maker's settings page.",
				'link' => admin_url( 'edit.php?post_type=popup&page=pum-settings' ),
			),
			array(
				'msg'  => 'Using the Popup Maker menu in your admin bar, you can open and close popups, check conditions, reseet cookies, and more!',
				'link' => 'https://docs.wppopupmaker.com/article/300-the-popup-maker-admin-toolbar',
			),
			array(
				'msg'  => "Did you know: You can easily customize your site's navigation to have a link open a popup by using the 'Trigger a Popup' option when editing your menus?",
				'link' => 'https://docs.wppopupmaker.com/article/51-open-a-popup-from-a-wordpress-nav-menu',
			),
		);

		if ( 7 < pum_count_popups() ) {
			$tips[] = array(
				'msg'  => 'Want to organize your popups? Enable categories on the settings page to group similar popups together!',
				'link' => admin_url( 'edit.php?post_type=popup&page=pum-settings&tab=pum-settings_misc' ),
			);
		}

		$random_tip = array_rand( $tips );
		return $tips[ $random_tip ];
	}

	/**
	 * Redirect to the welcome screen, if needed
	 *
	 * @since 1.14.0
	 */
	public static function welcome_redirect() {
		// Redirect idea from Better Click To Tweet's welcome screen. Thanks Ben!
		if ( get_transient( 'pum_activation_redirect' ) ) {
			$do_redirect  = true;
			$current_page = isset( $_GET['page'] ) ? wp_unslash( $_GET['page'] ) : false;
			// Bailout redirect during these events.
			if ( wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_options' ) ) {
				$do_redirect = false;
			}
			// Bailout redirect on these pages & events.
			if ( 'pum-welcome' === $current_page || isset( $_GET['activate-multi'] ) ) {
				delete_transient( 'pum_activation_redirect' );
				$do_redirect = false;
			}
			if ( $do_redirect ) {
				delete_transient( 'pum_activation_redirect' );
				update_option( 'pum_seen_welcome', 1 );
				wp_safe_redirect( admin_url( 'admin.php?page=pum-welcome' ) );
				exit;
			}
		}
	}

	/**
	 * Adds our welcome page to the dashboard
	 *
	 * @since 1.14.0
	 */
	public static function set_up_welcome_page() {
		add_dashboard_page( '', '', 'manage_options', 'pum-welcome', array( __CLASS__, 'display_welcome_page' ) );
	}

	/**
	 * Displays the contents for the welcome page
	 *
	 * @since 1.14.0
	 */
	public static function display_welcome_page() {
		wp_enqueue_style( 'pum-admin-general' );
		$gravatar_url = get_avatar_url( 'danieliser@wizardinternetsolutions.com', array( 'size' => 60 ) );
		?>
		<div class="pum-welcome-wrapper">
			<div>
				<h1>Welcome to Popup Maker!</h1>
			</div>
			<div>
				<p>Popup Maker was created to help us create effective popups on our own WordPress sites to boost our conversions. Now, over 4 years later, the plugin is installed on <strong>over 600,000 websites and has over 3,900 5-star reviews</strong>.</p>
				<p>There are a lot of ways you can use Popup Maker within your site including:</p>
				<ul>
					<li>Adding an auto-opening announcement popup</li>
					<li>Growing your email list with opt-in or lead magnet popups</li>
					<li>Increase order size by recommending products in a WooCommerce cross-sell popup</li>
					<li>Adding a content upgrade to your blog posts</li>
					<li>Greet a visitor from ProductHunt</li>
					<li>Reduce cart abandonment on your WooCommerce checkout page</li>
					<li>Adding post-sale WooCommerce surveys</li>
					<li>Using scroll-triggered popups to ask a site visitor if they have any questions</li>
					<li>And much more!</li>
				</ul>
				<p>Feel free to reach out if we can help with anything. We look forward to helping you increase your site’s conversions!</p>
				<div class="pum-welcome-signature">
					<img src="<?php echo esc_url( $gravatar_url ); ?>" alt="Daniel Iser, founder of Popup Maker">
					<p>~ Daniel and the Popup Maker team</p>
				</div>
			</div>
			<div class="pum-welcome-cta">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=popup' ) ); ?>">Create your first popup!</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Retrieves all dismissed pointers by user
	 *
	 * @param int|bool $user_id The ID of the user or false for current user.
	 * @return array The array of pointer ID's that have been dimissed.
	 * @since 1.11.0
	 */
	private static function get_dismissed_pointers( $user_id = false ) {
		if ( false === $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( 0 === intval( $user_id ) ) {
			return array();
		}
		$pointers = explode( ',', (string) get_user_meta( $user_id, 'dismissed_wp_pointers', true ) );
		if ( ! is_array( $pointers ) ) {
			return array();
		}
		return $pointers;
	}

	/**
	 * Whether or not we should show tip alert
	 *
	 * @return bool True if the alert should be shown
	 * @since 1.13.0
	 */
	public static function should_show_tip() {
		return pum_is_admin_page() && current_user_can( 'manage_options' ) && strtotime( self::get_installed_on() . ' +3 days' ) < time() && ! self::has_turned_off_tips();
	}

	/**
	 * Checks to see if site has turned off PM tips
	 *
	 * @return bool True if site has disabled tips
	 * @since 1.13.0
	 */
	public static function has_turned_off_tips() {
		return true === pum_get_option( 'disable_tips', false ) || 1 === intval( pum_get_option( 'disable_tips', false ) );
	}

	/**
	 * Get the datetime string for when PM was installed.
	 *
	 * @return string
	 * @since 1.13.0
	 */
	public static function get_installed_on() {
		$installed_on = get_option( 'pum_installed_on', false );
		if ( ! $installed_on ) {
			$installed_on = current_time( 'mysql' );
		}
		return $installed_on;
	}

	/**
	 * Ensures pointer is set up correctly.
	 *
	 * @param array $pointer The pointer.
	 * @return bool
	 * @since 1.11.0
	 */
	private static function is_pointer_valid( $pointer ) {
		return ! empty( $pointer ) && ! empty( $pointer['pointer_id'] ) && ! empty( $pointer['target'] ) && ! empty( $pointer['options'] );
	}
}
