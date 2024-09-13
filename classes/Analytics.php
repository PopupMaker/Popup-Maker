<?php
/**
 * Analytics class
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls the basic analytics methods for Popup Maker
 */
class PUM_Analytics {

	/**
	 * Initializes analytics endpoints and data
	 */
	public static function init() {
		if ( ! self::analytics_enabled() ) {
			return;
		}

		add_action( 'rest_api_init', [ __CLASS__, 'register_endpoints' ] );
		add_action( 'wp_ajax_pum_analytics', [ __CLASS__, 'ajax_request' ] );
		add_action( 'wp_ajax_nopriv_pum_analytics', [ __CLASS__, 'ajax_request' ] );
		add_filter( 'pum_vars', [ __CLASS__, 'pum_vars' ] );
	}

	/**
	 * Checks whether analytics is enabled.
	 *
	 * @return bool
	 */
	public static function analytics_enabled() {
		$disabled = pum_get_option( 'disable_analytics' ) || popmake_get_option( 'disable_popup_open_tracking' );

		return (bool) apply_filters( 'pum_analytics_enabled', ! $disabled );
	}

	/**
	 * Get a list of key pairs for each event type.
	 * Internally used only for meta keys.
	 *
	 * Example returns [[open,opened],[conversion,conversion]].
	 *
	 * Usage examples:
	 * - popup_open_count, popup_last_opened
	 * - popup_conversion_count, popup_last_conversion
	 *
	 * @param string $event Event key.
	 *
	 * @return mixed
	 */
	public static function event_keys( $event ) {
		$keys = [ $event, rtrim( $event, 'e' ) . 'ed' ];

		if ( 'conversion' === $event ) {
			$keys[1] = 'conversion';
		}

		return apply_filters( 'pum_analytics_event_keys', $keys, $event );
	}

	/**
	 * Returns an array of valid event types.
	 *
	 * @return string[]
	 */
	public static function valid_events() {
		return apply_filters( 'pum_analytics_valid_events', [ 'open', 'conversion' ] );
	}

	/**
	 * Track an event.
	 *
	 * This is called by various methods including the ajax & rest api requests.
	 *
	 * Can be used externally such as after purchase tracking.
	 *
	 * @param array $args
	 */
	public static function track( $args = [] ) {
		if ( empty( $args['pid'] ) || $args['pid'] <= 0 ) {
			return;
		}

		// $uuid = isset( $_COOKIE['__pum'] ) ? sanitize_text_field( $_COOKIE['__pum'] ) : false;
		// $session = $uuid && isset( $_COOKIE[ $uuid ] ) ? PUM_Utils_Array::safe_json_decode( $_COOKIE[ $uuid ] ) : false;

		$event = sanitize_text_field( $args['event'] );

		$popup = pum_get_popup( $args['pid'] );

		if ( ! pum_is_popup( $popup ) || ! in_array( $event, self::valid_events(), true ) ) {
			return;
		}

		$popup->increase_event_count( $event );

		if ( has_action( 'pum_analytics_' . $event ) ) {
			do_action( 'pum_analytics_' . $event, $popup->ID, $args );
		}

		do_action( 'pum_analytics_event', $args );
	}

