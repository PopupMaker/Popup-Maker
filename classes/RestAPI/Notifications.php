<?php
/**
 * REST API Notifications Controller.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2024, Code Atlantic LLC
 */

namespace PopupMaker\RestAPI;

use PUM_Utils_Alerts;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Notifications REST controller.
 *
 * Surfaces the existing `pum_alert_list` filter output as JSON for the
 * admin notifications panel, and handles user/global dismissals.
 *
 * @since 1.23.0
 */
class Notifications extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'popup-maker/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'notifications';

	/**
	 * Register the routes for the notifications endpoints.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/dismiss',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'dismiss_item' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'code'   => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'action' => [
							'required'          => false,
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_key',
						],
					],
				],
			]
		);
	}

	/**
	 * Permission check — uses the central Popup Maker permissions mapping
	 * so site admins can reassign required capabilities without editing
	 * each REST controller.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request = null ) {
		unset( $request );

		$capability = \PopupMaker\plugin()->get_permission( 'edit_popups' );

		return current_user_can( $capability );
	}

	/**
	 * GET /notifications — returns panel-eligible alerts.
	 *
	 * Excludes blocking alerts (error/warning/global) that are already shown
	 * inline at the top of admin pages to avoid duplicate surfacing.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		unset( $request );

		$items = [];

		foreach ( PUM_Utils_Alerts::get_alerts() as $alert ) {
			if ( ! is_array( $alert ) || ! self::is_panel_eligible( $alert ) ) {
				continue;
			}
			$items[] = $this->prepare_alert_for_response( $alert );
		}

		$response = rest_ensure_response( $items );
		$response->header( 'X-PM-Notifications-Count', (string) count( $items ) );

		return $response;
	}

	/**
	 * POST /notifications/dismiss — dismiss or act on an alert.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function dismiss_item( $request ) {
		$code   = sanitize_text_field( (string) $request->get_param( 'code' ) );
		$action = sanitize_key( (string) $request->get_param( 'action' ) );

		if ( '' === $code ) {
			return new WP_Error( 'pum_missing_code', __( 'Missing alert code.', 'popup-maker' ), [ 'status' => 400 ] );
		}

		/*
		 * Look up the alert we're about to act on and confirm the action
		 * is one the provider declared. Without this check a caller could
		 * pass any action string and fire arbitrary `pum_alert_dismissed`
		 * events.
		 */
		$alert = $this->find_alert_by_code( $code );
		if ( null === $alert ) {
			return new WP_Error( 'pum_unknown_notification', __( 'Unknown notification.', 'popup-maker' ), [ 'status' => 404 ] );
		}

		$expires = $this->resolve_action_expires( $alert, $action );
		if ( false === $expires ) {
			return new WP_Error( 'pum_invalid_notification_action', __( 'Invalid notification action.', 'popup-maker' ), [ 'status' => 400 ] );
		}

		$ok = PUM_Utils_Alerts::action_handler( $code, $action, $expires );

		if ( ! $ok ) {
			return new WP_Error( 'pum_dismiss_failed', __( 'Could not record dismissal.', 'popup-maker' ), [ 'status' => 500 ] );
		}

		/*
		 * Core's action_handler only fires `pum_alert_dismissed` for custom
		 * actions — empty ('' = corner X) and 'dismiss' paths short-circuit
		 * after writing user-meta. Providers that need a post-dismissal
		 * side-effect (e.g. WhatsNew clearing its release slot) would never
		 * get notified. Fire the hook here for those paths so providers see
		 * every dismissal consistently.
		 */
		if ( '' === $action || 'dismiss' === $action ) {
			do_action( 'pum_alert_dismissed', $code, $action );
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Find an alert in the current panel-eligible list by code.
	 *
	 * Skips blocking alerts (`type: error|warning` or `global: true`)
	 * because those render inline at the top of the admin pages instead
	 * of appearing in our panel — a REST dismiss POST should never act
	 * on an alert the user isn't seeing through this endpoint.
	 *
	 * @param string $code Alert code.
	 * @return array<string,mixed>|null
	 */
	protected function find_alert_by_code( $code ) {
		foreach ( PUM_Utils_Alerts::get_alerts() as $alert ) {
			if ( ! isset( $alert['code'] ) || $alert['code'] !== $code ) {
				continue;
			}
			if ( ! self::is_panel_eligible( $alert ) ) {
				continue;
			}
			return $alert;
		}

		return null;
	}

	/**
	 * Whether an alert belongs in the panel (i.e. isn't a blocking one
	 * rendered inline at the top of the admin pages).
	 *
	 * @param array<string,mixed> $alert Alert definition.
	 * @return bool
	 */
	protected static function is_panel_eligible( array $alert ) {
		return \PUM_Utils_Alerts::is_panel_eligible( $alert );
	}

	/**
	 * Determine whether the requested action is permitted on the alert and
	 * return the `expires` value the provider declared for that action.
	 *
	 * Semantics:
	 *   - action '' (corner X close): always permanent, never inherits a
	 *     declared `dismiss` action's expires. Only allowed when the alert
	 *     is flagged dismissible.
	 *   - action 'dismiss' or other: must match a declared action on the
	 *     alert. Inherits that declared action's `expires` (so "Not now"
	 *     with `expires: '30 days'` becomes a 30-day snooze).
	 *
	 * Returns `false` when the action isn't allowed.
	 *
	 * @param array<string,mixed> $alert  Alert definition.
	 * @param string              $action Requested action key.
	 * @return string|false
	 */
	protected function resolve_action_expires( array $alert, $action ) {
		if ( '' === $action ) {
			return ! empty( $alert['dismissible'] ) ? '' : false;
		}

		foreach ( (array) ( $alert['actions'] ?? [] ) as $declared ) {
			if ( ! is_array( $declared ) ) {
				continue;
			}
			if ( ( $declared['action'] ?? '' ) === $action ) {
				return isset( $declared['expires'] ) ? (string) $declared['expires'] : '';
			}
		}

		// Legacy fallback: admin-bar notices (review_request, etc.) embed
		// dismiss links in their `html` field with data-reason values
		// rather than declaring `actions[]`. Accept the known-safe set
		// and forward the reason through to `pum_alert_dismissed` so
		// provider-side handlers for review_request still run. Unknown
		// actions still reject.
		$legacy_reasons = [ 'dismiss', 'maybe_later', 'already_did', 'am_now', 'never' ];
		if ( in_array( $action, $legacy_reasons, true ) ) {
			return '';
		}

		return false;
	}

	/**
	 * Normalize an alert into the panel's JSON shape.
	 *
	 * @param array $alert Alert array.
	 * @return array
	 */
	protected function prepare_alert_for_response( array $alert ) {
		$category = isset( $alert['category'] ) ? (string) $alert['category'] : 'announcement';

		$allowed_tags = self::panel_allowed_tags();

		$title_raw   = isset( $alert['title'] ) ? (string) $alert['title'] : '';
		$message_raw = isset( $alert['message'] ) ? (string) $alert['message'] : '';
		$html_raw    = isset( $alert['html'] ) ? (string) $alert['html'] : '';

		// Strip <script>, <style>, and HTML comments BEFORE kses. Without
		// this, kses removes the tags but leaves the body content as
		// visible text — legacy admin-bar notices (review_request, etc.)
		// embed nonce/UUID setup inside <script> blocks that would
		// otherwise leak into the panel as raw JavaScript text.
		$title_raw   = self::strip_executable_blocks( $title_raw );
		$message_raw = self::strip_executable_blocks( $message_raw );
		$html_raw    = self::strip_executable_blocks( $html_raw );

		/*
		 * Sanitize HTML server-side via wp_kses before it leaves the API
		 * so the client never has to re-trust content from the
		 * pum_alert_list filter.
		 */
		$title   = $title_raw ? wp_kses( $title_raw, $allowed_tags ) : '';
		$message = $message_raw ? wp_kses( $message_raw, $allowed_tags ) : '';
		$html    = $html_raw ? wp_kses( $html_raw, $allowed_tags ) : '';

		$actions = [];
		if ( isset( $alert['actions'] ) && is_array( $alert['actions'] ) ) {
			foreach ( $alert['actions'] as $action ) {
				if ( ! is_array( $action ) ) {
					continue;
				}
				$target    = isset( $action['target'] ) ? (string) $action['target'] : '';
				$actions[] = [
					'text'     => isset( $action['text'] ) ? sanitize_text_field( (string) $action['text'] ) : '',
					'type'     => isset( $action['type'] ) ? sanitize_key( (string) $action['type'] ) : 'action',
					'action'   => isset( $action['action'] ) ? sanitize_key( (string) $action['action'] ) : '',
					'href'     => isset( $action['href'] ) ? esc_url_raw( (string) $action['href'] ) : '',
					'primary'  => ! empty( $action['primary'] ),
					'external' => ! empty( $action['external'] ),
					'target'   => in_array( $target, [ '_blank', '_self' ], true ) ? $target : '',
					'expires'  => isset( $action['expires'] ) ? sanitize_text_field( (string) $action['expires'] ) : '',
				];
			}
		}

		return [
			'code'        => sanitize_text_field( (string) ( $alert['code'] ?? '' ) ),
			'type'        => sanitize_key( (string) ( $alert['type'] ?? 'info' ) ),
			'category'    => sanitize_key( $category ),
			'priority'    => (int) ( $alert['priority'] ?? 10 ),
			'title'       => $title,
			'message'     => $message,
			'html'        => $html,
			'subtitle'    => isset( $alert['subtitle'] ) ? sanitize_text_field( (string) $alert['subtitle'] ) : '',
			'icon'        => isset( $alert['icon'] ) ? sanitize_key( (string) $alert['icon'] ) : '',
			'dismissible' => ! empty( $alert['dismissible'] ),
			'global'      => ! empty( $alert['global'] ),
			'actions'     => $actions,
		];
	}

	/**
	 * Public schema for panel notification items.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$this->schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'pum_notification',
			'type'       => 'object',
			'properties' => [
				'code'        => [ 'type' => 'string' ],
				'type'        => [ 'type' => 'string' ],
				'category'    => [ 'type' => 'string' ],
				'priority'    => [ 'type' => 'integer' ],
				'title'       => [ 'type' => 'string' ],
				'message'     => [ 'type' => 'string' ],
				'html'        => [ 'type' => 'string' ],
				'subtitle'    => [ 'type' => 'string' ],
				'icon'        => [ 'type' => 'string' ],
				'dismissible' => [ 'type' => 'boolean' ],
				'global'      => [ 'type' => 'boolean' ],
				'actions'     => [ 'type' => 'array' ],
			],
		];

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Panel-specific HTML allowlist for `wp_kses`.
	 *
	 * Deliberately narrow — only simple formatting tags. The legacy
	 * `PUM_Utils_Alerts::allowed_tags()` list permits interactive and
	 * executable tags (script, form, input, button, select, option) that
	 * don't belong in a notifications panel body, even from our own
	 * providers. Filterable so Pro/Pro+ can add back any safe tags they
	 * legitimately need for richer provider content.
	 *
	 * Strip executable / non-renderable blocks (script, style, HTML
	 * comments) along with their content before kses. wp_kses removes
	 * disallowed tags but leaves the body text — this would leak inline
	 * JavaScript or CSS as visible text in the panel. Run this BEFORE
	 * kses on any field that may contain legacy admin-bar HTML.
	 *
	 * @param string $html Raw HTML from a provider.
	 * @return string Cleaned HTML safe to pass to wp_kses.
	 */
	protected static function strip_executable_blocks( $html ) {
		if ( '' === $html ) {
			return $html;
		}
		// Order matters: strip elements with body first, then comments.
		$html = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $html );
		$html = preg_replace( '#<style\b[^>]*>.*?</style>#is', '', $html );
		$html = preg_replace( '#<!--.*?-->#s', '', $html );
		return is_string( $html ) ? $html : '';
	}

	/**
	 * @return array<string,array<string,bool>>
	 */
	protected static function panel_allowed_tags() {
		$tags = [
			'a'      => [
				'href'        => true,
				'title'       => true,
				'target'      => true,
				'rel'         => true,
				// Allow class + data-* so legacy admin-bar notices can
				// declare their own dismiss/action handlers (e.g. the
				// review_request notice's `class="pum-dismiss"` +
				// `data-reason="..."` anchors).
				'class'       => true,
				'data-reason' => true,
				'data-action' => true,
			],
			'strong' => [],
			'b'      => [],
			'em'     => [],
			'i'      => [],
			'br'     => [],
			'p'      => [
				'class' => true,
			],
			'span'   => [
				'class' => true,
			],
			'ul'     => [
				'class' => true,
			],
			'ol'     => [
				'class' => true,
			],
			'li'     => [
				'class' => true,
			],
			'h1'     => [
				'class' => true,
			],
			'h2'     => [
				'class' => true,
			],
			'h3'     => [
				'class' => true,
			],
			'h4'     => [
				'class' => true,
			],
			'h5'     => [
				'class' => true,
			],
			'div'    => [
				'class' => true,
			],
			'img'    => [
				'src'    => true,
				'alt'    => true,
				'class'  => true,
				'width'  => true,
				'height' => true,
			],
			'code'   => [],
		];

		/**
		 * Filters the HTML allowlist used to sanitize notification
		 * panel content before it leaves the REST API.
		 *
		 * @since 1.23.0
		 *
		 * @param array<string,array<string,bool>> $tags Allowed tags map.
		 */
		return (array) apply_filters( 'popup_maker/notifications/allowed_tags', $tags );
	}
}
