<?php
/**
 * Direct popup editing capability.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Interfaces\BuilderProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Implemented by providers whose builder can edit popup posts directly.
 *
 * Popups are registered non-publicly-queryable on purpose. A builder that
 * edits them through a front-end request needs that query restored, but only
 * for the one popup being edited and only for a user who may edit it.
 *
 * @since 1.25.0
 */
interface EditsPopups {

	/**
	 * Whether this request is the builder's editor or canvas for a popup.
	 *
	 * Implementations return the popup ID the builder is asking for, without
	 * performing authorization. The coordinator validates post type,
	 * capability, and login state, so a provider cannot accidentally widen
	 * access.
	 *
	 * @return int Popup ID the builder requested, or 0 when not such a request.
	 */
	public function get_requested_popup_id();

	/**
	 * Whether the request targets the isolated editing canvas.
	 *
	 * Builders separate the editor shell (chrome, panels) from the canvas
	 * iframe that renders the document. Only the canvas gets Popup Maker's
	 * isolated template; the shell must be left alone.
	 *
	 * @return bool
	 */
	public function is_canvas_request();
}
