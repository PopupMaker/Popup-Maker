<?php
/**
 * Notification provider contract.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\Services\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Interface implemented by each notification provider registered with the
 * Notifications service. A provider is any component that contributes
 * entries to the `pum_alert_list` filter and optionally reacts to
 * dismissals, version changes, or other plugin events.
 *
 * Providers are booted once during plugin load via
 * `Notifications::init()` — each is given the opportunity to wire its own
 * hooks inside its `init()` method.
 *
 * @since 1.23.0
 */
interface Provider {

	/**
	 * Wire up WordPress hooks this provider cares about.
	 *
	 * @return void
	 */
	public function init();
}
