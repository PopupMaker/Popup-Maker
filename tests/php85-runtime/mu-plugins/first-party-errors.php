<?php
/**
 * Promote Popup Maker runtime errors to failures during PHP 8.5 smoke tests.
 *
 * @package Popup_Maker
 */

error_reporting( E_ALL );

// Let external tooling and WordPress report their own warnings normally.
set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		if ( 0 === ( $severity & error_reporting() ) ) {
			return false;
		}

		$normalized_file = wp_normalize_path( $file );
		$plugin_dir      = class_exists( 'Popup_Maker', false )
			? wp_normalize_path( Popup_Maker::$DIR )
			: wp_normalize_path( WP_PLUGIN_DIR . '/popup-maker/' );

		if ( 0 === strpos( $normalized_file, trailingslashit( $plugin_dir ) ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the raw PHP diagnostic.
			throw new ErrorException( $message, 0, $severity, $file, $line );
		}

		return false;
	}
);
