<?php
/**
 * Notifications service — parent orchestrator.
 *
 * Owns the core notification providers and exposes a filter that lets addons
 * (Pro, Pro+, integrations) plug
 * in their own providers without touching core code.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Services\Notifications;

use PopupMaker\Base\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Notifications orchestrator service.
 *
 * Usage from an addon:
 *
 *     add_filter(
 *         'popup_maker/notification_providers',
 *         function ( $providers, $container ) {
 *             $providers[] = new \PopupMakerPro\Notifications\MyProvider( $container );
 *             return $providers;
 *         },
 *         10,
 *         2
 *     );
 *
 * Each provider must implement
 * \PopupMaker\Services\Notifications\Provider and is expected to wire
 * its own hooks (typically into `pum_alert_list`) inside its own
 * `init()` method.
 *
 * Lifecycle: Core calls `init()` once on `init` priority 5 — late enough
 * that addons loading on `plugins_loaded` priority 12+ have already had
 * their window to register providers and deferred boot hooks. From there
 * this class owns the boot decision: immediate in wp-admin, lazy on the
 * frontend. Provider resolution itself happens once, in `boot()`.
 *
 * @since 1.23.0
 */
class Manager extends Service {

	/**
	 * Providers this service has booted this request.
	 *
	 * @var array<int,Provider>
	 */
	protected $providers = [];

	/**
	 * Guard so provider resolution happens at most once per request —
	 * defensive against multiple `init` action fires or repeated manual calls.
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * Hooks this service registered for deferred frontend boot, so they can
	 * be released once the manager actually boots.
	 *
	 * @var string[]
	 */
	protected $deferred_hooks = [];

	/**
	 * Initialize the notifications lifecycle.
	 *
	 * This is the single entry point Core calls (on `init` priority 5). The
	 * manager — not the caller — decides how notifications come up:
	 *
	 * - wp-admin: boot immediately, since something is about to render alerts.
	 * - Frontend: stay dormant and boot lazily if a notification-relevant
	 *   event actually fires, so the typical frontend request never pays for
	 *   provider construction.
	 *
	 * Safe to call repeatedly; provider hooks are only wired on first boot.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->booted ) {
			return;
		}

		if ( is_admin() ) {
			$this->boot();
			return;
		}

		$this->register_deferred_boot();
	}

	/**
	 * Resolve and initialize providers exactly once.
	 *
	 * Every path that needs live providers funnels through here — immediate
	 * admin boot, a deferred frontend hook, or an explicit `get_providers()`
	 * call — so provider resolution has one home and one guard.
	 *
	 * @return void
	 */
	protected function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		// Deferred triggers have done their job; stop listening so later hook
		// fires don't keep calling back into an already-booted manager.
		$this->release_deferred_boot();

		$this->providers = $this->resolve_providers();

		foreach ( $this->providers as $provider ) {
			if ( $provider instanceof Provider ) {
				$provider->init();
			}
		}
	}

	/**
	 * Listen for the frontend events that justify booting notifications.
	 *
	 * @return void
	 */
	protected function register_deferred_boot() {
		$hooks = $this->get_deferred_boot_hooks();

		/*
		 * An event may already have fired earlier in this request — an upgrade
		 * dispatches popup_maker/update_version during plugins_loaded, before
		 * init@5 gets here. Check every hook before registering any, so a late
		 * match can't leave half the list wired to a booted manager.
		 */
		foreach ( $hooks as $hook ) {
			if ( did_action( $hook ) || did_filter( $hook ) ) {
				$this->boot();
				return;
			}
		}

		foreach ( $hooks as $hook ) {
			$this->deferred_hooks[] = $hook;
			add_filter( $hook, [ $this, 'boot_on_demand' ], PHP_INT_MIN );
		}
	}

	/**
	 * Detach the deferred boot listeners registered for this request.
	 *
	 * @return void
	 */
	protected function release_deferred_boot() {
		foreach ( $this->deferred_hooks as $hook ) {
			remove_filter( $hook, [ $this, 'boot_on_demand' ], PHP_INT_MIN );
		}

		$this->deferred_hooks = [];
	}

	/**
	 * Get hooks that can trigger lazy notification boot on frontend requests.
	 *
	 * Extensions (Pro, Pro+, legacy) can append their own trigger hooks via the
	 * `popup_maker/notifications/deferred_boot_hooks` filter. Any consumer that
	 * calls get_providers() boots the manager regardless, so this filter is an
	 * optimization and not a correctness requirement.
	 *
	 * @return string[]
	 */
	protected function get_deferred_boot_hooks() {
		$defaults = [
			'popup_maker/update_version',
			'pum_alert_list',
			'pum_alert_dismissed',
			'save_post_popup',
			'save_post_pum_cta',
			'deleted_post',
			'trashed_post',
			'untrashed_post',
			'update_option_pum_form_conversion_count',
			'update_option_pum_total_conversion_count',
			'update_option_pum_bypass_adblockers',
			'activated_plugin',
			'deactivated_plugin',
		];

		return apply_filters( 'popup_maker/notifications/deferred_boot_hooks', $defaults );
	}

	/**
	 * Boot notification providers when a frontend request reaches a relevant hook.
	 *
	 * Hooked to arbitrary third-party-reachable filters, so it stays
	 * permissive about its argument and returns it untouched.
	 *
	 * @param mixed $value Current filter value, if any.
	 * @return mixed
	 */
	public function boot_on_demand( $value = null ) {
		$this->boot();

		return $value;
	}

	/**
	 * Boot notifications immediately in wp-admin or defer them to relevant frontend hooks.
	 *
	 * @deprecated 1.25.0 Use `init()`, which now owns this decision.
	 *
	 * @return void
	 */
	public function register_lazy_boot() {
		$this->init();
	}

	/**
	 * Currently booted providers.
	 *
	 * Useful for debugging and for addons that want to swap or decorate
	 * specific providers after registration.
	 *
	 * Asking for the providers is an explicit demand for them, so this forces
	 * a boot rather than going through `init()` — on a frontend request
	 * `init()` would only arm the deferred listeners and hand back an empty
	 * list.
	 *
	 * @return array<int,Provider>
	 */
	public function get_providers() {
		$this->boot();

		return $this->providers;
	}

	/**
	 * Build the core provider list and let addons append their own.
	 *
	 * @return array<int,Provider>
	 */
	protected function resolve_providers() {
		$core = [
			new WhatsNew( $this->container ),
			new PageBuilderAnnouncements( $this->container ),
			new FeatureAnnouncements( $this->container ),
		];

		/**
		 * Filters the list of notification providers before they are booted.
		 *
		 * Addons (Pro, Pro+, integrations) can register their own providers
		 * by appending instances of
		 * \PopupMaker\Services\Notifications\Provider to the array.
		 * Non-conforming entries are silently skipped.
		 *
		 * @since 1.23.0
		 *
		 * @param Provider[]              $providers Provider instances to boot.
		 * @param \PopupMaker\Plugin\Core $container Plugin container so addons can inject dependencies.
		 * @return Provider[]
		 */
		$providers = apply_filters( 'popup_maker/notification_providers', $core, $this->container );

		if ( ! is_array( $providers ) ) {
			return $core;
		}

		// Filter out anything that isn't a real provider to keep the boot
		// chain honest — better to drop a bad entry than to crash on init().
		$filtered = [];
		foreach ( $providers as $provider ) {
			if ( $provider instanceof Provider ) {
				$filtered[] = $provider;
			}
		}

		return $filtered;
	}
}
