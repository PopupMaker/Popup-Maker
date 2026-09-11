<?php
/**
 * Standard namespace alias controller for Popup Maker post revisions.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 * @package PopupMaker\RestAPI\Alias
 */

namespace PopupMaker\RestAPI\Alias;

defined( 'ABSPATH' ) || exit;

use WP_REST_Revisions_Controller;

/**
 * Exposes a Popup Maker post type's revisions through the standard namespace.
 *
 * Mirrors the revision routes Core already registers under `popup-maker/v2`
 * so editors that resolve revisions through `wp/v2` see identical behavior.
 * Route definitions, schema, and permission checks are inherited unchanged.
 *
 * @since 1.26.0
 */
class RevisionsController extends WP_REST_Revisions_Controller {

	/**
	 * Build an aliased revisions controller.
	 *
	 * @param string $parent_post_type Parent post type key.
	 * @param string $rest_namespace   REST namespace to expose the routes under.
	 */
	public function __construct( $parent_post_type, $rest_namespace ) {
		parent::__construct( $parent_post_type );

		$this->namespace = $rest_namespace;
	}
}
