<?php
/**
 * Utility functions.
 *
 * @since 1.21.0
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker;

defined( 'ABSPATH' ) || exit;


/**
 * Change camelCase to snake_case.
 *
 * @param string $str String to convert.
 *
 * @return string Converted string.
 *
 * @since 1.21.0
 */
function camel_case_to_snake_case( $str ) {
	return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $str ) );
}

/**
 * Change snake_case to camelCase.
 *
 * @param string $str String to convert.
 *
 * @return string Converted string.
 *
 * @since 1.21.0
 */
function snake_case_to_camel_case( $str ) {
	return lcfirst( str_replace( '_', '', ucwords( $str, '_' ) ) );
}

/**
 * Get array values using dot.notation.
 *
 * @param string              $key Key to fetch.
 * @param array<string,mixed> $data Array to fetch from.
 * @param string|null         $key_case Case to use for key (snake_case|camelCase).
 *
 * @return mixed|null
 *
 * @since 1.21.0
 */
function fetch_key_from_array( $key, $data, $key_case = null ) {
	// If key is .notation, then we need to traverse the array.
	$dotted_keys = explode( '.', $key );

	foreach ( $dotted_keys as $key ) {
		if ( $key_case ) {
			switch ( $key_case ) {
				case 'snake_case':
					// Check if key is camelCase & convert to snake_case.
					$key = camel_case_to_snake_case( $key );
					break;
				case 'camelCase':
					// Check if key is snake_case & convert to camelCase.
					$key = snake_case_to_camel_case( $key );
					break;
			}
		}

		if ( ! isset( $data[ $key ] ) ) {
			return null;
		}

		$data = $data[ $key ];
	}

	return $data ? $data : null;
}

/**
 * Generate a short unique ID.
 * This generates a unique ID that is URL-safe by combining timestamp and random elements.
 *
 * @param string $prefix Optional prefix for the UUID.
 * @param int    $random_length Length of random suffix (default 4).
 * @return string
 */
function generate_uuid( $prefix = '', $random_length = 4 ) {
	// Get microtime as base36 - this gives us a 6-7 character time component
	$time = base_convert( str_replace( '.', '', microtime( true ) ), 10, 36 );

	// Add random suffix
	$chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$random = '';

	for ( $i = 0; $i < $random_length; $i++ ) {
		$random .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
	}

	return $prefix . $time . $random;
}

/**
 * Safely redirect to URL, allowing external domains when appropriate.
 *
 * This function provides a wrapper around wp_safe_redirect() that allows
 * external redirects with proper security controls via filters.
 *
 * @param string $url    URL to redirect to.
 * @param int    $status HTTP status code (default 302).
 *
 * @return void
 */
function safe_redirect( $url, $status = 302 ) {
	/**
	 * Filter to determine if external redirects should be allowed.
	 *
	 * @param bool   $allow_external Whether to allow external redirects.
	 * @param string $url           The URL being redirected to.
	 */
	$allow_external = apply_filters( 'popup_maker/allow_external_redirect', true, $url );

	if ( $allow_external ) {
		// Parse the URL to check if it's external
		$parsed_url = wp_parse_url( $url );
		$site_url   = wp_parse_url( home_url() );

		// If it's an external URL, temporarily add the host to allowed hosts
		if ( isset( $parsed_url['host'] ) &&
			isset( $site_url['host'] ) &&
			$parsed_url['host'] !== $site_url['host']
		) {
			add_filter(
				'allowed_redirect_hosts',
				function ( $hosts ) use ( $parsed_url ) {
					if ( ! in_array( $parsed_url['host'], $hosts, true ) ) {
						$hosts[] = $parsed_url['host'];
					}
					return $hosts;
				},
				20
			);
		}
	}

	wp_safe_redirect( sanitize_url( $url ), $status );
	exit;
}

/**
 * Render a progress bar.
 *
 * @param float|int                                    $percentage The percentage to display.
 * @param array{size:string,title:string,class:string} $args       The arguments for the progress bar.
 * @return void
 */
function progress_bar( $percentage, $args = [] ) {

	$args = wp_parse_args(
		$args,
		[
			'size'            => null,
			'title'           => '',
			'class'           => '',
			'show_percentage' => true,
		]
	);

	$classes = [
		'pum-progress-bar',
	];

	if ( $args['size'] ) {
		$classes[] = 'pum-progress-bar--' . esc_attr( $args['size'] );
	}

	if ( $args['class'] ) {
		$classes[] = esc_attr( $args['class'] );
	}

	echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" title="' . esc_html( $args['title'] ) . '">';
	echo '<div class="pum-progress-bar__inner">';
	echo '<div class="pum-progress-fill" style="width: ' . esc_attr( min( $percentage, 100 ) ) . '%;"></div>';
	echo '</div>';

	if ( $args['show_percentage'] ) {
		echo '<strong>' . esc_html( round( $percentage, 1 ) ) . '%</strong>';
	}

	echo '</div>';
}