	/**
	 * Process ajax requests.
	 *
	 * Only used when WP-JSON Restful API is not available.
	 */
	public static function ajax_request() {

		$args = wp_parse_args(
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$_REQUEST,
			[
				'event'  => null,
				'pid'    => null,
				'method' => null,
			]
		);

		self::track( $args );

		switch ( $args['method'] ) {
			case 'image':
				self::serve_pixel();
				break;

			case 'json':
				self::serve_json();
				break;

			default:
				self::serve_no_content();
				break;
		}
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|mixed
	 */
	public static function analytics_endpoint( WP_REST_Request $request ) {
		$args = $request->get_params();

		if ( ! $args || empty( $args['pid'] ) ) {
			return new WP_Error( 'missing_params', __( 'Missing Parameters.', 'default' ), [ 'status' => 404 ] );
		}

		self::track( $args );

		self::serve_no_content();

		return true;
	}

	/**
	 * @param $param
	 *
	 * @return bool
	 */
	public static function endpoint_absint( $param ) {
		return is_numeric( $param );
	}

	/**
	 * Registers the analytics endpoints
	 */
	public static function register_endpoints() {
		register_rest_route(
			self::get_analytics_namespace(),
			self::get_analytics_route(),
			apply_filters(
				'pum_analytics_rest_route_args',
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'analytics_endpoint' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'event' => [
							'required'    => true,
							'description' => __( 'Event Type', 'popup-maker' ),
							'type'        => 'string',
						],
						'pid'   => [
							'required'            => true,
							'description'         => __( 'Popup ID', 'popup-maker' ),
							'type'                => 'integer',
							'validation_callback' => [ __CLASS__, 'endpoint_absint' ],
							'sanitize_callback'   => 'absint',
						],
					],
				]
			)
		);
	}

	/**
	 * Adds our analytics endpoint to pum_vars
	 *
	 * @param array $vars The current pum_vars.
	 * @return array The updates pum_vars
	 */
	public static function pum_vars( $vars = [] ) {
		$vars['analytics_route'] = self::get_analytics_route();
		if ( function_exists( 'rest_url' ) ) {
			$vars['analytics_api'] = esc_url_raw( rest_url( self::get_analytics_namespace() ) );
		} else {
			$vars['analytics_api'] = false;
		}
		return $vars;
	}

	/**
	 * Gets the analytics namespace
	 *
	 * If bypass adblockers is enabled, will return random or custom string. If not, returns 'pum/v1'.
	 *
	 * @return string The analytics namespce
	 * @since 1.13.0
	 */
	public static function get_analytics_namespace() {
		$version   = 1;
		$namespace = self::customize_endpoint_value( 'pum' );
		return "$namespace/v$version";
	}

	/**
	 * Gets the analytics route
	 *
	 * If bypass adblockers is enabled, will return random or custom string. If not, returns 'analytics'.
	 *
	 * @return string The analytics route
	 * @since 1.13.0
	 */
	public static function get_analytics_route() {
		$route = 'analytics';
		return self::customize_endpoint_value( $route );
	}

	/**
	 * Customizes the endpoint value given to it
	 *
	 * If bypass adblockers is enabled, will return random or custom string. If not, returns the value given to it.
	 *
	 * @param string $value The value to, potentially, customize.
	 * @return string
	 * @since 1.13.0
	 */
	public static function customize_endpoint_value( $value = '' ) {
		$bypass_adblockers = pum_get_option( 'bypass_adblockers', false );
		if ( true === $bypass_adblockers || 1 === intval( $bypass_adblockers ) ) {
			switch ( pum_get_option( 'adblock_bypass_url_method', 'random' ) ) {
				case 'custom':
					$value = preg_replace( '/[^a-z0-9]+/', '-', pum_get_option( 'adblock_bypass_custom_filename', $value ) );
					break;
				case 'random':
				default:
					$site_url = get_site_url();
					$value    = md5( $site_url . $value );
					break;
			}
		}
		return $value;
	}

	/**
	 * Creates and returns a 1x1 tracking gif to the browser.
	 */
	public static function serve_pixel() {
		$gif = self::get_file( Popup_Maker::$DIR . 'assets/images/beacon.gif' );
		header( 'Content-Type: image/gif' );
		header( 'Content-Length: ' . strlen( $gif ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $gif;
		exit;
	}

	/**
	 * @param $path
	 *
	 * @return bool|string
	 */
	public static function get_file( $path ) {

		if ( function_exists( 'realpath' ) ) {
			$path = realpath( $path );
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $path || ! @is_file( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return @file_get_contents( $path );
	}

	/**
	 * Returns a 204 no content header.
	 */
	public static function serve_no_content() {
		header( 'HTTP/1.0 204 No Content' );
		header( 'Content-Type: image/gif' );
		header( 'Content-Length: 0' );
		exit;
	}

	/**
	 * Serves a proper json response.
	 *
	 * @param mixed $data
	 */
	public static function serve_json( $data = 0 ) {
		header( 'Content-Type: application/json' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo PUM_Utils_Array::safe_json_encode( $data );
		exit;
	}
}
