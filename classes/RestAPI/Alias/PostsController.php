<?php
/**
 * Standard namespace alias controller for Popup Maker post collections.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 * @package PopupMaker\RestAPI\Alias
 */

namespace PopupMaker\RestAPI\Alias;

defined( 'ABSPATH' ) || exit;

use WP_REST_Posts_Controller;

/**
 * Exposes a Popup Maker post type through the standard `wp/v2` namespace.
 *
 * Popup Maker registers its post types under the versioned `popup-maker/v2`
 * namespace. Generic block editors and third-party tooling discover content
 * through `wp/v2`, so this controller re-registers the same Core routes under
 * the standard namespace without redefining any route arguments itself.
 *
 * All request handling, schema, and permission behavior is inherited from
 * WP_REST_Posts_Controller so the two namespaces stay in lockstep.
 *
 * @since 1.26.0
 */
class PostsController extends WP_REST_Posts_Controller {

	/**
	 * Build an aliased posts controller.
	 *
	 * @param string $post_type Post type key.
	 * @param string $rest_namespace REST namespace to expose the routes under.
	 * @param string $rest_base REST collection base.
	 */
	public function __construct( $post_type, $rest_namespace, $rest_base ) {
		parent::__construct( $post_type );

		$this->namespace = $rest_namespace;
		$this->rest_base = $rest_base;
	}
}
