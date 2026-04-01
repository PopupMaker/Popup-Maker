<?php
/**
 * Class for Upsell
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

use function PopupMaker\plugin;
/**
 * Handles displaying promotional text throughout plugin UI
 */
class PUM_Upsell {

	/**
	 * Hooks any needed methods
	 */
	public static function init() {
		// Kill switch — disable all upsells if constant is defined.
		if ( defined( 'POPUP_MAKER_DISABLE_UPSELLS' ) && POPUP_MAKER_DISABLE_UPSELLS ) {
			return;
		}

		add_filter( 'views_edit-popup', [ __CLASS__, 'addon_tabs' ], 10, 1 );
		add_filter( 'views_edit-popup_theme', [ __CLASS__, 'addon_tabs' ], 10, 1 );
		add_filter( 'pum_popup_settings_fields', [ __CLASS__, 'popup_promotional_fields' ] );
		add_filter( 'pum_theme_settings_fields', [ __CLASS__, 'theme_promotional_fields' ] );
		add_action( 'in_admin_header', [ __CLASS__, 'notice_bar_display' ] );

		// Premium feature previews — only when Pro is NOT active.
		if ( ! \PopupMaker\plugin()->is_pro_active() ) {
			add_filter( 'pum_registered_triggers', [ __CLASS__, 'register_preview_triggers' ] );
			add_filter( 'pum_registered_conditions', [ __CLASS__, 'register_preview_conditions' ] );
			add_filter( 'popup_maker/cta_types_as_array', [ __CLASS__, 'register_preview_cta_types' ] );
			add_action( 'pum_popup_analytics_metabox_after', [ __CLASS__, 'render_analytics_teaser' ] );
			add_filter( 'pum_admin_vars', [ __CLASS__, 'localize_premium_preview_data' ] );
		}
	}

