<?php
/**
 * Notifications service — parent orchestrator.
 *
 * Owns the core notification providers (WhatsNew, FeatureAnnouncements)
 * and exposes a filter that lets addons (Pro, Pro+, integrations) plug
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
	 * Currently booted providers.
	 *
	 * Useful for debugging and for addons that want to swap or decorate
	 * specific providers after registration.
	 *
	 * @return array<int,Provider>
	 */
	public function get_providers() {
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
