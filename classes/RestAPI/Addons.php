<?php
/**
 * Static add-on catalog REST API.
 *
 * @package PopupMaker\RestAPI
 */

namespace PopupMaker\RestAPI;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

use function PopupMaker\plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the bundled catalog and installed extension lifecycle actions.
 */
class Addons extends WP_REST_Controller {

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
	protected $base = 'addons';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'view_permission' ],
				],
			]
		);

		foreach ( [ 'activate', 'deactivate' ] as $action ) {
			register_rest_route(
				$this->namespace,
				'/' . $this->base . '/' . $action,
				[
					[
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => [ $this, $action . '_item' ],
						'permission_callback' => [ $this, 'manage_permission' ],
						'args'                => $this->get_slug_args(),
					],
				]
			);
		}
	}

	/**
	 * Get the bundled catalog and current installed status.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request = null ) {
		unset( $request );

		return new WP_REST_Response( plugin( 'addon_catalog' )->get_public_items(), 200 );
	}

	/**
	 * Activate an installed catalog item.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function activate_item( $request ) {
		return $this->run_lifecycle_action( 'activate', $request );
	}

	/**
	 * Deactivate an installed catalog item.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function deactivate_item( $request ) {
		return $this->run_lifecycle_action( 'deactivate', $request );
	}

	/**
	 * Run an installed extension lifecycle action.
	 *
	 * @param string          $action  Lifecycle action.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	private function run_lifecycle_action( $action, $request ) {
		$slug      = sanitize_key( $request->get_param( 'slug' ) );
		$lifecycle = plugin( 'addon_lifecycle' );
		$result    = 'activate' === $action
			? $lifecycle->activate( $slug )
			: $lifecycle->deactivate( $slug );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				[
					'error'   => true,
					'message' => $this->sanitize_error_message( $result->get_error_message() ),
				],
				400
			);
		}

		return new WP_REST_Response(
			[
				'success'        => true,
				'pluginBasename' => $result,
			],
			200
		);
	}

	/**
	 * Preserve useful error formatting without trusting arbitrary plugin HTML.
	 *
	 * @param string $message Error message.
	 *
	 * @return string
	 */
	private function sanitize_error_message( $message ) {
		return wp_kses(
			$message,
			[
				'p'      => [],
				'strong' => [],
				'em'     => [],
				'br'     => [],
				'code'   => [],
				'a'      => [
					'href'   => true,
					'target' => true,
					'rel'    => true,
				],
			]
		);
	}

	/**
	 * Check catalog visibility.
	 *
	 * @return bool
	 */
	public function view_permission() {
		$capability = class_exists( '\\PUM_Admin_Pages' )
			? \PUM_Admin_Pages::get_submenu_capability( 'extensions' )
			: 'manage_options';

		return current_user_can( $capability );
	}

	/**
	 * Check installed extension lifecycle permissions.
	 *
	 * @return bool
	 */
	public function manage_permission() {
		return current_user_can( 'activate_plugins' );
	}

	/**
	 * Get request argument definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_slug_args() {
		return [
			'slug' => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => function ( $slug ) {
					return is_string( $slug ) && sanitize_key( $slug ) === $slug;
				},
			],
		];
	}
}