/**
 * Get a filterable query parameter name.
 *
 * Used for URL tracking parameters like 'pid' which can conflict
 * with other plugins. Site admins can filter to change the param name.
 *
 * @since 1.22.0
 *
 * @param string $key Parameter key (e.g., 'popup_id').
 *
 * @return string The parameter name.
 */
function get_param_name( $key ) {
	static $cache = [];

	if ( ! isset( $cache[ $key ] ) ) {
		$defaults      = [ 'popup_id' => 'pid' ];
		$cache[ $key ] = sanitize_key(
			apply_filters(
				"popup_maker/param_name/{$key}",
				$defaults[ $key ] ?? $key
			)
		);
	}

	return $cache[ $key ];
}

/**
 * Get all filterable query parameter names.
 *
 * @since 1.22.0
 *
 * @return array<string,string> Parameter names keyed by their identifier.
 */
function get_param_names() {
	return [
		'popup_id' => get_param_name( 'popup_id' ),
		'cta'      => get_param_name( 'cta' ),
		'notrack'  => get_param_name( 'notrack' ),
	];
}

/**
 * Get a query parameter value with type safety and filtering support.
 *
 * Uses the filterable parameter name system via get_param_name().
 * Returns fallback if parameter is not set OR is an empty string.
 * Note: Allows "0" as a valid value (unlike empty() check).
 *
 * @since 1.22.0
 *
 * @param string $key      Parameter key (e.g., 'popup_id', 'cta').
 * @param mixed  $fallback Fallback value if parameter not set or empty.
 * @param string $type     Type to cast value to: 'string', 'int', 'bool', 'key', 'email', 'url'.
 *                         Defaults to 'string'.
 *
 * @return mixed The sanitized parameter value, or fallback if not set/empty.
 */
function get_param_value( $key, $fallback = null, $type = 'string' ) {
	$param_name = get_param_name( $key );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Parameter reading, not state-changing operation.
	if ( ! isset( $_GET[ $param_name ] ) || '' === $_GET[ $param_name ] ) {
		return $fallback;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization handled by sanitize_param_by_type().
	$value = wp_unslash( $_GET[ $param_name ] );

	return sanitize_param_by_type( $value, $type );
}

/**
 * Get a POST parameter value with type safety.
 *
 * Separate function for POST to enforce deliberate intent.
 * Note: POST parameters do not use the filterable name system.
 *
 * @since 1.22.0
 *
 * @param string $key      Parameter key (e.g., 'action', 'nonce').
 * @param mixed  $fallback Fallback value if parameter not set or empty.
 * @param string $type     Type to cast value to: 'string', 'int', 'bool', 'key', 'email', 'url'.
 *                         Defaults to 'string'.
 *
 * @return mixed The sanitized parameter value, or fallback if not set/empty.
 */
function get_post_param_value( $key, $fallback = null, $type = 'string' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Parameter reading, not state-changing operation.
	if ( ! isset( $_POST[ $key ] ) || '' === $_POST[ $key ] ) {
		return $fallback;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization handled by sanitize_param_by_type().
	$value = wp_unslash( $_POST[ $key ] );

	return sanitize_param_by_type( $value, $type );
}

/**
 * Sanitize a parameter value based on type specification.
 *
 * Reusable helper for type-safe sanitization of request data.
 *
 * @since 1.22.0
 *
 * @param mixed  $value Raw parameter value.
 * @param string $type  Type to sanitize for: 'string', 'int', 'bool', 'key', 'email', 'url'.
 *                      Note: 'bool' returns false for invalid boolean values.
 *
 * @return mixed Sanitized value. Returns false for 'bool' type with invalid input.
 */
function sanitize_param_by_type( $value, $type ) {
	switch ( $type ) {
		case 'int':
			$int_value = absint( $value );

			// Log suspicious int conversions in debug mode.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && 0 === $int_value && '0' !== $value && 0 !== $value ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Popup Maker: Non-numeric value "%s" converted to int resulted in 0', $value ) );
			}

			return $int_value;

		case 'bool':
			$result = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

			// Log invalid boolean values in debug mode.
			if ( null === $result && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Popup Maker: Invalid boolean value "%s", returning false', $value ) );
			}

			return $result ?? false;

		case 'key':
			return sanitize_key( $value );

		case 'email':
			$sanitized = sanitize_email( $value );

			// Log email validation failures in debug mode.
			if ( '' === $sanitized && '' !== $value && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Popup Maker: Email validation failed for value "%s"', $value ) );
			}

			return $sanitized;

		case 'url':
			$sanitized = esc_url_raw( $value );

			// Log URL validation failures in debug mode.
			if ( '' === $sanitized && '' !== $value && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Popup Maker: URL validation failed for value "%s"', $value ) );
			}

			return $sanitized;

		case 'string':
		default:
			return sanitize_text_field( $value );
	}
}
