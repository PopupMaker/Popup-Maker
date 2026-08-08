<?php
/**
 * Builder preview URL capability.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Interfaces\BuilderProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Implemented by providers that redirect their builder's preview button.
 *
 * Preview URL filters do not call the builder runtime, so the coordinator
 * registers them once even when the builder has not finished bootstrapping.
 * This is independent from the provider's available runtime operations.
 *
 * @since 1.25.0
 */
interface ProvidesPreviewUrl {

	/**
	 * Register the builder-specific preview URL filter.
	 *
	 * @return void
	 */
	public function register_preview_url();
}
