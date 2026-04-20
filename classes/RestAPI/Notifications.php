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
						'code'    => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'action'  => [
							'required'          => false,
							'type'              => 'string',
							'default'           => 'dismiss',
							'sanitize_callback' => 'sanitize_key',
						],
						'expires' => [
							'required'          => false,
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
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

		$alerts = PUM_Utils_Alerts::get_alerts();

		$items = [];

		foreach ( $alerts as $alert ) {
			$type    = isset( $alert['type'] ) ? (string) $alert['type'] : 'info';
			$is_bad  = in_array( $type, [ 'error', 'warning' ], true );
			$is_glob = ! empty( $alert['global'] );

			// Skip inline-rendered blocking alerts.
			if ( $is_bad || $is_glob ) {
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
		$code    = (string) $request->get_param( 'code' );
		$action  = (string) $request->get_param( 'action' );
		$expires = (string) $request->get_param( 'expires' );

		if ( '' === $code ) {
			return new WP_Error( 'pum_missing_code', __( 'Missing alert code.', 'popup-maker' ), [ 'status' => 400 ] );
		}

		$ok = PUM_Utils_Alerts::action_handler( $code, $action, $expires );

		if ( ! $ok ) {
			return new WP_Error( 'pum_dismiss_failed', __( 'Could not record dismissal.', 'popup-maker' ), [ 'status' => 500 ] );
		}

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Normalize an alert into the panel's JSON shape.
	 *
	 * @param array $alert Alert array.
	 * @return array
	 */
	protected function prepare_alert_for_response( array $alert ) {
		$category = isset( $alert['category'] ) ? (string) $alert['category'] : 'announcement';

		$allowed_tags = PUM_Utils_Alerts::allowed_tags();

		$title_raw   = isset( $alert['title'] ) ? (string) $alert['title'] : '';
		$message_raw = isset( $alert['message'] ) ? (string) $alert['message'] : '';
		$html_raw    = isset( $alert['html'] ) ? (string) $alert['html'] : '';

		// Sanitize HTML server-side via wp_kses before it leaves the API so
		// the client never has to re-trust content from the pum_alert_list filter.
		$title   = $title_raw ? wp_kses( $title_raw, $allowed_tags ) : '';
		$message = $message_raw ? wp_kses( $message_raw, $allowed_tags ) : '';
		$html    = $html_raw ? wp_kses( $html_raw, $allowed_tags ) : '';

		$actions = [];
		if ( isset( $alert['actions'] ) && is_array( $alert['actions'] ) ) {
			foreach ( $alert['actions'] as $action ) {
				if ( ! is_array( $action ) ) {
					continue;
				}
				$actions[] = [
					'text'    => isset( $action['text'] ) ? (string) $action['text'] : '',
					'type'    => isset( $action['type'] ) ? (string) $action['type'] : 'action',
					'action'  => isset( $action['action'] ) ? (string) $action['action'] : '',
					'href'    => isset( $action['href'] ) ? esc_url_raw( (string) $action['href'] ) : '',
					'primary' => ! empty( $action['primary'] ),
					'expires' => isset( $action['expires'] ) ? sanitize_text_field( (string) $action['expires'] ) : '',
				];
			}
		}

		return [
			'code'        => (string) ( $alert['code'] ?? '' ),
			'type'        => (string) ( $alert['type'] ?? 'info' ),
			'category'    => $category,
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
}
