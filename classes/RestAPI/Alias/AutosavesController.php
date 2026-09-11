<?php
/**
 * Standard namespace alias controller for Popup Maker post autosaves.
 *
 * @copyright (c) 2024, Code Atlantic LLC.
 * @package PopupMaker\RestAPI\Alias
 */

namespace PopupMaker\RestAPI\Alias;

defined( 'ABSPATH' ) || exit;

use WP_REST_Autosaves_Controller;

/**
 * Exposes a Popup Maker post type's autosaves through the standard namespace.
 *
 * Mirrors the autosave routes Core registers under `popup-maker/v2`. Core only
 * creates an autosave controller when the post type supports autosaves, so the
 * registrar applies the same gate before constructing this class. Route
 * definitions, schema, and permission checks are inherited unchanged.
 *
 * @since 1.26.0
 */
class AutosavesController extends WP_REST_Autosaves_Controller {

	/**
	 * Build an aliased autosaves controller.
	 *
	 * @param string $parent_post_type Parent post type key.
	 * @param string $rest_namespace   REST namespace to expose the routes under.
	 */
	public function __construct( $parent_post_type, $rest_namespace ) {
		parent::__construct( $parent_post_type );

		$this->namespace = $rest_namespace;
	}
}
