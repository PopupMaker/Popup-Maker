<?php
/**
 * Object Search REST API Controller
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 * @package PopupMaker\RestAPI
 */

namespace PopupMaker\RestAPI;

defined( 'ABSPATH' ) || exit;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;
use WP_REST_Request;

/**
 * Object Search REST API Controller
 *
 * Provides REST API equivalent of the AJAX object search functionality.
 * Supports post types, taxonomies, users, and custom object types via filters.
 */
class ObjectSearch extends WP_REST_Controller {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'popup-maker/v2';

	/**
	 * REST base.
	 *
	 * @var string
	 */
	protected $rest_base = 'object-search';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'search_objects' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => $this->get_search_args(),
				],
			]
		);
	}

	/**
	 * Check if user has permission to search objects.
	 *
	 * @return bool|WP_Error True if the request has permission; WP_Error object otherwise.
	 */
	public function permissions_check() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to search objects.', 'popup-maker' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Search objects (posts, taxonomies, users, custom types).
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function search_objects( $request ) {
		$results = [
			'items'       => [],
			'total_count' => 0,
		];

		$object_type = $request->get_param( 'object_type' );
		$included    = $request->get_param( 'include' ) ?: [];
		$excluded    = $request->get_param( 'exclude' ) ?: [];

		if ( ! empty( $included ) ) {
			$excluded = array_merge( $included, $excluded );
		}

		/**
		 * Filter object search results for unknown or custom object types.
		 *
		 * Allows plugins to handle custom object types that aren't natively supported.
		 *
		 * @param array{items:array,total_count:int}|null  $results     Current search results with 'items' and 'total_count'.
		 * @param string $object_type The object type being searched.
		 * @param array  $request     The full request parameters.
		 *
		 * @return array{items:array,total_count:int}|null $results
		 */
		$pre_results = apply_filters( 'popup_maker/pre_object_search', null, $object_type, $request->get_params() );

		if ( null !== $pre_results ) {
			$results = $pre_results;
		} else {
			switch ( $object_type ) {
				case 'post_type':
					$post_type = $request->get_param( 'object_key' ) ?: 'post';
					$results   = $this->search_post_type( $post_type, $request, $included, $excluded );
					break;

				case 'taxonomy':
					$taxonomy = $request->get_param( 'object_key' ) ?: 'category';
					$results  = $this->search_taxonomy( $taxonomy, $request, $included, $excluded );
					break;

				case 'user':
					if ( ! current_user_can( 'list_users' ) ) {
						return new WP_Error(
							'rest_forbidden',
							__( 'You do not have permission to search users.', 'popup-maker' ),
							[ 'status' => 403 ]
						);
					}
					$user_role = $request->get_param( 'object_key' );
					$results   = $this->search_users( $user_role, $request, $included, $excluded );
					break;

				case 'custom_entity':
					// Custom entities are handled by the popup_maker/object_search filter below.
					// Leave results empty so filter handlers can populate them.
					break;
			}
		}

		/**
		 * Filter object search results for unknown or custom object types.
		 *
		 * Allows plugins to handle custom object types that aren't natively supported.
		 * This filter runs for both REST and AJAX requests - use popup_maker_object_search_unified() helper.
		 *
		 * @param array  $results     Current search results with 'items' and 'total_count'.
		 * @param string $object_type The object type being searched.
		 * @param array  $request     The full request parameters.
		 */
		$results = apply_filters( 'popup_maker/object_search', $results, $object_type, $request->get_params() );

		// Take out keys which were only used to deduplicate.
		$results['items'] = array_values( $results['items'] );

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Search post type objects.
	 *
	 * @param string          $post_type Post type to search.
	 * @param WP_REST_Request $request   Request object.
	 * @param array           $included   IDs to include.
	 * @param array           $excluded   IDs to exclude.
	 * @return array Search results.
	 */
	private function search_post_type( $post_type, $request, $included, $excluded ) {
		$results = [
			'items'       => [],
			'total_count' => 0,
		];

		if ( ! empty( $included ) ) {
			$included_query = \PUM_Helpers::post_type_selectlist_query(
				$post_type,
				[
					'post__in'       => $included,
					'posts_per_page' => -1,
				],
				true
			);

			foreach ( $included_query['items'] as $id => $name ) {
				$results['items'][] = [
					'id'   => $id,
					'text' => "$name (ID: $id)",
				];
			}

			$results['total_count'] += (int) $included_query['total_count'];
		}

		$query = \PUM_Helpers::post_type_selectlist_query(
			$post_type,
			[
				's'              => $request->get_param( 's' ),
				'paged'          => $request->get_param( 'paged' ),
				'post__not_in'   => $excluded,
				'posts_per_page' => 10,
			],
			true
		);

		foreach ( $query['items'] as $id => $name ) {
			$results['items'][] = [
				'id'   => $id,
				'text' => "$name (ID: $id)",
			];
		}

		$results['total_count'] += (int) $query['total_count'];

		return $results;
	}

	/**
	 * Search taxonomy objects.
	 *
	 * @param string          $taxonomy Taxonomy to search.
	 * @param WP_REST_Request $request  Request object.
	 * @param array           $included  IDs to include.
	 * @param array           $excluded  IDs to exclude.
	 * @return array Search results.
	 */
	private function search_taxonomy( $taxonomy, $request, $included, $excluded ) {
		$results = [
			'items'       => [],
			'total_count' => 0,
		];

		if ( ! empty( $included ) ) {
			$included_query = \PUM_Helpers::taxonomy_selectlist_query(
				$taxonomy,
				[
					'include' => $included,
					'number'  => 0,
				],
				true
			);

			foreach ( $included_query['items'] as $id => $name ) {
				$results['items'][] = [
					'id'   => $id,
					'text' => "$name (ID: $id)",
				];
			}

			$results['total_count'] += (int) $included_query['total_count'];
		}

		$query = \PUM_Helpers::taxonomy_selectlist_query(
			$taxonomy,
			[
				'search'  => $request->get_param( 's' ),
				'paged'   => $request->get_param( 'paged' ),
				'exclude' => $excluded,
				'number'  => 10,
			],
			true
		);

		foreach ( $query['items'] as $id => $name ) {
			$results['items'][] = [
				'id'   => $id,
				'text' => "$name (ID: $id)",
			];
		}

		$results['total_count'] += (int) $query['total_count'];

		return $results;
	}

	/**
	 * Search user objects.
	 *
	 * @param string|null     $user_role User role to filter by.
	 * @param WP_REST_Request $request   Request object.
	 * @param array           $included   IDs to include.
	 * @param array           $excluded   IDs to exclude.
	 * @return array Search results.
	 */
	private function search_users( $user_role, $request, $included, $excluded ) {
		$results = [
			'items'       => [],
			'total_count' => 0,
		];

		if ( ! empty( $included ) ) {
			$included_query = \PUM_Helpers::user_selectlist_query(
				[
					'role'    => $user_role,
					'include' => $included,
					'number'  => -1,
				],
				true
			);

			foreach ( $included_query['items'] as $id => $name ) {
				$results['items'][] = [
					'id'   => $id,
					'text' => "$name (ID: $id)",
				];
			}

			$results['total_count'] += (int) $included_query['total_count'];
		}

		$search = $request->get_param( 's' );
		$query  = \PUM_Helpers::user_selectlist_query(
			[
				'role'    => $user_role,
				'search'  => $search ? '*' . $search . '*' : null,
				'paged'   => $request->get_param( 'paged' ),
				'exclude' => $excluded,
				'number'  => 10,
			],
			true
		);

		foreach ( $query['items'] as $id => $name ) {
			$results['items'][] = [
				'id'   => $id,
				'text' => "$name (ID: $id)",
			];
		}

		$results['total_count'] += (int) $query['total_count'];

		return $results;
	}

	/**
	 * Get arguments for search endpoint.
	 *
	 * @return array Endpoint arguments.
	 */
	protected function get_search_args() {
		return [
			'object_type' => [
				'type'        => 'string',
				'description' => __( 'Type of object to search (post_type, taxonomy, user, custom_entity)', 'popup-maker' ),
				'required'    => true,
				'enum'        => [ 'post_type', 'taxonomy', 'user', 'custom_entity' ],
			],
			'object_key'  => [
				'type'        => 'string',
				'description' => __( 'Specific object key (post type name, taxonomy name, user role)', 'popup-maker' ),
				'required'    => false,
			],
			's'           => [
				'type'        => 'string',
				'description' => __( 'Search term', 'popup-maker' ),
				'required'    => false,
			],
			'include'     => [
				'type'        => 'array',
				'description' => __( 'IDs to include in results (integers for standard objects, strings for custom entities)', 'popup-maker' ),
				'required'    => false,
				'items'       => [
					'type' => [ 'integer', 'string' ],
				],
			],
			'exclude'     => [
				'type'        => 'array',
				'description' => __( 'IDs to exclude from results (integers for standard objects, strings for custom entities)', 'popup-maker' ),
				'required'    => false,
				'items'       => [
					'type' => [ 'integer', 'string' ],
				],
			],
			'paged'       => [
				'type'        => 'integer',
				'description' => __( 'Page number for pagination', 'popup-maker' ),
				'required'    => false,
				'minimum'     => 1,
			],
		];
	}
}
