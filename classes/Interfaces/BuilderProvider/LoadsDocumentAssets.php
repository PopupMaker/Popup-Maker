<?php
/**
 * Secondary document asset capability.
 *
 * @package   PopupMaker
 * @copyright Copyright (c) 2026, Code Atlantic LLC
 */

namespace PopupMaker\Interfaces\BuilderProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Implemented by providers whose builder needs help loading assets for popups.
 *
 * Popups are *secondary* documents: they are not the main query post, and they
 * may be discovered before the main loop (preloaded) or after it (a click
 * trigger found while filtering `the_content`). Builders only load assets for
 * the main document on their own.
 *
 * Finalization stays with each provider because builder asset APIs differ.
 * The coordinator owns *when* finalization happens and guarantees it succeeds
 * at most once per batch. Providers own *what* finalization does.
 *
 * @since 1.25.0
 */
interface LoadsDocumentAssets {

	/**
	 * Whether the popup's assets belong to this builder.
	 *
	 * Kept on the asset capability rather than inferred from
	 * `RendersDocuments`: a builder may render through `the_content` while still
	 * needing help loading assets for the secondary popup document.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return bool
	 */
	public function is_builder_document( $popup_id );

	/**
	 * Collect assets for one popup document.
	 *
	 * Called immediately after the document renders, while the builder's own
	 * render-time state is still valid. Implementations should accumulate work
	 * rather than emit it, so many popups collapse into one finalization.
	 *
	 * @param int $popup_id Popup ID.
	 *
	 * @return void
	 */
	public function collect_document_assets( $popup_id );

	/**
	 * Finalize every asset collected since the last finalization.
	 *
	 * Called at batch boundaries by the coordinator, never by the provider.
	 * Implementations must be safe to call when nothing was collected, and
	 * must not repeat one-time global bootstrap work.
	 *
	 * @param bool $after_head Whether output has already passed `wp_head()`,
	 *                         in which case enqueueing is no longer effective
	 *                         and markup must be printed instead.
	 *
	 * @return bool Whether the collected assets were finalized. Returning
	 *              false leaves them pending for the next boundary.
	 */
	public function finalize_document_assets( $after_head );
}
