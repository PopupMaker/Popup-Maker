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
	 * Guard so `init()` is idempotent — defensive against multiple `init`
	 * action fires or repeated manual calls.
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * Boot the service — gather providers and wire each one.
	 *
	 * Safe to call multiple times; the guard ensures providers only hook
	 * their WordPress events on the first call.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$this->providers = $this->resolve_providers();

		foreach ( $this->providers as $provider ) {
			if ( $provider instanceof Provider ) {
				$provider->init();
			}
		}
	}

	/**
	 * Boot notifications immediately in wp-admin or defer them to relevant frontend hooks.
	 *
	 * @return void
	 */
	public function register_lazy_boot() {
		if ( is_admin() ) {
			$this->init();
			return;
		}

		foreach ( $this->get_deferred_boot_hooks() as $hook ) {
			if ( did_action( $hook ) || did_filter( $hook ) ) {
				// The event already fired earlier in this request — e.g. an
				// upgrade dispatches popup_maker/update_version during
				// plugins_loaded, before this registration runs on init.
				$this->init();
				return;
			}

			add_filter( $hook, [ $this, 'boot_on_demand' ], PHP_INT_MIN );
		}
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
	 * @param mixed $value Current filter value, if any.
	 * @return mixed
	 */
	public function boot_on_demand( $value = null ) {
		$this->init();

		return $value;
	}

	/**
	 * Currently booted providers.
	 *
	 * Useful for debugging and for addons that want to swap or decorate
	 * specific providers after registration.
	 *
	 * @return array<int,Provider>
	 */
	public function get_providers() {
		$this->init();

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