	/**
	 * Adds a small notice bar in PM admin areas when not using any extensions
	 *
	 * @since 1.14.0
	 */
	public static function notice_bar_display() {
		if ( pum_is_admin_page() ) {
			// Temporarily disable for CTA post type screens.
			if ( isset( $_GET['page'] ) && 'popup-maker-call-to-actions' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			// Disable it on post.php edit screens for popup editor & popup theme editor. post.php?post=850&action=edit | post-new.php?post_type=popup.
			$screen = get_current_screen();
			if ( 'post' === $screen->base && ( 'popup' === $screen->post_type || 'popup_theme' === $screen->post_type ) ) {
				return;
			}

			// Generate appropriate upsell message.
			$message = self::generate_upgrade_message();

			if ( empty( $message ) ) {
				return;
			}

			wp_enqueue_style( 'pum-admin-general' );
			?>
			<div class="pum-notice-bar-wrapper">
				<div class="pum-notice-bar">
					<span class="pum-notice-bar-message">
						<?php
						echo wp_kses(
							$message,
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						);
						?>
					</span>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Detect installed plugins that can be integrated with Popup Maker.
	 *
	 * @return array<string, array<string, string[]>>
	 */
	private static function detect_integrations() {
		return \PUM_Admin_Helpers::detect_integrations();

		return array_filter( $integrations );
	}

	/**
	 * Generate appropriate upgrade message based on current license and plugin status.
	 *
	 * Uses priority-based trigger system for targeted, engaging upgrade messaging.
	 *
	 * @since 1.21.3 Refactored to use priority-based trigger system.
	 *
	 * @return string Upgrade message or empty string if no message should be shown.
	 */
	private static function generate_upgrade_message() {
		$license_service = \PopupMaker\plugin( 'license' );
		$license_tier    = $license_service->get_license_tier();
		$license_status  = $license_service->get_license_status();

		$active_extensions  = pum_enabled_extensions();
		$has_active_add_ons = ! empty( $active_extensions );

		/**
		 * 1. Pro Plus users with valid license see nothing.
		 * 2. Extension users get no message for now (regardless of license status).
		 * 3. All other users (Free, Pro with invalid license, Pro with valid license) use priority system.
		 */

		// 1. Pro Plus users with valid license see nothing.
		if ( 'valid' === $license_status && 'pro_plus' === $license_tier ) {
			return '';
		}

		// 2. Extension users get no message for now.
		if ( $has_active_add_ons ) {
			return '';
		}

		// 3. Use priority-based trigger system for all other users.
		$trigger = self::get_current_notice_bar_trigger();

		if ( empty( $trigger ) ) {
			return '';
		}

		return $trigger['message'];
	}

	/**
	 * Get a random matching notice bar trigger.
	 *
	 * Collects all triggers whose conditions are met and returns one at random,
	 * so users see different messages across page loads.
	 *
	 * @since 1.21.3
	 *
	 * @return array|false Trigger array or false if no trigger matches.
	 */
	private static function get_current_notice_bar_trigger() {
		$triggers = self::get_notice_bar_triggers();

		// Collect all matching triggers across all groups.
		$matching = [];
		foreach ( $triggers as $group ) {
			foreach ( $group['triggers'] as $trigger ) {
				if ( ! in_array( false, $trigger['conditions'], true ) ) {
					$matching[] = $trigger;
				}
			}
		}

		if ( empty( $matching ) ) {
			return false;
		}

		// Randomize so users see different messages across page loads.
		return $matching[ array_rand( $matching ) ];
	}

	/**
	 * Get notice bar triggers organized by priority groups.
	 *
	 * Trigger groups are sorted by group priority (highest first).
	 * Triggers within each group are sorted by trigger priority (highest first).
	 *
	 * @since 1.21.3
	 *
	 * @return array<string, array{pri: int, triggers: array<string, array{message: string, conditions: bool[], link: string, utm_campaign: string, pri: int}>}> Trigger groups.
	 */
	private static function get_notice_bar_triggers() {
		static $triggers;

		if ( isset( $triggers ) ) {
			return $triggers;
		}

		$license_service = \PopupMaker\plugin( 'license' );
		$license_tier    = $license_service->get_license_tier();
		$license_status  = $license_service->get_license_status();
		$integrations    = self::detect_integrations();
		$has_ecommerce   = ! empty( $integrations['pro_plus']['ecommerce'] );
		$has_lms         = ! empty( $integrations['pro_plus']['lms'] );
		$has_crm         = ! empty( $integrations['pro']['crm'] );

		// Get form conversion count (will be 0 if service not available).
		$form_count = self::get_form_conversion_count();

		// Get total popup views.
		$popup_views = (int) get_option( 'pum_total_open_count', 0 );

		// New installs (after form tracking shipped) get celebration messaging.
		// Existing installs get "tracking is now live" messaging instead.
		$installed_on   = get_option( 'pum_installed_on', '' );
		$is_new_install = ! empty( $installed_on ) && strtotime( $installed_on ) >= strtotime( '2026-03-25' );

		$triggers = [

			/*
			 * Group 1: Milestone Achievements (Highest Priority: 100)
			 * Celebration-based messaging for user success milestones.
			 */
			'milestone_achievements' => [
				'pri'      => 100,
				'triggers' => [
					'first_form_conversion' => [
						'message'      => $is_new_install
							? sprintf(
								/* translators: 1: Opening link tag, 2: Closing link tag. */
								esc_html__( '🎉 Congrats on your first form submission! %1$sUpgrade to Pro%2$s for exit intent triggers, conversion analytics, and advanced targeting.', 'popup-maker' ),
								'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'first-form-milestone' ) ) . '" target="_blank" rel="noopener">',
								'</a>'
							)
							: sprintf(
								/* translators: 1: Opening link tag, 2: Closing link tag. */
								esc_html__( '📊 Form conversion tracking is now live! Your first submission has been captured. %1$sSee what\'s converting with Pro analytics%2$s.', 'popup-maker' ),
								'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'first-form-milestone' ) ) . '" target="_blank" rel="noopener">',
								'</a>'
							),
						'conditions'   => [
							1 === $form_count,
						],
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'first-form-milestone' ),
						'utm_campaign' => 'first-form-milestone',
						'pri'          => 100,
					],
					'high_engagement_10k'   => [
						'message'      => sprintf(
							/* translators: 1: Number of popup views, 2: Opening link tag, 3: Closing link tag. */
							esc_html__( '🚀 Amazing! You\'ve had %1$s popup views! %2$sSee which ones convert best with Pro analytics%3$s.', 'popup-maker' ),
							number_format( $popup_views ),
							'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-10k' ) ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						'conditions'   => [
							$popup_views >= 10000,
						],
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-10k' ),
						'utm_campaign' => 'high-engagement-10k',
						'pri'          => 95,
					],
					'high_engagement_5k'    => [
						'message'      => sprintf(
							/* translators: 1: Number of popup views, 2: Opening link tag, 3: Closing link tag. */
							esc_html__( '📊 You\'ve had %1$s popup views! %2$sSee which ones convert best with Pro analytics%3$s.', 'popup-maker' ),
							number_format( $popup_views ),
							'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-5k' ) ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						'conditions'   => [
							$popup_views >= 5000,
							$popup_views < 10000,
						],
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-5k' ),
						'utm_campaign' => 'high-engagement-5k',
						'pri'          => 90,
					],
					'high_engagement_1k'    => [
						'message'      => sprintf(
							/* translators: 1: Number of popup views, 2: Opening link tag, 3: Closing link tag. */
							esc_html__( '📈 You\'ve had %1$s popup views! %2$sSee which ones convert best with Pro analytics%3$s.', 'popup-maker' ),
							number_format( $popup_views ),
							'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-1k' ) ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						'conditions'   => [
							$popup_views >= 1000,
							$popup_views < 5000,
						],
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-1k' ),
						'utm_campaign' => 'high-engagement-1k',
						'pri'          => 85,
					],
				],
			],

			/*
			 * Group 2: Integration Detected (Priority: 60)
			 * Contextual messages based on detected plugins.
			 */
			'integration_detected'   => [
				'pri'      => 60,
				'triggers' => [],
			],

			/*
			 * Group 4: Generic Upgrade (Priority: 40)
			 * Fallback messages for users without specific triggers.
			 */
			'generic_upgrade'        => [
				'pri'      => 40,
				'triggers' => [],
			],
		];

		// Build integration-detected triggers dynamically.
		if ( $has_ecommerce ) {
			$platform_list = self::format_integration_list( $integrations['pro_plus']['ecommerce'] );
			$triggers['integration_detected']['triggers']['ecommerce_carts']   = [
				'message'      => sprintf(
					/* translators: 1: Detected ecommerce platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( '%1$s detected — recover abandoned carts, trigger discount popups, and track revenue per popup with %2$sPro+ Ecommerce Popups%3$s.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-carts' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => [ true ],
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-carts' ),
				'utm_campaign' => 'ecommerce-carts',
				'pri'          => 100,
			];
			$triggers['integration_detected']['triggers']['ecommerce_revenue'] = [
				'message'      => sprintf(
					/* translators: 1: Detected ecommerce platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( 'Track exactly which popups drive %1$s sales with %2$sPro+ Ecommerce Popups%3$s — revenue attribution, purchase targeting, and conversion analytics.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-revenue' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => [ true ],
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-revenue' ),
				'utm_campaign' => 'ecommerce-revenue',
				'pri'          => 90,
			];
			$triggers['integration_detected']['triggers']['ecommerce_upsell']  = [
				'message'      => sprintf(
					/* translators: 1: Detected ecommerce platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( 'Show personalized offers to %1$s customers based on cart contents and purchase history with %2$sPro+ Ecommerce Popups%3$s.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-upsell' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => [ true ],
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-upsell' ),
				'utm_campaign' => 'ecommerce-upsell',
				'pri'          => 80,
			];
		}

		if ( $has_lms ) {
			$platform_list = self::format_integration_list( $integrations['pro_plus']['lms'] );
			$triggers['integration_detected']['triggers']['lms_enrollment'] = [
				'message'      => sprintf(
					/* translators: 1: Detected LMS platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( '%1$s detected — boost course enrollment, target students by progress, and track signups per popup with %2$sPro+ LMS Popups%3$s.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'lms-enrollment' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => [ true ],
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'lms-enrollment' ),
				'utm_campaign' => 'lms-enrollment',
				'pri'          => 90,
			];
			$triggers['integration_detected']['triggers']['lms_targeting']  = [
				'message'      => sprintf(
					/* translators: 1: Detected LMS platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( 'Show the right offer at the right time — target %1$s students by enrollment status, course progress, and membership with %2$sPro+ LMS Popups%3$s.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'lms-targeting' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => [ true ],
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'lms-targeting' ),
				'utm_campaign' => 'lms-targeting',
				'pri'          => 80,
			];
		}

		if ( $has_crm ) {
			$platform_list = self::format_integration_list( $integrations['pro']['crm'] );
			$triggers['integration_detected']['triggers']['crm_tagging']    = [
				'message'      => sprintf(
					/* translators: 1: Detected CRM platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( '%1$s detected — auto-tag subscribers, trigger email sequences from popups, and sync leads with %2$sPopup Maker Pro%3$s.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'crm-tagging' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => [ true ],
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'crm-tagging' ),
				'utm_campaign' => 'crm-tagging',
				'pri'          => 80,
			];
			$triggers['integration_detected']['triggers']['crm_automation'] = [
				'message'      => sprintf(
					/* translators: 1: Detected CRM platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( 'Connect popups to %1$s workflows — automatically add contacts, apply tags, and start automations when visitors convert with %2$sPopup Maker Pro%3$s.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'crm-automation' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => [ true ],
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'crm-automation' ),
				'utm_campaign' => 'crm-automation',
				'pri'          => 70,
			];
		}

		// Build generic upgrade triggers.
		if ( 'valid' === $license_status && 'pro' === $license_tier ) {
			// Pro users get Pro+ generic message.
			$triggers['generic_upgrade']['triggers']['pro_generic'] = [
				'message'      => sprintf(
					/* translators: 1: Opening link tag, 2: Closing link tag. */
					esc_html__( 'Level up with %1$sPopup Maker Pro+%2$s - unlock ecommerce automation, revenue attribution, and enhanced targeting.', 'popup-maker' ),
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'pro-generic-upgrade' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => [ true ],
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'pro-generic-upgrade' ),
				'utm_campaign' => 'pro-generic-upgrade',
				'pri'          => 100,
			];
		} else {
			// Free users or Pro with invalid license get free generic message.
			$triggers['generic_upgrade']['triggers']['free_generic'] = [
				'message'      => sprintf(
					/* translators: 1: Opening link tag, 2: Closing link tag. */
					esc_html__( 'Unlock advanced features with %1$sPopup Maker Pro & Pro+%2$s - Enhanced targeting, revenue tracking, live analytics, and more.', 'popup-maker' ),
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'free-generic-upgrade' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => [ true ],
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'free-generic-upgrade' ),
				'utm_campaign' => 'free-generic-upgrade',
				'pri'          => 90,
			];
		}

		return $triggers;
	}

	/**
	 * Get site-wide form conversion count.
	 *
	 * @since 1.21.3
	 *
	 * @return int Form conversion count, or 0 if not available.
	 */
	private static function get_form_conversion_count() {
		try {
			$form_tracking = \PopupMaker\plugin( 'form_conversion_tracking' );
			if ( $form_tracking && method_exists( $form_tracking, 'get_site_count' ) ) {
				return $form_tracking->get_site_count();
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Service not available, graceful fallback.
			unset( $e );
		}

		return 0;
	}

	/**
	 * Convert detected platform names into a readable list.
	 *
	 * @param string[] $items List of platform names.
	 * @return string
	 */
	private static function format_integration_list( array $items ) {
		$items = array_values( array_filter( $items ) );

		if ( empty( $items ) ) {
			return '';
		}

		if ( function_exists( 'wp_sprintf_l' ) ) {
			return wp_sprintf_l( '%l', $items );
		}

		$count = count( $items );
		if ( 1 === $count ) {
			return $items[0];
		}

		$last = array_pop( $items );
		return sprintf(
			/* translators: 1: List of platforms, 2: Last platform. */
			esc_html__( '%1$s and %2$s', 'popup-maker' ),
			implode( esc_html__( ', ', 'popup-maker' ), $items ),
			$last
		);
	}

	/**
	 * Adds messages throughout Popup Settings UI
	 *
	 * @param array $tabs The tabs/fields for popup settings.
	 * @return array
	 */
	public static function popup_promotional_fields( $tabs = [] ) {
		if ( ! pum_extension_enabled( 'forced-interaction' ) && ! pum_extension_enabled( 'pro' ) ) {
			/* translators: %s url to product page. */
			$message = sprintf( __( 'Want to disable the close button? Check out <a href="%s" target="_blank">Popup Maker Pro</a>!', 'popup-maker' ), 'https://wppopupmaker.com/pricing/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=upsell&utm_content=close-button-settings' );

			// TODO Rewrite this for PM Pro instead of extension.

			$promotion = [
				'type'     => 'html',
				'content'  => '<img src="' . pum_asset_url( 'images/upsell-icon-forced-interaction.png' ) . '" />' . $message,
				'priority' => 999,
				'class'    => 'pum-upgrade-tip',
			];

			$tabs['close']['button']['fi_promotion']            = $promotion;
			$tabs['close']['forms']['fi_promotion']             = $promotion;
			$tabs['close']['alternate_methods']['fi_promotion'] = $promotion;
		}

		if ( ! \PopupMaker\plugin()->is_pro_active() ) {
			/* translators: %s url to product page. */
			$message = sprintf( __( 'Unlock 50+ advanced targeting conditions with <a href="%s" target="_blank" rel="noopener">Popup Maker Pro</a> — user roles, device detection, URL matching, referrer targeting, and more.', 'popup-maker' ), 'https://wppopupmaker.com/extensions/advanced-targeting-conditions/?utm_campaign=upsell&utm_source=plugin-popup-editor&utm_medium=text-link&utm_content=conditions-editor' );

			$tabs['targeting']['main']['atc_promotion'] = [
				'type'     => 'html',
				'content'  => '<img class="pum-upgrade-icon" src="' . pum_asset_url( 'images/mark.svg' ) . '" />' . $message,
				'priority' => 999,
				'class'    => 'pum-upgrade-tip',
			];
		}

		return $tabs;
	}

	/**
	 * Adds messages throughout Popup Theme UI
	 *
	 * @param array $tabs The tabs/fields for popup theme.
	 * @return array
	 */
	public static function theme_promotional_fields( $tabs = [] ) {

		if ( ! \PopupMaker\plugin()->is_pro_active() ) {
			foreach ( [ 'overlay', 'container', 'close' ] as $tab ) {
				/* translators: %s url to product page. */
				$message = sprintf(
					__( 'Unlock background images, parallax effects, and advanced styling with <a href="%s" target="_blank" rel="noopener">Popup Maker Pro</a>.', 'popup-maker' ),
					'https://wppopupmaker.com/extensions/advanced-theme-builder/?utm_campaign=upsell&utm_source=plugin-theme-editor&utm_medium=text-link&utm_content=' . $tab . '-settings'
				);

				$tabs[ $tab ]['background']['atc_promotion'] = [
					'type'     => 'html',
					'content'  => '<img class="pum-upgrade-icon" src="' . pum_asset_url( 'images/mark.svg' ) . '" />' . $message,
					'priority' => 999,
					'class'    => 'pum-upgrade-tip',
				];
			}
		}

		return $tabs;
	}

	/**
	 * When the Popup or Popup Theme list table loads, call the function to view our tabs.
	 *
	 * @since 1.8.0
	 * @param array $views An array of available list table views.
	 * @return mixed
	 */
	public static function addon_tabs( $views ) {
		self::display_addon_tabs();

		return $views;
	}

	/**
	 * Displays the tabs for 'Popups', 'Popup Themes' and 'Extensions and Integrations'
	 *
	 * @since 1.8.0
	 */
	/**
	 * Register greyed-out preview triggers for Pro/Pro+ features.
	 *
	 * Shows locked triggers in the trigger picker to demonstrate Pro value.
	 * Each trigger has pro_required flag so JS can intercept selection.
	 *
	 * @since 1.22.0
	 *
	 * @param array $triggers Registered triggers.
	 * @return array Modified triggers with preview entries.
	 */
	public static function register_preview_triggers( $triggers ) {
		// Marketing copy — not translatable (English-only upsell content).
		$preview_triggers = [
			'exit_intent'  => [
				'name'            => __( 'Exit Intent', 'popup-maker' ),
				'modal_title'     => 'Exit Intent Settings',
				'settings_column' => 'Exit Intent',
				'pro_required'    => true,
				'pro_tier'        => 'pro',
				'fields'          => [ 'general' => [] ],
				'pro_description' => "Stop losing visitors \u{2014} catch them before they leave.",
				'pro_bullets'     => [
					'Mouse leave detection',
					'Back button interception',
					'Mobile scroll-up + time delay',
					'Sensitivity tuning',
				],
			],
			'scroll'       => [
				'name'            => __( 'Scroll Trigger', 'popup-maker' ),
				'modal_title'     => 'Scroll Trigger Settings',
				'settings_column' => 'Scroll Trigger',
				'pro_required'    => true,
				'pro_tier'        => 'pro',
				'fields'          => [ 'general' => [] ],
				'pro_description' => 'Show the right message at exactly the right moment.',
				'pro_bullets'     => [
					'Trigger by scroll depth (px or %)',
					'Detect when elements become visible',
					'Auto-close when user scrolls back up',
				],
			],
			'time_on_site' => [
				'name'            => __( 'Time on Site', 'popup-maker' ),
				'modal_title'     => 'Time on Site Settings',
				'settings_column' => 'Time on Site',
				'pro_required'    => true,
				'pro_tier'        => 'pro',
				'fields'          => [ 'general' => [] ],
				'pro_description' => "Engage visitors who are actually interested \u{2014} not just passing through.",
				'pro_bullets'     => [
					'Set any delay from 1 second to 5 minutes',
					'Only count active mouse/keyboard time',
					'Ignore idle or background tabs',
				],
			],
		];

		// Add ecommerce trigger when WooCommerce or EDD detected.
		$integrations  = self::detect_integrations();
		$has_ecommerce = ! empty( $integrations['pro_plus']['ecommerce'] );

		if ( $has_ecommerce ) {
			$preview_triggers['product_added_to_cart'] = [
				'name'            => __( 'Item Added to Cart', 'popup-maker' ),
				'modal_title'     => 'Add to Cart Trigger Settings',
				'settings_column' => 'Item Added to Cart',
				'pro_required'    => true,
				'pro_tier'        => 'pro_plus',
				'fields'          => [ 'general' => [] ],
				'pro_description' => 'Turn every add-to-cart into an upsell opportunity.',
				'pro_bullets'     => [
					'Target specific products',
					'Upsell complementary items',
					'Free shipping threshold nudges',
				],
			];
		}

		// Append lock + tier label to preview trigger names for dropdown display.
		foreach ( $preview_triggers as $id => &$trigger ) {
			$tier_label       = 'pro_plus' === $trigger['pro_tier'] ? 'Pro+' : 'Pro';
			$trigger['name'] .= " \xF0\x9F\x94\x92 {$tier_label}";
		}
		unset( $trigger );

		return array_merge( $triggers, $preview_triggers );
	}

	/**
	 * Register greyed-out preview conditions for Pro/Pro+ features.
	 *
	 * Shows locked conditions in the targeting picker grouped by category.
	 * Only the most impactful conditions are shown, not the full Pro set.
	 *
	 * @since 1.22.0
	 *
	 * @param array $conditions Registered conditions.
	 * @return array Modified conditions with preview entries.
	 */
	public static function register_preview_conditions( $conditions ) {
		// Marketing copy — not translatable (English-only upsell content).
		// Only group names and condition names are translated (visible in UI dropdowns).
		$preview_conditions = [
			// User Targeting (Pro).
			'user_is_logged_in'         => [
				'group'           => __( 'User Targeting (Pro)', 'popup-maker' ),
				'name'            => __( 'User Is Logged In', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro',
				'fields'          => [],
				'pro_description' => "Know exactly who you're talking to \u{2014} personalize every interaction.",
				'pro_bullets'     => [
					'Show offers only to logged-in users',
					'Hide signup forms for existing members',
					'Combine with role targeting for precision',
				],
			],
			'user_has_role'             => [
				'group'           => __( 'User Targeting (Pro)', 'popup-maker' ),
				'name'            => __( 'User Has Role', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro',
				'fields'          => [],
				'pro_description' => 'Different users deserve different experiences.',
				'pro_bullets'     => [
					'Show VIP content to specific roles',
					'Hide admin notices from subscribers',
					'Segment by membership tier',
				],
			],

			// Behavior Targeting (Pro).
			'referrer_is_search_engine' => [
				'group'           => __( 'Behavior Targeting (Pro)', 'popup-maker' ),
				'name'            => __( 'Came From Search Engine', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro',
				'advanced'        => true,
				'fields'          => [],
				'pro_description' => "First impressions matter \u{2014} greet search visitors with the right offer.",
				'pro_bullets'     => [
					'Tailored offers for search traffic',
					'Different CTAs by traffic source',
					'Detects Google, Bing, Yahoo, and more',
				],
			],
			'device_is_mobile'          => [
				'group'           => __( 'Behavior Targeting (Pro)', 'popup-maker' ),
				'name'            => __( 'Is Mobile Device', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro',
				'advanced'        => true,
				'fields'          => [],
				'pro_description' => "Mobile visitors behave differently \u{2014} your popups should too.",
				'pro_bullets'     => [
					'Mobile-optimized popup designs',
					'Separate phone vs tablet targeting',
					'Avoid intrusive interstitials on mobile',
				],
			],
			'url_contains'              => [
				'group'           => __( 'Behavior Targeting (Pro)', 'popup-maker' ),
				'name'            => __( 'URL Contains', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro',
				'advanced'        => true,
				'fields'          => [],
				'pro_description' => "Precision targeting \u{2014} show the right popup on the right page.",
				'pro_bullets'     => [
					'Match UTM parameters for campaign targeting',
					'Target specific URL paths or sections',
					'Combine with other conditions for precision',
				],
			],
			// Form Engagement (Pro).
			'is_filling_form'           => [
				'group'           => __( 'Behavior Targeting (Pro)', 'popup-maker' ),
				'name'            => __( 'Is Filling Out a Form', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro',
				'advanced'        => true,
				'fields'          => [],
				'pro_description' => 'Recover form abandoners before they walk away.',
				'pro_bullets'     => [
					'Detect active form engagement',
					'Set minimum fields touched threshold',
					'Combine with Exit Intent for abandonment recovery',
				],
			],
		];

		// Add ecommerce conditions when WooCommerce or EDD detected.
		$integrations  = self::detect_integrations();
		$has_ecommerce = ! empty( $integrations['pro_plus']['ecommerce'] );
		$has_lms       = ! empty( $integrations['pro_plus']['lms'] );

		if ( $has_ecommerce ) {
			$preview_conditions['product_in_cart']        = [
				'group'           => __( 'Ecommerce (Pro+)', 'popup-maker' ),
				'name'            => __( 'Product In Cart', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro_plus',
				'fields'          => [],
				'pro_description' => "Every cart is a conversion opportunity \u{2014} don't waste it.",
				'pro_bullets'     => [
					'Cross-sell related products',
					'Upsell higher-value alternatives',
					'Target specific product combinations',
				],
			];
			$preview_conditions['cart_total']             = [
				'group'           => __( 'Ecommerce (Pro+)', 'popup-maker' ),
				'name'            => __( 'Cart Total', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro_plus',
				'fields'          => [],
				'pro_description' => 'Boost average order value with smart threshold targeting.',
				'pro_bullets'     => [
					'Free shipping threshold nudges',
					'Discount offers at checkout',
					'Compare subtotal, tax, or total',
				],
			];
			$preview_conditions['customer_has_purchased'] = [
				'group'           => __( 'Ecommerce (Pro+)', 'popup-maker' ),
				'name'            => __( 'Customer Has Purchased', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro_plus',
				'fields'          => [],
				'pro_description' => 'Reward loyalty, re-engage lapsed buyers, exclude past purchasers.',
				'pro_bullets'     => [
					'Welcome back returning customers',
					'Exclude past purchasers from promos',
					'Target by specific products bought',
				],
			];
			$preview_conditions['customer_spent']         = [
				'group'           => __( 'Ecommerce (Pro+)', 'popup-maker' ),
				'name'            => __( 'Customer Lifetime Spend', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro_plus',
				'fields'          => [],
				'pro_description' => 'Segment customers by their total spending history.',
				'pro_bullets'     => [
					'VIP offers for high-value customers',
					'Win-back campaigns for low spenders',
					'Tiered promotions based on lifetime value',
				],
			];
		}

		if ( $has_lms ) {
			$preview_conditions['llms_student_course_enrollment'] = [
				'group'           => __( 'LMS (Pro+)', 'popup-maker' ),
				'name'            => __( 'Student Course Enrollment', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro_plus',
				'fields'          => [],
				'pro_cta'         => 'Boost My Course Sales',
				'pro_description' => 'Meet students where they are in their learning journey.',
				'pro_bullets'     => [
					'Promote advanced courses to completers',
					'Re-engage students who dropped off',
					'Target by specific course enrollment',
				],
			];
			$preview_conditions['llms_cart_total']                = [
				'group'           => __( 'LMS (Pro+)', 'popup-maker' ),
				'name'            => __( 'LMS Cart Total', 'popup-maker' ),
				'callback'        => '__return_false',
				'pro_required'    => true,
				'pro_tier'        => 'pro_plus',
				'fields'          => [],
				'pro_cta'         => 'Boost My Course Sales',
				'pro_description' => 'Increase course revenue with targeted cart offers.',
				'pro_bullets'     => [
					'Bundle discount nudges at checkout',
					'Upsell memberships to course buyers',
					'Target by cart value threshold',
				],
			];
		}

		// Append lock indicator to preview condition names for dropdown display.
		foreach ( $preview_conditions as $id => &$condition ) {
			$condition['name'] .= " \xF0\x9F\x94\x92";
		}
		unset( $condition );

		return array_merge( $conditions, $preview_conditions );
	}

	/**
	 * Register locked preview CTA types for the CTA editor type picker.
	 *
	 * Injects disabled options into the CTA types array so they appear
	 * greyed-out in the React SelectControl dropdown.
	 *
	 * @since 1.22.0
	 *
	 * @param array $cta_types Registered CTA types as arrays.
	 * @return array Modified CTA types with preview entries.
	 */
	public static function register_preview_cta_types( $cta_types ) {
		$integrations  = self::detect_integrations();
		$has_ecommerce = ! empty( $integrations['pro_plus']['ecommerce'] );
		$has_lms       = ! empty( $integrations['pro_plus']['lms'] );
		$has_crm       = ! empty( $integrations['pro']['crm'] );

		$pro_url      = \PopupMaker\generate_upgrade_url( 'cta-editor', 'pro-cta-type' );
		$pro_plus_url = \PopupMaker\generate_upgrade_url( 'cta-editor', 'pro-plus-cta-type' );

		// FluentCRM CTA types (Pro).
		if ( $has_crm ) {
			$cta_types['fluentcrm_add_tag']     = [
				'key'             => 'fluentcrm_add_tag',
				'label'           => 'FluentCRM: Add Tag (Pro)',
				'pro_required'    => true,
				'pro_description' => 'Automatically tag contacts in FluentCRM when visitors interact with your CTAs.',
				'pro_cta'         => 'Increase My Conversion Rate',
				'upgrade_url'     => $pro_url,
				'fields'          => [],
			];
			$cta_types['fluentcrm_add_to_list'] = [
				'key'             => 'fluentcrm_add_to_list',
				'label'           => 'FluentCRM: Add to List (Pro)',
				'pro_required'    => true,
				'pro_description' => 'Add visitors to FluentCRM lists directly from popup CTAs.',
				'pro_cta'         => 'Increase My Conversion Rate',
				'upgrade_url'     => $pro_url,
				'fields'          => [],
			];
		}

		// Ecommerce CTA types (Pro+).
		if ( $has_ecommerce ) {
			$cta_types['ecom_add_to_cart']    = [
				'key'             => 'ecom_add_to_cart',
				'label'           => 'Add to Cart (Pro+)',
				'pro_required'    => true,
				'pro_description' => 'Add products directly to the cart from popup CTAs — works with WooCommerce and Easy Digital Downloads.',
				'pro_cta'         => 'Boost My Revenue',
				'upgrade_url'     => $pro_plus_url,
				'fields'          => [],
			];
			$cta_types['ecom_apply_discount'] = [
				'key'             => 'ecom_apply_discount',
				'label'           => 'Apply Discount Code (Pro+)',
				'pro_required'    => true,
				'pro_description' => 'Auto-apply discount codes when visitors click your CTA — works with WooCommerce and Easy Digital Downloads.',
				'pro_cta'         => 'Boost My Revenue',
				'upgrade_url'     => $pro_plus_url,
				'fields'          => [],
			];
		}

		// LMS CTA types (Pro+).
		if ( $has_lms ) {
			$cta_types['llms_enroll_course']     = [
				'key'             => 'llms_enroll_course',
				'label'           => 'Enroll in Course (Pro+)',
				'pro_required'    => true,
				'pro_description' => 'Enroll students directly from popup CTAs.',
				'pro_cta'         => 'Boost My Course Sales',
				'upgrade_url'     => $pro_plus_url,
				'fields'          => [],
			];
			$cta_types['llms_enroll_membership'] = [
				'key'             => 'llms_enroll_membership',
				'label'           => 'Enroll in Membership (Pro+)',
				'pro_required'    => true,
				'pro_description' => 'Sign up members directly from popup CTAs.',
				'pro_cta'         => 'Boost My Course Sales',
				'upgrade_url'     => $pro_plus_url,
				'fields'          => [],
			];
		}

		return $cta_types;
	}

	/**
	 * Render analytics teaser below the popup editor sidebar analytics box.
	 *
	 * @since 1.22.0
	 *
	 * @param int $popup_id The popup ID.
	 */
	public static function render_analytics_teaser( $popup_id ) {
		$upgrade_url = \PopupMaker\generate_upgrade_url( 'analytics-teaser', 'popup-editor-sidebar' );
		/* translators: %s url to product page. */
		$message = sprintf( __( 'Track trends over time with <a href="%s" target="_blank" rel="noopener">Pro Analytics</a> — charts, funnels, and revenue attribution.', 'popup-maker' ), esc_url( $upgrade_url ) );
		?>
		<div class="pum-upgrade-tip" style="font-size:12px;line-height:1.5 !important;">
			<img class="pum-upgrade-icon" src="<?php echo esc_url( pum_asset_url( 'images/mark.svg' ) ); ?>" style="height:1.4em;margin-right:6px;" />
			<?php echo wp_kses_post( $message ); ?>
		</div>
		<?php
	}

	/**
	 * Add premium preview data to pum_admin_vars for JS consumption.
	 *
	 * @since 1.22.0
	 *
	 * @param array $vars Admin vars.
	 * @return array Modified admin vars with premium preview data.
	 */
	public static function localize_premium_preview_data( $vars ) {
		$vars['premium_previews'] = self::get_premium_preview_data();
		return $vars;
	}

	/**
	 * Get premium preview modal content data.
	 *
	 * Returns structured data for each preview feature, consumed by
	 * premium-previews.js to render upsell modals.
	 *
	 * @since 1.22.0
	 *
	 * @return array Premium preview data keyed by feature ID.
	 */
	private static function get_premium_preview_data() {
		$data = [
			'triggers'   => [],
			'conditions' => [],
		];

		// Default CTAs per tier — features can override with pro_cta.
		$default_ctas = [
			'pro'      => 'Increase My Conversion Rate',
			'pro_plus' => 'Boost My Revenue',
		];

		// Build trigger preview data from registered preview triggers.
		$all_triggers = \PUM_Triggers::instance()->get_triggers();
		foreach ( $all_triggers as $id => $trigger ) {
			if ( empty( $trigger['pro_required'] ) ) {
				continue;
			}
			$tier                    = $trigger['pro_tier'] ?? 'pro';
			$data['triggers'][ $id ] = [
				'label'       => $trigger['name'],
				'tier'        => $tier,
				'description' => $trigger['pro_description'] ?? '',
				'bullets'     => $trigger['pro_bullets'] ?? [],
				'cta'         => $trigger['pro_cta'] ?? $default_ctas[ $tier ] ?? $default_ctas['pro'],
				'upgrade_url' => \PopupMaker\generate_upgrade_url( 'feature-preview', 'trigger-' . $id ),
			];
		}

		// Build condition preview data from registered preview conditions.
		$all_conditions = \PUM_Conditions::instance()->get_conditions();
		foreach ( $all_conditions as $id => $condition ) {
			if ( empty( $condition['pro_required'] ) ) {
				continue;
			}
			$tier                      = $condition['pro_tier'] ?? 'pro';
			$data['conditions'][ $id ] = [
				'label'       => $condition['name'],
				'group'       => $condition['group'],
				'tier'        => $tier,
				'description' => $condition['pro_description'] ?? '',
				'bullets'     => $condition['pro_bullets'] ?? [],
				'cta'         => $condition['pro_cta'] ?? $default_ctas[ $tier ] ?? $default_ctas['pro'],
				'upgrade_url' => \PopupMaker\generate_upgrade_url( 'feature-preview', 'condition-' . $id ),
			];
		}

		// Marketing copy — not translatable.
		$data['all_features_url']  = \PopupMaker\generate_upgrade_url( 'feature-preview', 'see-all-features' );
		$data['all_features_text'] = 'See All Pro Features';

		$data['I10n'] = [
			'pro_feature' => 'Pro',
			'pro_plus'    => 'Pro+',
		];

		return $data;
	}

	public static function display_addon_tabs() {
		// Get labels for the Popup and Popup Theme post types.
		$popup_labels = (array) get_post_type_labels( get_post_type_object( plugin( 'PostTypes' )->get_type_key( 'popup' ) ) );
		$theme_labels = (array) get_post_type_labels( get_post_type_object( plugin( 'PostTypes' )->get_type_key( 'popup_theme' ) ) );

		?>
		<style>
			.wrap h1.wp-heading-inline + a.page-title-action {
				display: none;
			}

			.edit-php.post-type-popup .wrap .nav-tab-wrapper .page-title-action, .edit-php.post-type-popup_theme .wrap .nav-tab-wrapper .page-title-action, .popup_page_pum-extensions .wrap .nav-tab-wrapper .page-title-action {
				top: 7px;
				margin-left: 5px
			}

			@media only screen and (min-width: 0px) and (max-width: 783px) {
				.edit-php.post-type-popup .wrap .nav-tab-wrapper .page-title-action, .edit-php.post-type-popup_theme .wrap .nav-tab-wrapper .page-title-action, .popup_page_pum-extensions .wrap .nav-tab-wrapper .page-title-action {
					display: none !important
				}
			}
		</style>
		<nav class="nav-tab-wrapper">
			<?php
			// Default upgrade tab configuration.
			$upgrade_tab = [
				'name'  => esc_html__( 'Go Pro', 'popup-maker' ),
				'url'   => admin_url( 'edit.php?post_type=popup&page=pum-settings#go-pro' ),
				'class' => 'pum-upgrade-tab pum-upgrade-tab-pro',
			];

			// Adjust based on license status.
			try {
				$license_service = \PopupMaker\plugin( 'license' );
				$license_status  = $license_service->get_license_status();
				$license_tier    = $license_service->get_license_tier();

				if ( 'valid' === $license_status ) {
					if ( 'pro_plus' === $license_tier ) {
						$upgrade_tab = null; // Pro Plus - hide upgrade tab.
					} elseif ( 'pro' === $license_tier ) {
						$upgrade_tab['name']  = esc_html__( 'Go Pro+', 'popup-maker' );
						$upgrade_tab['class'] = 'pum-upgrade-tab pum-upgrade-tab-pro-plus';
					}
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Use default configuration if license service unavailable.
				unset( $e ); // Prevent unused variable warning.
			}

			$tabs = [
				'popups' => [
					'name' => esc_html( $popup_labels['name'] ),
					'url'  => admin_url( 'edit.php?post_type=popup' ),
				],
				'themes' => [
					'name' => esc_html( $theme_labels['name'] ),
					'url'  => admin_url( 'edit.php?post_type=popup_theme' ),
				],
			];

			// Only add upgrade tab if not Pro Plus.
			if ( $upgrade_tab ) {
				$tabs['integrations'] = $upgrade_tab;
			}

			$tabs = apply_filters( 'pum_add_ons_tabs', $tabs );

			$active_tab = false;

			// Calculate which tab is currently active.

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['page'] ) && 'pum-extensions' === $_GET['page'] ) {
				$active_tab = 'integrations';
			} elseif ( ! isset( $_GET['page'] ) && isset( $_GET['post_type'] ) ) {
				switch ( $_GET['post_type'] ) {
					case 'popup':
						$active_tab = 'popups';
						break;
					case 'popup_theme':
						$active_tab = 'themes';
						break;
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			// Add each tab, marking the current one as active.
			foreach ( $tabs as $tab_id => $tab ) {
				$active      = $active_tab === $tab_id ? ' nav-tab-active' : '';
				$extra_class = isset( $tab['class'] ) ? ' ' . esc_attr( $tab['class'] ) : '';
				?>
				<a href="<?php echo esc_url( $tab['url'] ); ?>" class="nav-tab<?php echo esc_attr( $active . $extra_class ); ?>">
					<?php echo esc_html( $tab['name'] ); ?>
				</a>
				<?php
			}
			?>

			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=popup' ) ); ?>" class="page-title-action">
				<?php echo esc_html( $popup_labels['add_new_item'] ); ?>
			</a>

			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=popup_theme' ) ); ?>" class="page-title-action">
				<?php echo esc_html( $theme_labels['add_new_item'] ); ?>
			</a>
		</nav>
		<?php
	}
}
