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
		add_filter( 'views_edit-popup', array( __CLASS__, 'addon_tabs' ), 10, 1 );
		add_filter( 'views_edit-popup_theme', array( __CLASS__, 'addon_tabs' ), 10, 1 );
		add_filter( 'pum_popup_settings_fields', array( __CLASS__, 'popup_promotional_fields' ) );
		add_filter( 'pum_theme_settings_fields', array( __CLASS__, 'theme_promotional_fields' ) );
		add_action( 'in_admin_header', array( __CLASS__, 'notice_bar_display' ) );
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
							array(
								'a' => array(
									'href'   => array(),
									'rel'    => array(),
									'target' => array(),
								),
							)
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
		$detection_map = array(
			// Pro+ integrations (from Pro to Pro+).
			'pro_plus' => array(
				'ecommerce' => array(
					'WooCommerce'            => class_exists( 'WooCommerce' ),
					'Easy Digital Downloads' => class_exists( 'Easy_Digital_Downloads' ),
				),
				'lms'       => array(
					'LifterLMS' => class_exists( 'LifterLMS' ),
				),
			),
			// Pro integrations (from Free to Pro).
			'pro'      => array(
				'crm' => array(
					'FluentCRM' => defined( 'FLUENTCRM' ),
				),
			),
		);

		$integrations = array(
			'pro_plus' => array(
				'ecommerce' => array(),
				'lms'       => array(),
			),
			'pro'      => array(
				'crm' => array(),
			),
		);

		foreach ( $detection_map as $tier => $categories ) {
			foreach ( $categories as $category => $plugins ) {
				foreach ( $plugins as $label => $is_detected ) {
					if ( $is_detected ) {
						$integrations[ $tier ][ $category ][] = $label;
					}
				}
			}
		}

		// Remove empty categories.
		foreach ( $integrations as $tier => $categories ) {
			$integrations[ $tier ] = array_filter( $categories );
		}

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
	 * Get the highest priority notice bar trigger that meets all conditions.
	 *
	 * @since 1.21.3
	 *
	 * @return array|false Trigger array or false if no trigger matches.
	 */
	private static function get_current_notice_bar_trigger() {
		$triggers = self::get_notice_bar_triggers();

		// Loop through groups by priority (highest first).
		foreach ( $triggers as $group_key => $group ) {
			// Loop through triggers within group by priority (highest first).
			foreach ( $group['triggers'] as $trigger_key => $trigger ) {
				// Check if all conditions are met.
				if ( ! in_array( false, $trigger['conditions'], true ) ) {
					return $trigger;
				}
			}
		}

		return false;
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
		$pro_is_active   = \PopupMaker\plugin()->is_pro_active();
		$integrations    = self::detect_integrations();
		$has_ecommerce   = ! empty( $integrations['pro_plus']['ecommerce'] );
		$has_lms         = ! empty( $integrations['pro_plus']['lms'] );
		$has_crm         = ! empty( $integrations['pro']['crm'] );

		// Get form conversion count (will be 0 if service not available).
		$form_count = self::get_form_conversion_count();

		// Get total popup views.
		$popup_views = (int) get_option( 'pum_total_open_count', 0 );

		$triggers = array(

			/*
			 * Group 1: Milestone Achievements (Highest Priority: 100)
			 * Celebration-based messaging for user success milestones.
			 */
			'milestone_achievements'   => array(
				'pri'      => 100,
				'triggers' => array(
					'first_form_conversion' => array(
						'message'      => sprintf(
							/* translators: 1: Opening link tag, 2: Closing link tag. */
							esc_html__( '🎉 Congrats on your first form submission! %1$sIncrease signups by 300-500%% with Exit Intent%2$s and advanced targeting.', 'popup-maker' ),
							'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'first-form-milestone' ) ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						'conditions'   => array(
							1 === $form_count,
						),
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'first-form-milestone' ),
						'utm_campaign' => 'first-form-milestone',
						'pri'          => 100,
					),
					'high_engagement_10k'   => array(
						'message'      => sprintf(
							/* translators: 1: Number of popup views, 2: Opening link tag, 3: Closing link tag. */
							esc_html__( '🚀 Amazing! You\'ve had %1$s popup views! %2$sSee which ones convert best with Pro analytics%3$s.', 'popup-maker' ),
							number_format( $popup_views ),
							'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-10k' ) ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						'conditions'   => array(
							$popup_views >= 10000,
						),
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-10k' ),
						'utm_campaign' => 'high-engagement-10k',
						'pri'          => 95,
					),
					'high_engagement_5k'    => array(
						'message'      => sprintf(
							/* translators: 1: Number of popup views, 2: Opening link tag, 3: Closing link tag. */
							esc_html__( '📊 You\'ve had %1$s popup views! %2$sSee which ones convert best with Pro analytics%3$s.', 'popup-maker' ),
							number_format( $popup_views ),
							'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-5k' ) ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						'conditions'   => array(
							$popup_views >= 5000,
							$popup_views < 10000,
						),
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-5k' ),
						'utm_campaign' => 'high-engagement-5k',
						'pri'          => 90,
					),
					'high_engagement_1k'    => array(
						'message'      => sprintf(
							/* translators: 1: Number of popup views, 2: Opening link tag, 3: Closing link tag. */
							esc_html__( '📈 You\'ve had %1$s popup views! %2$sSee which ones convert best with Pro analytics%3$s.', 'popup-maker' ),
							number_format( $popup_views ),
							'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-1k' ) ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						'conditions'   => array(
							$popup_views >= 1000,
							$popup_views < 5000,
						),
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'high-engagement-1k' ),
						'utm_campaign' => 'high-engagement-1k',
						'pri'          => 85,
					),
				),
			),

			/*
			 * Group 2: Conversion Extrapolation (Priority: 80)
			 * Data-driven messaging showing potential missed conversions.
			 */
			'conversion_extrapolation' => array(
				'pri'      => 80,
				'triggers' => array(
					'ecommerce_exit_intent' => array(
						'message'      => sprintf(
							/* translators: 1: Number of tracked conversions, 2: Estimated additional conversions, 3: Opening link tag, 4: Closing link tag. */
							esc_html__( '💰 You\'ve tracked %1$d conversions. %3$sWith Pro+ Exit Intent, you could capture ~%2$d more%4$s!', 'popup-maker' ),
							$form_count,
							max( 1, (int) ( $form_count * 0.33 ) ), // 33% extrapolation.
							'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-exit-intent-extrapolation' ) ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						'conditions'   => array(
							$has_ecommerce,
							$form_count >= 3,
						),
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-exit-intent-extrapolation' ),
						'utm_campaign' => 'ecommerce-exit-intent-extrapolation',
						'pri'          => 100,
					),
					'lms_targeting'         => array(
						'message'      => sprintf(
							/* translators: 1: Number of tracked conversions, 2: Estimated additional conversions, 3: Opening link tag, 4: Closing link tag. */
							esc_html__( '🎓 You\'ve tracked %1$d enrollments. %3$sWith Pro+ LMS targeting, you could capture ~%2$d more%4$s!', 'popup-maker' ),
							$form_count,
							max( 1, (int) ( $form_count * 0.4 ) ), // 40% extrapolation for LMS.
							'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'lms-targeting-extrapolation' ) ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						'conditions'   => array(
							$has_lms,
							$form_count >= 3,
						),
						'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'lms-targeting-extrapolation' ),
						'utm_campaign' => 'lms-targeting-extrapolation',
						'pri'          => 90,
					),
				),
			),

			/*
			 * Group 3: Integration Detected (Priority: 60)
			 * Contextual messages based on detected plugins.
			 */
			'integration_detected'     => array(
				'pri'      => 60,
				'triggers' => array(),
			),

			/*
			 * Group 4: Generic Upgrade (Priority: 40)
			 * Fallback messages for users without specific triggers.
			 */
			'generic_upgrade'          => array(
				'pri'      => 40,
				'triggers' => array(),
			),
		);

		// Build integration-detected triggers dynamically.
		if ( $has_ecommerce ) {
			$platform_list = self::format_integration_list( $integrations['pro_plus']['ecommerce'] );
			$triggers['integration_detected']['triggers']['ecommerce'] = array(
				'message'      => sprintf(
					/* translators: 1: Detected ecommerce platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( 'Automate %1$s campaigns with %2$sPopup Maker Pro+ Ecommerce%3$s - unlock cart actions, revenue attribution, and precision targeting.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-detected' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => array( true ), // Already checked via $has_ecommerce.
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'ecommerce-detected' ),
				'utm_campaign' => 'ecommerce-detected',
				'pri'          => 100,
			);
		}

		if ( $has_lms ) {
			$platform_list                                       = self::format_integration_list( $integrations['pro_plus']['lms'] );
			$triggers['integration_detected']['triggers']['lms'] = array(
				'message'      => sprintf(
					/* translators: 1: Detected LMS platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( 'Deliver targeted funnels for %1$s with %2$sPopup Maker Pro+ LMS%3$s - track enrollments, issue rewards, and automate course journeys.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'lms-detected' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => array( true ), // Already checked via $has_lms.
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'lms-detected' ),
				'utm_campaign' => 'lms-detected',
				'pri'          => 90,
			);
		}

		if ( $has_crm ) {
			$platform_list                                       = self::format_integration_list( $integrations['pro']['crm'] );
			$triggers['integration_detected']['triggers']['crm'] = array(
				'message'      => sprintf(
					/* translators: 1: Detected CRM platforms, 2: Opening link tag, 3: Closing link tag. */
					esc_html__( 'Unlock %1$s integration with %2$sPopup Maker Pro%3$s - connect popups to your CRM workflows and automate lead capture.', 'popup-maker' ),
					$platform_list,
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'crm-detected' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => array( true ), // Already checked via $has_crm.
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'crm-detected' ),
				'utm_campaign' => 'crm-detected',
				'pri'          => 80,
			);
		}

		// Build generic upgrade triggers.
		if ( 'valid' === $license_status && 'pro' === $license_tier ) {
			// Pro users get Pro+ generic message.
			$triggers['generic_upgrade']['triggers']['pro_generic'] = array(
				'message'      => sprintf(
					/* translators: 1: Opening link tag, 2: Closing link tag. */
					esc_html__( 'Level up with %1$sPopup Maker Pro+%2$s - unlock ecommerce automation, revenue attribution, and enhanced targeting.', 'popup-maker' ),
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'pro-generic-upgrade' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => array( true ),
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'pro-generic-upgrade' ),
				'utm_campaign' => 'pro-generic-upgrade',
				'pri'          => 100,
			);
		} else {
			// Free users or Pro with invalid license get free generic message.
			$triggers['generic_upgrade']['triggers']['free_generic'] = array(
				'message'      => sprintf(
					/* translators: 1: Opening link tag, 2: Closing link tag. */
					esc_html__( 'Unlock advanced features with %1$sPopup Maker Pro & Pro+%2$s - Enhanced targeting, revenue tracking, live analytics, and more.', 'popup-maker' ),
					'<a href="' . esc_url( \PopupMaker\generate_upgrade_url( 'notice-bar', 'free-generic-upgrade' ) ) . '" target="_blank" rel="noopener">',
					'</a>'
				),
				'conditions'   => array( true ),
				'link'         => \PopupMaker\generate_upgrade_url( 'notice-bar', 'free-generic-upgrade' ),
				'utm_campaign' => 'free-generic-upgrade',
				'pri'          => 90,
			);
		}

		// Sort groups by priority (highest first).
		uasort( $triggers, array( __CLASS__, 'rsort_by_priority' ) );

		// Sort triggers within each group by priority (highest first).
		foreach ( $triggers as $group_key => $group ) {
			if ( ! empty( $group['triggers'] ) ) {
				uasort( $triggers[ $group_key ]['triggers'], array( __CLASS__, 'rsort_by_priority' ) );
			}
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
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Service not available, return 0.
			unset( $e ); // Prevent unused variable warning.
		}

		return 0;
	}

	/**
	 * Sort array in reverse by priority value (highest first).
	 *
	 * @since 1.21.3
	 *
	 * @param array{pri?: int} $a First array to compare.
	 * @param array{pri?: int} $b Second array to compare.
	 * @return int Comparison result.
	 */
	private static function rsort_by_priority( $a, $b ) {
		if ( ! isset( $a['pri'] ) || ! isset( $b['pri'] ) || $a['pri'] === $b['pri'] ) {
			return 0;
		}

		return ( $a['pri'] < $b['pri'] ) ? 1 : -1;
	}

	/**
	 * Generate targeted upgrade messages based on detected integrations.
	 *
	 * @deprecated 1.21.3 Use get_notice_bar_triggers() priority system instead.
	 *
	 * @param string $user_tier Current user tier ('free', 'pro', 'pro_plus').
	 * @return string Targeted upgrade message or empty string.
	 */
	private static function get_integration_messages( $user_tier ) {
		$upgrade_link = admin_url( 'edit.php?post_type=popup&page=pum-settings#go-pro' );
		$integrations = self::detect_integrations();
		$messages     = array();

		// For Pro users, show Pro+ integration opportunities.
		if ( in_array( $user_tier, array( 'free', 'pro' ), true ) && ! empty( $integrations['pro_plus'] ) ) {
			foreach ( $integrations['pro_plus'] as $category => $platforms ) {
				if ( ! empty( $platforms ) ) {
					$platform_list = self::format_integration_list( $platforms );

					switch ( $category ) {
						case 'ecommerce':
							$messages[] = sprintf(
								/* translators: 1: Detected ecommerce platforms, 2: Opening link tag, 3: Closing link tag. */
								esc_html__( 'Automate %1$s campaigns with %2$sPopup Maker Pro+ Ecommerce%3$s - unlock cart actions, revenue attribution, and precision targeting.', 'popup-maker' ),
								$platform_list,
								'<a href="' . esc_url( $upgrade_link ) . '">',
								'</a>'
							);
							break;

						case 'lms':
							$messages[] = sprintf(
								/* translators: 1: Detected LMS platforms, 2: Opening link tag, 3: Closing link tag. */
								esc_html__( 'Deliver targeted funnels for %1$s with %2$sPopup Maker Pro+ LMS%3$s - track enrollments, issue rewards, and automate course journeys.', 'popup-maker' ),
								$platform_list,
								'<a href="' . esc_url( $upgrade_link ) . '">',
								'</a>'
							);
							break;
					}
				}
			}
		}

		// For Free users, show Pro integration opportunities.
		if ( 'free' === $user_tier && ! empty( $integrations['pro'] ) ) {
			foreach ( $integrations['pro'] as $category => $platforms ) {
				if ( ! empty( $platforms ) ) {
					$platform_list = self::format_integration_list( $platforms );

					switch ( $category ) {
						case 'crm':
							$messages[] = sprintf(
								/* translators: 1: Detected CRM platforms, 2: Opening link tag, 3: Closing link tag. */
								esc_html__( 'Unlock %1$s integration with %2$sPopup Maker Pro%3$s - connect popups to your CRM workflows and automate lead capture.', 'popup-maker' ),
								$platform_list,
								'<a href="' . esc_url( $upgrade_link ) . '">',
								'</a>'
							);
							break;
					}
				}
			}
		}

		// Randomly select one message if available.
		if ( ! empty( $messages ) ) {
			$random_index = array_rand( $messages );
			return $messages[ $random_index ];
		}

		return '';
	}

	/**
	 * Get upgrade message for Pro users based on detected integrations.
	 *
	 * @deprecated 1.21.3 Use get_notice_bar_triggers() priority system instead.
	 *
	 * @return string Targeted upgrade message.
	 */
	private static function get_pro_integration_message() {
		$integration_message = self::get_integration_messages( 'pro' );

		if ( ! empty( $integration_message ) ) {
			return $integration_message;
		}

		// Generic Pro+ upgrade message fallback.
		$upgrade_link = admin_url( 'edit.php?post_type=popup&page=pum-settings#go-pro' );
		return sprintf(
			/* translators: %s - Wraps ending in link to pro settings page. */
			esc_html__( 'Level up with %1$sPopup Maker Pro+%2$s - unlock ecommerce automation, revenue attribution, and enhanced targeting.', 'popup-maker' ),
			'<a href="' . esc_url( $upgrade_link ) . '">',
			'</a>'
		);
	}

	/**
	 * Get upgrade message for free users.
	 *
	 * @deprecated 1.21.3 Use get_notice_bar_triggers() priority system instead.
	 *
	 * @return string General upgrade message.
	 */
	private static function get_free_upgrade_message() {
		// Try to get an integration-specific message first.
		$integration_message = self::get_integration_messages( 'free' );

		if ( ! empty( $integration_message ) ) {
			return $integration_message;
		}

		// Generic upgrade message fallback.
		$upgrade_link = admin_url( 'edit.php?post_type=popup&page=pum-settings#go-pro' );
		return sprintf(
			/* translators: %s - Wraps ending in link to pro settings page. */
			esc_html__( 'Unlock advanced features with %1$sPopup Maker Pro & Pro+%2$s - Enhanced targeting, revenue tracking, live analytics, and more.', 'popup-maker' ),
			'<a href="' . esc_url( $upgrade_link ) . '">',
			'</a>'
		);
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
	public static function popup_promotional_fields( $tabs = array() ) {
		if ( ! pum_extension_enabled( 'forced-interaction' ) && ! pum_extension_enabled( 'pro' ) ) {
			/* translators: %s url to product page. */
			$message = sprintf( __( 'Want to disable the close button? Check out <a href="%s" target="_blank">Popup Maker Pro</a>!', 'popup-maker' ), 'https://wppopupmaker.com/pricing/?utm_source=plugin-theme-editor&utm_medium=text-link&utm_campaign=upsell&utm_content=close-button-settings' );

			// TODO Rewrite this for PM Pro instead of extension.

			$promotion = array(
				'type'     => 'html',
				'content'  => '<img src="' . pum_asset_url( 'images/upsell-icon-forced-interaction.png' ) . '" />' . $message,
				'priority' => 999,
				'class'    => 'pum-upgrade-tip',
			);

			$tabs['close']['button']['fi_promotion']            = $promotion;
			$tabs['close']['forms']['fi_promotion']             = $promotion;
			$tabs['close']['alternate_methods']['fi_promotion'] = $promotion;
		}

		if ( ! pum_extension_enabled( 'advanced-targeting-conditions' ) ) {
			/* translators: %s url to product page. */
			$message = sprintf( __( 'Need more <a href="%s" target="_blank">advanced targeting</a> options?', 'popup-maker' ), 'https://wppopupmaker.com/extensions/advanced-targeting-conditions/?utm_campaign=upsell&utm_source=plugin-popup-editor&utm_medium=text-link&utm_content=conditions-editor' );

			$tabs['targeting']['main']['atc_promotion'] = array(
				'type'     => 'html',
				'content'  => '<img class="pum-upgrade-icon" src="' . pum_asset_url( 'images/mark.svg' ) . '" />' . $message,
				'priority' => 999,
				'class'    => 'pum-upgrade-tip',
			);
		}

		return $tabs;
	}

	/**
	 * Adds messages throughout Popup Theme UI
	 *
	 * @param array $tabs The tabs/fields for popup theme.
	 * @return array
	 */
	public static function theme_promotional_fields( $tabs = array() ) {

		if ( ! pum_extension_enabled( 'advanced-theme-builder' ) && ! class_exists( 'PUM_ATB' ) ) {
			foreach ( array( 'overlay', 'container', 'close' ) as $tab ) {
				/* translators: %s url to product page. */
				$message = __( 'Want to use <a href="%s" target="_blank">background images</a>?', 'popup-maker' );

				$tabs[ $tab ]['background']['atc_promotion'] = array(
					'type'     => 'html',
					'content'  => '<img src="' . pum_asset_url( 'images/upsell-icon-advanted-theme-builder.png' ) . '" height="28" />' . sprintf( $message, 'https://wppopupmaker.com/extensions/advanced-theme-builder/?utm_campaign=upsell&utm_source=plugin-theme-editor&utm_medium=text-link&utm_content=' . $tab . '-settings' ),
					'priority' => 999,
					'class'    => 'pum-upgrade-tip',
				);
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
			$upgrade_tab = array(
				'name'  => esc_html__( 'Go Pro', 'popup-maker' ),
				'url'   => admin_url( 'edit.php?post_type=popup&page=pum-settings#go-pro' ),
				'class' => 'pum-upgrade-tab pum-upgrade-tab-pro',
			);

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

			$tabs = array(
				'popups' => array(
					'name' => esc_html( $popup_labels['name'] ),
					'url'  => admin_url( 'edit.php?post_type=popup' ),
				),
				'themes' => array(
					'name' => esc_html( $theme_labels['name'] ),
					'url'  => admin_url( 'edit.php?post_type=popup_theme' ),
				),
			);

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
