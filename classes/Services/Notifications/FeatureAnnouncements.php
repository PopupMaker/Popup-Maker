<?php
/**
 * Feature adoption announcements service.
 *
 * Surfaces newer capabilities to users who likely aren't using them yet.
 * Each announcement has a cheap "is this already being used" check so we
 * don't pester users who've already adopted the feature.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Services\Notifications;

use PopupMaker\Base\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Feature adoption announcements — registers entries into pum_alert_list.
 *
 * @since 1.23.0
 */
class FeatureAnnouncements extends Service implements Provider {

	/**
	 * Minimum total form conversions (site-wide) before the exit-intent
	 * upsell fires. Filters out test fires and idle installs.
	 *
	 * @var int
	 */
	const EXIT_INTENT_MIN_CONVERSIONS = 10;

	/**
	 * Minimum popup count before the scheduling upsell fires.
	 *
	 * @var int
	 */
	const SCHEDULING_MIN_POPUPS = 3;

	/**
	 * Days since last modification that mark a popup as "stale" — i.e. the
	 * user is likely cycling it on and off manually.
	 *
	 * @var int
	 */
	const SCHEDULING_STALE_DAYS = 90;

	/**
	 * Base transient key for the cached computed announcement list.
	 *
	 * The actual key is suffixed with the current locale so two admins in
	 * different languages don't stomp each other's translated content.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'pum_feature_announcements';

	/**
	 * Option name storing the list of cache keys we've written — used to
	 * flush every locale-scoped variant when invalidation fires.
	 *
	 * @var string
	 */
	const CACHE_INDEX_OPTION = 'pum_feature_announcements_cache_index';

	/**
	 * Cache TTL. Invalidated on popup save; the TTL is a safety net for
	 * anything we didn't wire invalidation for.
	 *
	 * @var int
	 */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Hook into the alert list filter.
	 *
	 * NOTE: no is_admin() gate — REST requests aren't is_admin() and the
	 * notifications endpoint needs these announcements too. Permission
	 * check lives inside the filter callback so it runs per-request.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pum_alert_list', [ $this, 'register_announcements' ], 15 );

		/*
		 * Any popup or CTA lifecycle event can flip a condition — flush
		 * so the next panel fetch reflects reality instead of stale
		 * cache.
		 */
		add_action( 'save_post_popup', [ $this, 'flush_cache' ] );
		add_action( 'save_post_pum_cta', [ $this, 'flush_cache' ] );
		add_action( 'deleted_post', [ $this, 'maybe_flush_on_deleted_post' ], 10, 2 );
		add_action( 'trashed_post', [ $this, 'maybe_flush_on_trashed_post' ] );
		add_action( 'untrashed_post', [ $this, 'maybe_flush_on_trashed_post' ] );

