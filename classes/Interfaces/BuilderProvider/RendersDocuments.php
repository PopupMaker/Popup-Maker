<?php
/**
 * Builder document rendering capability.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Interfaces\BuilderProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Implemented by providers that can render a popup's builder document.
 *
 * The contract is stable ("give me HTML for this popup") even though builder
 * APIs differ in how they return rendered documents.
 *
 * @since 1.25.0
 */
interface RendersDocuments {

	/**
	 * Whether the popup's content is built with this builder.
	 *
	 * Must be cheap and side-effect free; the coordinator calls it for every
	 * loaded popup on every request.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function is_builder_document( $popup_id );

	/**
	 * Render the popup's builder document.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return string|null Rendered markup, or null to fall back to post content.
	 */
	public function render_document( $popup_id );
}
