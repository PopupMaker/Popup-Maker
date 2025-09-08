<?php
/**
 * Self-hosted bootstrap injection code for Popup Maker.
 *
 * This code should be injected into popup-maker.php by the EDD Watermark plugin
 * when processing self-hosted downloads from wppopupmaker.com.
 *
 * Injection location: After line 120 in popup-maker.php (after bootstrap.php require)
 *
 * @package PopupMaker
 * @since   1.21.0
 */

// This is the code to be injected into popup-maker.php by EDD Watermark.
// Do not include the <?php tag when injecting.

/**
 * Self-hosted update system for distributions outside wordpress.org.
 *
 * This compatibility layer enables automatic updates for self-hosted versions
 * and ensures telemetry tracking for better product development.
 *
 * @since 1.21.0
 */
if ( ! defined( 'PUM_SELF_HOSTED' ) ) {
	// Check if this is a self-hosted distribution.
	$self_hosted_updater = __DIR__ . '/includes/self-hosted-updater.php';

	if ( file_exists( $self_hosted_updater ) ) {
		// Define constant to prevent duplicate loading.
		define( 'PUM_SELF_HOSTED', true );

		if ( ! defined( 'POPUP_MAKER_FREE_LICENSE' ) ) {
			define( 'POPUP_MAKER_FREE_LICENSE', 'XXLICENSEXX' );
		}

		// Load the self-hosted updater system.
		require_once $self_hosted_updater;
	}
}
