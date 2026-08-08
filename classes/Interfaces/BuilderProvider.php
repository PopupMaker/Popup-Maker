<?php
/**
 * Page builder provider contract.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Base contract every page builder provider implements.
 *
 * Keeps only the operations every bundled integration shares. The coordinator
 * checks for builder-specific operations before calling them, because no two
 * builders expose the same surface.
 *
 * Providers answer questions and perform single operations. They never own
 * request lifecycle, batching, or finalization — that belongs to
 * `PopupMaker\Controllers\Builders`.
 *
 * @since 1.25.0
 */
interface BuilderProvider {

	/**
	 * Stable provider key.
	 *
	 * Used for hook names, signed preview URLs, and coordinator lookups, so it
	 * must not change once shipped.
	 *
	 * @return string
	 */
	public function key();

	/**
	 * Whether the builder is active and exposes the APIs this provider needs.
	 *
	 * Called late (never at construction) because theme and plugin builders can
	 * load at different points. Implementations must verify every class or method
	 * they later call, so a builder API change degrades to a no-op instead of a
	 * fatal error.
	 *
	 * @return bool
	 */
	public function is_available();

	/**
	 * Register the provider's own builder-side hooks.
	 *
	 * Called once, only when {@see BuilderProvider::is_available()} is true.
	 * Providers must not register popup rendering or asset finalization hooks
	 * here; the coordinator owns those.
	 *
	 * @return void
	 */
	public function register_hooks();
}
