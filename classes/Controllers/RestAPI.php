<?php
/**
 * REST API setup.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 * @package PopupMaker
 */

namespace PopupMaker\Controllers;

use PopupMaker\Plugin\Controller;

/**
 * REST controller.
 *
 * @since X.X.X
 */
class RestAPI extends Controller {

	/**
	 * Init controller.
	 *
	 * @return void
	 */
	public function init() {
		// Register custom REST API fields.
		add_action( 'init', [ $this, 'register_popup_rest_fields' ] );
		add_action( 'init', [ $this, 'register_cta_rest_fields' ] );

		// Authentication.
		add_filter( 'rest_pre_dispatch', [ $this, 'rest_pre_dispatch' ], 10, 3 );

		// Sanitize and validate filters.
		// add_filter( 'popup_maker/sanitize_popup_settings', [ $this, 'sanitize_popup_settings' ], 10, 2 );
		// add_filter( 'popup_maker/validate_popup_settings', [ $this, 'validate_popup_settings' ], 10, 2 );
		add_filter( 'popup_maker/sanitize_call_to_action_settings', [ $this, 'sanitize_call_to_action_settings' ], 10, 2 );
		add_filter( 'popup_maker/validate_call_to_action_settings', [ $this, 'validate_call_to_action_settings' ], 10, 2 );
	}

	protected function register_data_version_field( $post_type, $update_permission ) {
		register_rest_field( $post_type, 'data_version', [
			'get_callback'        => function ( $obj ) {
				return get_post_meta( $obj['id'], 'data_version', true );
			},
			'update_callback'     => function ( $value, $obj ) {
				// Update the field/meta value.
				update_post_meta( $obj->ID, 'data_version', $value );
			},
			'permission_callback' => function () use ( $update_permission ) {
				return current_user_can( $update_permission );
			},
		] );
	}

	/**
	 * Register common REST fields for a given post type
	 *
	 * @return void
	 */
	public function register_popup_rest_fields() {
		$post_type       = $this->container->get_controller( 'PostTypes' )->get_type_key( 'popup' );
		$edit_permission = $this->container->get_permission( 'edit_popups' );

		register_rest_field( $post_type, 'settings', [
			'get_callback'        => function ( $obj, $field, $request ) {
				$popup = pum_get_popup( $obj['id'] );

				// If edit context, return the current settings.
				if ( 'edit' === $request['context'] ) {
					$settings = $popup->get_settings();
				} else {
					// Otherwise, return the public settings.
					$settings = $popup->get_public_settings();
				}

				return $settings;
			},
			'update_callback'     => function ( $value, $obj ) {
				$popup = pum_get_popup( $obj->ID );
				$popup->update_settings( $value );
			},
			'schema'              => [
				'type'        => 'object',
				'arg_options' => [
					'sanitize_callback' => function ( $settings, $request ) {
						/**
						 * Sanitize the popup settings.
						 *
						 * @param array<string,mixed> $settings The settings to sanitize.
						 * @param int   $id       The popup ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return array<string,mixed> The sanitized settings.
						 */
						return apply_filters( 'popup_maker/sanitize_popup_settings', $settings, $request->get_param( 'id' ), $request );
					},
					'validate_callback' => function ( $settings, $request ) {
						/**
						 * Validate the popup settings.
						 *
						 * @param array<string,mixed> $settings The settings to validate.
						 * @param int   $id       The popup ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return bool|\WP_Error True if valid, WP_Error if not.
						 */
						return apply_filters( 'popup_maker/validate_popup_settings', $settings, $request->get_param( 'id' ), $request );
					},
				],
			],
			'permission_callback' => function () use ( $edit_permission ) {
				return current_user_can( $edit_permission );
			},
		] );

		register_rest_field( $post_type, 'priority', [
			'get_callback'        => function ( $obj ) {
				return (int) get_post_field( 'menu_order', $obj['id'], 'raw' );
			},
			'update_callback'     => function ( $value, $obj ) {
				wp_update_post( [
					'ID'         => $obj->ID,
					'menu_order' => $value,
				] );
			},
			'permission_callback' => function () use ( $edit_permission ) {
				return current_user_can( $edit_permission );
			},
			'schema'              => [
				'type'        => 'integer',
				'arg_options' => [
					'sanitize_callback' => function ( $priority ) {
						return absint( $priority );
					},
					'validate_callback' => function ( $priority ) {
						return is_int( $priority );
					},
				],
			],
		] );

		// Register data version field.
		$this->register_data_version_field( $post_type, $edit_permission );
	}

	/**
	 * Sanitize popup settings.
	 *
	 * @param array<string,mixed> $settings The settings to sanitize.
	 * @param int                 $id       The popup ID.
	 *
	 * @return array<string,mixed> The sanitized settings.
	 */
	public function sanitize_popup_settings( $settings, $id ) {
		return $settings;
	}

