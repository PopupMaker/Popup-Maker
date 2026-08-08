<?php
/**
 * Page builder provider registry.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Services;

use PopupMaker\Interfaces\BuilderProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Collects page builder providers and exposes the available ones.
 *
 * Deliberately shaped like the form provider collector
 * (`PUM_Integrations::$integrations` plus `get_enabled_form_integrations()`):
 * a keyed map, one registration filter, and availability decided by asking each
 * provider rather than by inspecting it from outside.
 *
 * Availability is resolved lazily on every lookup. Provider probes are cheap,
 * and not caching them avoids freezing a builder as unavailable while a
 * third-party `init` callback is still constructing its API.
 *
 * @since 1.25.0
 */
class BuilderProviders {

	/**
	 * Registered providers keyed by provider key.
	 *
	 * @var array<string,BuilderProvider>
	 */
	private $providers = [];

	/**
	 * Register a provider.
	 *
	 * Later registrations replace earlier ones for the same key, which lets an
	 * add-on swap a bundled provider without unhooking anything.
	 *
	 * @param BuilderProvider $provider Provider instance.
	 *
	 * @return void
	 */
	public function register( BuilderProvider $provider ) {
		$key = sanitize_key( $provider->key() );

		if ( '' === $key ) {
			return;
		}

		$this->providers[ $key ] = $provider;
	}

	/**
	 * Get every registered provider, available or not.
	 *
	 * @return array<string,BuilderProvider>
	 */
	public function all() {
		return $this->providers;
	}

	/**
	 * Get the providers whose builder is active and usable.
	 *
	 * @return array<string,BuilderProvider>
	 */
	public function available() {
		$available = [];

		foreach ( $this->providers as $key => $provider ) {
			if ( $provider->is_available() ) {
				$available[ $key ] = $provider;
			}
		}

		return $available;
	}

	/**
	 * Get one available provider by key.
	 *
	 * @param string $key Provider key.
	 *
	 * @return BuilderProvider|null
	 */
	public function get( $key ) {
		$available = $this->available();
		$key       = sanitize_key( $key );

		return isset( $available[ $key ] ) ? $available[ $key ] : null;
	}

	/**
	 * Get available providers implementing a given capability interface.
	 *
	 * @param string $capability Fully qualified capability interface name.
	 *
	 * @return array<string,BuilderProvider>
	 */
	public function supporting( $capability ) {
		$matches = [];

		foreach ( $this->available() as $key => $provider ) {
			if ( $provider instanceof $capability ) {
				$matches[ $key ] = $provider;
			}
		}

		return $matches;
	}
}
