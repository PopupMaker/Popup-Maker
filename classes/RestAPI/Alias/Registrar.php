<?php
/**
 * Registrar for standard namespace REST aliases.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 * @package PopupMaker\RestAPI\Alias
 */

namespace PopupMaker\RestAPI\Alias;

defined( 'ABSPATH' ) || exit;

use WP_Post_Type;

/**
 * Registers `wp/v2` aliases for Popup Maker post types.
 *
 * Popup Maker exposes its post types under the versioned `popup-maker/v2`
 * namespace, which keeps the plugin's own API stable but hides the content
 * from generic editors and tooling that only look at `wp/v2`. This registrar
 * re-registers Core's own controllers under the standard namespace.
 *
 * Two properties matter for correctness:
 *
 * - The alias set is derived from the same conditions Core uses, so a route
 *   exists in `wp/v2` only when the equivalent route exists in the plugin
 *   namespace. Revisions and autosaves follow `post_type_supports()` exactly
 *   as `WP_Post_Type::get_revisions_rest_controller()` and
 *   `get_autosave_rest_controller()` do.
 * - Registration is idempotent. `rest_api_init` can fire more than once in a
 *   single request (test suites and some integrations re-run it), and
 *   registering the same route twice appends duplicate handlers.
 *
 * @since 1.26.0
 */
class Registrar {

	/**
	 * Namespace the aliases are published under.
	 *
	 * @var string
	 */
	const ALIAS_NAMESPACE = 'wp/v2';

	/**
	 * Post types already aliased against the current REST server.
	 *
	 * Keyed by post type so a repeated `rest_api_init` is a no-op rather than
	 * a second set of handlers on the same routes. The map is discarded when
	 * the REST server is replaced, because a new server starts with no routes
	 * and must be populated again.
	 *
	 * @var array<string,bool>
	 */
	protected $registered = [];

	/**
	 * The REST server the current registration map belongs to.
	 *
	 * @var \WP_REST_Server|null
	 */
	protected $registered_server = null;

	/**
	 * Post types to alias, mapped to their standard collection base.
	 *
	 * @var array<string,string>
	 */
	protected $post_types = [];

	/**
	 * Build the registrar.
	 *
	 * @param array<string,string> $post_types Post type key => REST collection base.
	 */
	public function __construct( array $post_types ) {
		$this->post_types = $post_types;
	}

	/**
	 * Register aliases for every configured post type.
	 *
	 * @return void
	 */
	public function register() {
		$this->sync_with_current_server();

		foreach ( $this->post_types as $post_type => $rest_base ) {
			$this->register_post_type( (string) $post_type, (string) $rest_base );
		}
	}

	/**
	 * Drop the registration map when the REST server has been replaced.
	 *
	 * Route registration lives on the server object. Test suites and some
	 * integrations swap in a fresh server mid-request, which discards every
	 * previously registered route, so the guard must be rebuilt alongside it.
	 *
	 * @return void
	 */
	protected function sync_with_current_server() {
		global $wp_rest_server;

		if ( $this->registered_server !== $wp_rest_server ) {
			$this->registered        = [];
			$this->registered_server = $wp_rest_server;
		}
	}

	/**
	 * Register the alias routes for a single post type.
	 *
	 * @param string $post_type Post type key.
	 * @param string $rest_base REST collection base.
	 *
	 * @return void
	 */
	protected function register_post_type( $post_type, $rest_base ) {
		if ( isset( $this->registered[ $post_type ] ) ) {
			return;
		}

		$post_type_object = get_post_type_object( $post_type );

		if ( ! $this->should_alias( $post_type_object ) ) {
			return;
		}

		// Mark before registering so a controller failure cannot cause a retry
		// to append duplicate handlers for whatever did register successfully.
		$this->registered[ $post_type ] = true;

		$posts = new PostsController( $post_type, self::ALIAS_NAMESPACE, $rest_base );
		$posts->register_routes();

		$this->register_child_routes( $post_type_object, $rest_base );
	}

	/**
	 * Register revision and autosave aliases for a post type.
	 *
	 * Core resolves the child controllers against the post type's declared
	 * `rest_base`, so the property is pointed at the alias base for the
	 * duration of the call and restored afterwards. The restore runs from a
	 * `finally` block so a throwing controller cannot leave the global post
	 * type object mutated for the rest of the request.
	 *
	 * @param WP_Post_Type $post_type_object Post type object.
	 * @param string       $rest_base        REST collection base.
	 *
	 * @return void
	 */
	protected function register_child_routes( $post_type_object, $rest_base ) {
		$post_type      = $post_type_object->name;
		$original_base  = $post_type_object->rest_base;
		$restore_needed = $original_base !== $rest_base;

		if ( $restore_needed ) {
			$post_type_object->rest_base = $rest_base;
		}

		try {
			// Match Core's gate: revisions are only exposed when supported.
			if ( post_type_supports( $post_type, 'revisions' ) ) {
				$this->make_revisions_controller( $post_type )->register_routes();
			}

			// Match Core's gate: WordPress grants `autosave` implicitly with
			// `editor`, and only registers autosave routes when it is present.
			if ( post_type_supports( $post_type, 'autosave' ) ) {
				$this->make_autosaves_controller( $post_type )->register_routes();
			}
		} finally {
			if ( $restore_needed ) {
				$post_type_object->rest_base = $original_base;
			}
		}
	}

	/**
	 * Build the revisions alias controller.
	 *
	 * @param string $post_type Post type key.
	 *
	 * @return RevisionsController
	 */
	protected function make_revisions_controller( $post_type ) {
		return new RevisionsController( $post_type, self::ALIAS_NAMESPACE );
	}

	/**
	 * Build the autosaves alias controller.
	 *
	 * @param string $post_type Post type key.
	 *
	 * @return AutosavesController
	 */
	protected function make_autosaves_controller( $post_type ) {
		return new AutosavesController( $post_type, self::ALIAS_NAMESPACE );
	}

	/**
	 * Determine whether a post type should be aliased.
	 *
	 * @param WP_Post_Type|null $post_type_object Post type object.
	 *
	 * @return bool
	 */
	protected function should_alias( $post_type_object ) {
		if ( ! $post_type_object instanceof WP_Post_Type ) {
			return false;
		}

		if ( ! $post_type_object->show_in_rest ) {
			return false;
		}

		// Already published under the standard namespace by Core.
		if ( self::ALIAS_NAMESPACE === $post_type_object->rest_namespace ) {
			return false;
		}

		return true;
	}
}