		/*
		 * Option-backed conditions (form conversion count, ad-block bypass
		 * setting) — flush when they change so the next panel fetch
		 * reflects current state.
		 */
		add_action( 'update_option_pum_form_conversion_count', [ $this, 'flush_cache' ] );
		add_action( 'update_option_pum_bypass_adblockers', [ $this, 'flush_cache' ] );
	}

	/**
	 * Clear the cached announcement list across locales.
	 *
	 * @return void
	 */
	public function flush_cache() {
		foreach ( $this->cache_keys_to_flush() as $key ) {
			delete_transient( $key );
		}
	}

	/**
	 * Flush only when the deleted post is a popup or CTA.
	 *
	 * @param int           $post_id Post ID (unused — post type read from $post).
	 * @param \WP_Post|null $post    Deleted post object.
	 * @return void
	 */
	public function maybe_flush_on_deleted_post( $post_id, $post = null ) {
		unset( $post_id );
		if ( $post instanceof \WP_Post && in_array( $post->post_type, [ 'popup', 'pum_cta' ], true ) ) {
			$this->flush_cache();
		}
	}

	/**
	 * Flush when a popup/CTA is trashed or untrashed.
	 *
	 * @param int $post_id Affected post ID.
	 * @return void
	 */
	public function maybe_flush_on_trashed_post( $post_id ) {
		if ( in_array( get_post_type( (int) $post_id ), [ 'popup', 'pum_cta' ], true ) ) {
			$this->flush_cache();
		}
	}

	/**
	 * Register feature adoption announcements.
	 *
	 * Fetches from transient when possible so we skip all condition
	 * queries and message-building on normal page loads.
	 *
	 * @param array $alerts Existing alerts.
	 * @return array
	 */
	public function register_announcements( $alerts ) {
		if ( ! current_user_can( $this->container->get_permission( 'edit_popups' ) ) ) {
			return $alerts;
		}

		$cache_key = $this->cache_key_for_current_locale();

		$cached = get_transient( $cache_key );
		if ( ! is_array( $cached ) ) {
			$cached = $this->compute_announcements();
			set_transient( $cache_key, $cached, self::CACHE_TTL );
			$this->remember_cache_key( $cache_key );
		}

		return array_merge( $alerts, $cached );
	}

	/**
	 * Current request's cache key — locale-scoped so translated strings
	 * don't leak across admins using different languages.
	 *
	 * @return string
	 */
	protected function cache_key_for_current_locale() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return self::CACHE_KEY . '_' . sanitize_key( (string) $locale );
	}

	/**
	 * Track a cache key so `flush_cache()` can clear every locale variant.
	 *
	 * @param string $key Transient key.
	 * @return void
	 */
	protected function remember_cache_key( $key ) {
		$index = (array) get_option( self::CACHE_INDEX_OPTION, [] );
		if ( ! in_array( $key, $index, true ) ) {
			$index[] = $key;
			update_option( self::CACHE_INDEX_OPTION, $index, false );
		}
	}

	/**
	 * All known cache keys (from the flush index plus the current locale's
	 * key as a safety net).
	 *
	 * @return array<int,string>
	 */
	protected function cache_keys_to_flush() {
		$index   = (array) get_option( self::CACHE_INDEX_OPTION, [] );
		$index[] = $this->cache_key_for_current_locale();
		return array_values( array_unique( $index ) );
	}

	/**
	 * Build the announcement list from scratch — only called on cache miss.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function compute_announcements() {
		$out = [];

		foreach ( $this->definitions() as $announcement ) {
			if ( isset( $announcement['condition'] ) && is_callable( $announcement['condition'] ) && ! call_user_func( $announcement['condition'] ) ) {
				continue;
			}

			$out[] = [
				'code'        => $announcement['code'],
				'type'        => 'info',
				'category'    => $announcement['category'],
				'title'       => $announcement['title'] ?? '',
				'message'     => $announcement['message'] ?? '',
				'subtitle'    => $announcement['subtitle'] ?? '',
				'icon'        => $announcement['icon'] ?? '',
				'priority'    => $announcement['priority'] ?? 40,
				'dismissible' => true,
				'actions'     => $announcement['actions'] ?? [],
			];
		}

		return $out;
	}

	/**
	 * Announcement definitions.
	 *
	 * Only surface features that ship in the FREE version of Popup Maker
	 * here. Pro, Pro+ and addon announcements belong in their own plugins
	 * so they only appear when that plugin is active.
	 *
	 * Upsells in core are ALLOWED when they are behaviorally targeted —
	 * the user has demonstrated usage that makes the Pro capability a
	 * genuine next step. Blanket "Go Pro" notices do not belong here.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function definitions() {
		return [

			// CTAs — new call-to-action system (core/free).
			[
				'code'      => 'pm_feat_ctas_2026',
				'category'  => 'feature',
				'priority'  => 85,
				'condition' => [ $this, 'has_no_ctas' ],
				'title'     => __( 'Try the new Call-to-Action system', 'popup-maker' ),
				'message'   => __( 'Turn links and buttons in your popups into <strong>trackable CTAs</strong> — see which calls-to-action actually get clicked.', 'popup-maker' ),
				'subtitle'  => __( 'new in PM', 'popup-maker' ),
				'icon'      => 'megaphone',
				'actions'   => [
					[
						'text'    => __( 'Create your first CTA', 'popup-maker' ),
						'type'    => 'link',
						'action'  => '',
						'href'    => $this->cta_admin_url(),
						'primary' => true,
					],
					[
						'text'     => __( 'Learn more', 'popup-maker' ),
						'type'     => 'link',
						'action'   => '',
						'href'     => $this->doc_url( 'apply-popup-maker/create-call-to-action-cta-popup', 'ctas' ),
						'external' => true,
					],
				],
			],

			// Exit intent upsell — triggered by proven form conversion volume.
			[
				'code'      => 'pm_upsell_exit_intent',
				'category'  => 'recommendation',
				'priority'  => 80,
				'condition' => [ $this, 'converts_without_exit_intent' ],
				'title'     => __( 'Capture visitors who are about to leave', 'popup-maker' ),
				'message'   => $this->exit_intent_message(),
				'subtitle'  => __( 'based on your conversions', 'popup-maker' ),
				'icon'      => 'chart-line',
				'actions'   => [
					[
						'text'     => __( 'See how exit intent works', 'popup-maker' ),
						'type'     => 'link',
						'action'   => '',
						'href'     => $this->feature_url( 'popup-triggers/exit-intent-triggers', 'exit-intent-feature' ),
						'primary'  => true,
						'external' => true,
					],
					[
						'text'    => __( 'Not now', 'popup-maker' ),
						'type'    => 'action',
						'action'  => 'dismiss',
						'expires' => '30 days',
					],
				],
			],

			// Ad-blocker bypass tip — surfaces a free setting users miss.
			[
				'code'      => 'pm_tip_adblock_bypass',
				'category'  => 'recommendation',
				'priority'  => 78,
				'condition' => [ $this, 'needs_adblock_bypass' ],
				'title'     => __( 'Turn on ad-blocker bypass', 'popup-maker' ),
				'message'   => __( 'Ad blockers can hide your popups from a real chunk of traffic. Popup Maker has a <strong>built-in bypass</strong> that renames our assets and routes so they look less like something to block — takes a minute to enable.', 'popup-maker' ),
				'subtitle'  => __( 'free setting', 'popup-maker' ),
				'icon'      => 'shield-alt',
				'actions'   => [
					[
						'text'    => __( 'Enable bypass', 'popup-maker' ),
						'type'    => 'link',
						'action'  => '',
						'href'    => $this->settings_url( 'misc' ),
						'primary' => true,
					],
					[
						'text'    => __( 'Not now', 'popup-maker' ),
						'type'    => 'action',
						'action'  => 'dismiss',
						'expires' => '30 days',
					],
				],
			],

			// Scheduling upsell — needs multiple popups + stale/disabled signals.
			[
				'code'      => 'pm_upsell_scheduling',
				'category'  => 'recommendation',
				'priority'  => 75,
				'condition' => [ $this, 'needs_popup_scheduling' ],
				'title'     => __( 'Stop manually toggling popups on and off', 'popup-maker' ),
				'message'   => $this->scheduling_message(),
				'subtitle'  => __( 'based on your popup activity', 'popup-maker' ),
				'icon'      => 'calendar-alt',
				'actions'   => [
					[
						'text'     => __( 'See how scheduling works', 'popup-maker' ),
						'type'     => 'link',
						'action'   => '',
						'href'     => $this->feature_url( 'popup-targeting/popup-scheduling', 'scheduling-feature' ),
						'primary'  => true,
						'external' => true,
					],
					[
						'text'    => __( 'Not now', 'popup-maker' ),
						'type'    => 'action',
						'action'  => 'dismiss',
						'expires' => '30 days',
					],
				],
			],

		];
	}

	/*
	 * Condition helpers.
	 * These only run on cache miss (see register_announcements) so their
	 * simplicity matters more than per-call optimization.
	 */

	/**
	 * True when no CTA records exist yet.
	 *
	 * @return bool
	 */
	public function has_no_ctas() {
		if ( ! post_type_exists( 'pum_cta' ) ) {
			return false;
		}

		$ctas = get_posts( [
			'post_type'      => 'pum_cta',
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		return empty( $ctas );
	}

	/**
	 * True when the site has crossed the form conversion floor AND no
	 * popup is using an exit-intent trigger yet.
	 *
	 * @return bool
	 */
	public function converts_without_exit_intent() {
		$total_conversions = (int) get_option( 'pum_form_conversion_count', 0 );

		if ( $total_conversions < self::EXIT_INTENT_MIN_CONVERSIONS ) {
			return false;
		}

		return ! $this->any_popup_uses_exit_intent();
	}

	/**
	 * True when the ad-blocker bypass setting is off AND the site has
	 * actual popup activity worth protecting.
	 *
	 * @return bool
	 */
	public function needs_adblock_bypass() {
		if ( ! function_exists( 'pum_get_option' ) ) {
			return false;
		}

		if ( pum_get_option( 'bypass_adblockers', false ) ) {
			return false;
		}

		$conversions = (int) get_option( 'pum_form_conversion_count', 0 );
		return $conversions >= self::EXIT_INTENT_MIN_CONVERSIONS;
	}

	/**
	 * True when the user has multiple popups AND at least one signal that
	 * they would benefit from scheduling.
	 *
	 * Consumes the memoized scheduling_stats() so the popup scan runs
	 * once per request even when both the condition check and the
	 * message builder fire.
	 *
	 * @return bool
	 */
	public function needs_popup_scheduling() {
		$stats = $this->scheduling_stats();

		if ( $stats['total'] < self::SCHEDULING_MIN_POPUPS ) {
			return false;
		}

		return $stats['disabled'] > 0 || $stats['stale'] > 0;
	}

	/**
	 * True when any published popup has at least one exit-intent trigger.
	 *
	 * @return bool
	 */
	protected function any_popup_uses_exit_intent() {
		$popups = get_posts( [
			'post_type'      => 'popup',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		if ( empty( $popups ) ) {
			return false;
		}

		foreach ( $popups as $popup_id ) {
			$triggers = get_post_meta( (int) $popup_id, 'popup_triggers', true );

			if ( ! is_array( $triggers ) ) {
				continue;
			}

			foreach ( $triggers as $trigger ) {
				if ( isset( $trigger['type'] ) && 'exit_intent' === $trigger['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	// Message builders.

	/**
	 * Exit-intent upsell message with live numbers.
	 *
	 * @return string
	 */
	protected function exit_intent_message() {
		$captured = (int) get_option( 'pum_form_conversion_count', 0 );
		$low      = (int) max( 1, round( $captured * 0.10 ) );
		$high     = (int) max( $low + 1, round( $captured * 0.15 ) );

		return sprintf(
			/* translators: 1: total captured conversions, 2: estimated low lift, 3: estimated high lift */
			__( 'You\'ve captured <strong>%1$s conversions</strong> so far. Exit-intent commonly recovers 10–15%% of abandoning visitors — on your traffic, that\'s another <strong>%2$s–%3$s</strong> left on the table.', 'popup-maker' ),
			number_format_i18n( $captured ),
			number_format_i18n( $low ),
			number_format_i18n( $high )
		);
	}

	/**
	 * Scheduling upsell message with live counts.
	 *
	 * @return string
	 */
	protected function scheduling_message() {
		$stats = $this->scheduling_stats();

		$stale_phrase = sprintf(
			/* translators: %s: number of stale popups */
			_n(
				'<strong>%s popup</strong> hasn\'t been touched in months',
				'<strong>%s popups</strong> haven\'t been touched in months',
				$stats['stale'],
				'popup-maker'
			),
			number_format_i18n( $stats['stale'] )
		);

		$disabled_phrase = sprintf(
			/* translators: %s: number of disabled popups */
			_n(
				'<strong>%s popup</strong> is currently disabled',
				'<strong>%s popups</strong> are currently disabled',
				$stats['disabled'],
				'popup-maker'
			),
			number_format_i18n( $stats['disabled'] )
		);

		if ( $stats['disabled'] > 0 && $stats['stale'] > 0 ) {
			$context = sprintf(
				/* translators: 1: disabled popup phrase, 2: stale popup phrase */
				__( '%1$s and %2$s.', 'popup-maker' ),
				$disabled_phrase,
				$stale_phrase
			);
		} elseif ( $stats['disabled'] > 0 ) {
			$context = $disabled_phrase . '.';
		} else {
			$context = $stale_phrase . '.';
		}

		return sprintf(
			/* translators: 1: total popup count, 2: context sentence describing what fired */
			__( 'You manage <strong>%1$s popups</strong>, and %2$s Scheduling in Pro turns your popups on and off automatically — seasonal sales, launches, business hours — so you never forget to flip a switch.', 'popup-maker' ),
			number_format_i18n( $stats['total'] ),
			$context
		);
	}

	/**
	 * Per-request memoized scheduling stats.
	 *
	 * @var array{total:int,disabled:int,stale:int}|null
	 */
	protected $scheduling_stats_cache = null;

	/**
	 * Counts used by the scheduling upsell message.
	 *
	 * Memoized for the lifetime of the request — both the condition
	 * check and the message builder call this, and the underlying
	 * get_posts() scan is the most expensive thing in this class.
	 *
	 * @return array{total:int,disabled:int,stale:int}
	 */
	protected function scheduling_stats() {
		if ( null !== $this->scheduling_stats_cache ) {
			return $this->scheduling_stats_cache;
		}

		$popups = get_posts( [
			'post_type'      => 'popup',
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		] );

		$stale_threshold = time() - ( self::SCHEDULING_STALE_DAYS * DAY_IN_SECONDS );
		$stats           = [
			'total'    => count( $popups ),
			'disabled' => 0,
			'stale'    => 0,
		];

		foreach ( $popups as $popup ) {
			$enabled = get_post_meta( (int) $popup->ID, 'popup_enabled', true );
			if ( '' !== $enabled && ! $enabled ) {
				++$stats['disabled'];
			}

			$modified = get_post_modified_time( 'U', true, $popup );
			if ( $modified && $modified < $stale_threshold ) {
				++$stats['stale'];
			}
		}

		$this->scheduling_stats_cache = $stats;

		return $stats;
	}

	// URL helpers.

	/**
	 * URL to the CTA admin screen.
	 *
	 * @return string
	 */
	protected function cta_admin_url() {
		return admin_url( 'edit.php?post_type=pum_cta' );
	}

	/**
	 * URL to a specific Popup Maker settings tab.
	 *
	 * Emits both `?tab=<slug>` (server-rendered active tab) AND `#<slug>`
	 * (client-side tab switcher in assets/js/src/admin/settings-page/).
	 * The hash is what actually activates the tab in the DOM; the query
	 * param keeps the URL semantically correct.
	 *
	 * @param string $tab Settings tab slug.
	 * @return string
	 */
	protected function settings_url( $tab ) {
		$slug = rawurlencode( $tab );
		return admin_url( 'edit.php?post_type=popup&page=pum-settings&tab=' . $slug ) . '#' . $slug;
	}

	/**
	 * UTM-tagged docs URL.
	 *
	 * @param string $path     Docs path (no leading slash).
	 * @param string $campaign UTM campaign.
	 * @return string
	 */
	protected function doc_url( $path, $campaign ) {
		return add_query_arg(
			[
				'utm_source'   => 'pm-notifications',
				'utm_medium'   => 'panel',
				'utm_campaign' => $campaign,
			],
			'https://wppopupmaker.com/docs/' . ltrim( $path, '/' ) . '/'
		);
	}

	/**
	 * UTM-tagged feature landing page URL on wppopupmaker.com.
	 *
	 * Educational destination for "See how X works" style CTAs —
	 * informational, not a pricing grid. Each feature page carries
	 * its own on-page upgrade CTA that routes to /pricing/.
	 *
	 * @param string $slug     Feature slug (e.g. 'exit-intent').
	 * @param string $campaign UTM campaign identifier.
	 * @return string
	 */
	protected function feature_url( $slug, $campaign ) {
		return add_query_arg(
			[
				'utm_source'   => 'pm-notifications',
				'utm_medium'   => 'panel',
				'utm_campaign' => $campaign,
			],
			'https://wppopupmaker.com/features/' . ltrim( $slug, '/' ) . '/'
		);
	}

	/**
	 * Upgrade URL — reuses the central Popup Maker upgrade link helper.
	 *
	 * @param string $campaign UTM campaign identifier.
	 * @return string
	 */
	protected function upgrade_url( $campaign ) {
		$args = [
			'utm_source'   => 'pm-notifications',
			'utm_medium'   => 'panel',
			'utm_campaign' => $campaign,
		];

		if ( function_exists( '\\PopupMaker\\get_upgrade_link' ) ) {
			return \PopupMaker\get_upgrade_link( $args );
		}

		return add_query_arg( $args, 'https://wppopupmaker.com/pricing/' );
	}
}