	/**
	 * Validate popup settings.
	 *
	 * @param array<string,mixed> $settings The settings to validate.
	 * @param int                 $id       The popup ID.
	 *
	 * @return bool|\WP_Error True if valid, WP_Error if not.
	 */
	public function validate_popup_settings( $settings, $id ) {
		// TODO Validate all known settings by type.
		return true;
	}

	/**
	 * Registers custom REST API fields for call to action post type.
	 *
	 * @return void
	 */
	public function register_cta_rest_fields() {
		$post_type       = $this->container->get_controller( 'PostTypes' )->get_type_key( 'pum_cta' );
		$edit_permission = $this->container->get_permission( 'edit_ctas' );

		$ctas = $this->container->get( 'ctas' );

		register_rest_field( $post_type, 'settings', [
			'get_callback'        => function ( $obj, $field, $request ) use ( $ctas ) {
				$cta = $ctas->get_by_id( $obj['id'] );

				if ( ! $cta ) {
					return [];
				}

				$settings = [];

				// If edit context, return the current settings.
				if ( 'edit' === $request['context'] ) {
					$settings = $cta->get_settings();
				} else {
					// Otherwise, return the public settings.
					$settings = $cta->get_public_settings();
				}

				return $settings;
			},
			'update_callback'     => function ( $value, $obj ) {
				update_post_meta( $obj->ID, 'cta_settings', $value );
			},
			'schema'              => [
				'type'        => 'object',
				'arg_options' => [
					'sanitize_callback' => function ( $settings, $request ) {
						/**
						 * Sanitize the call to action settings.
						 *
						 * @param array<string,mixed> $settings The settings to sanitize.
						 * @param int   $id       The call to action ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return array<string,mixed> The sanitized settings.
						 */
						return apply_filters( 'popup_maker/sanitize_call_to_action_settings', $settings, $request->get_param( 'id' ), $request );
					},
					'validate_callback' => function ( $settings, $request ) {
						/**
						 * Validate the popup settings.
						 *
						 * @param array<string,mixed> $settings The settings to validate.
						 * @param int   $id       The popup ID.
						 * @param \WP_REST_Request $request The request object.
						 *
						 * @return bool|\WP_Error True if valid, WP_Error if not.
						 */
						return apply_filters( 'popup_maker/validate_call_to_action_settings', $settings, $request->get_param( 'id' ), $request );
					},
				],
			],
			'permission_callback' => function () use ( $edit_permission ) {
				return current_user_can( $edit_permission );
			},
		] );

		// Register data version field.
		$this->register_data_version_field( $post_type, $edit_permission );
	}

	/**
	 * Sanitize call to action settings.
	 *
	 * @param array<string,mixed> $settings The settings to sanitize.
	 * @param int                 $id       The call to action ID.
	 *
	 * @return array<string,mixed> The sanitized settings.
	 */
	public function sanitize_call_to_action_settings( $settings, $id ) {
		return $settings;
	}

	/**
	 * Validate call to action settings.
	 *
	 * @param array<string,mixed> $settings The settings to validate.
	 * @param int                 $id       The call to action ID.
	 *
	 * @return bool|\WP_Error True if valid, WP_Error if not.
	 */
	public function validate_call_to_action_settings( $settings, $id ) {
		// TODO Validate all known settings by type.
		return true;
	}

	/**
	 * Prevent access to the popups endpoint.
	 *
	 * @param mixed                                 $result Response to replace the requested version with.
	 * @param \WP_REST_Server                       $server Server instance.
	 * @param \WP_REST_Request<array<string,mixed>> $request  Request used to generate the response.
	 * @return mixed
	 */
	public function rest_pre_dispatch( $result, $server, $request ) {
		// Get the route being requested.
		$route = $request->get_route();

		if ( false === strpos( $route, '/popup-maker/v2' ) ) {
			return $result;
		}

		$current_user_can = true;

		// Only proceed if the current user has permission.
		if ( false !== strpos( $route, '/popup-maker/v2/popups' ) ) {
			$current_user_can = current_user_can( $this->container->get_permission( 'edit_popups' ) );
		} elseif ( false !== strpos( $route, '/popup-maker/v2/ctas' ) ) {
			$current_user_can = current_user_can( $this->container->get_permission( 'edit_ctas' ) );
		}

		// Prevent discovery of the endpoints data from unauthorized users.
		if ( ! $current_user_can ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Access to this endpoint requires authorization.', 'popup-maker' ),
				[
					'status' => rest_authorization_required_code(),
				]
			);
		}

		// Return data to the client to parse.
		return $result;
	}
}
