/**
 * Popup Preview menu item for the Block Editor.
 *
 * Adds a "Preview Popup" entry to the editor's top-bar Preview ("View")
 * dropdown, alongside the native preview options. Builds the front-end
 * preview URL (popup_preview nonce + popup id) and opens it in a reusable
 * tab, where an admin_debug trigger force-opens the popup.
 *
 * The preview opens in a named tab so repeat clicks re-focus and refresh the
 * same tab rather than spawning new ones, and a successful save refreshes an
 * already-open preview tab so it reflects the latest settings.
 */

import { __ } from '@wordpress/i18n';
import { PluginPreviewMenuItem } from '@wordpress/editor';
import { seen } from '@wordpress/icons';
import { useSelect, useDispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { useRef, useEffect } from '@wordpress/element';

interface EditorSelect {
	getCurrentPostType: () => string;
	getCurrentPostId: () => number;
	isEditedPostNew: () => boolean;
	isEditedPostDirty: () => boolean;
	isSavingPost: () => boolean;
	isAutosavingPost: () => boolean;
	didPostSaveRequestSucceed: () => boolean;
}

interface EditorDispatch {
	savePost: () => Promise< void >;
}

// Build the front-end popup preview URL. Returns null when the required
// localized vars or popup id are unavailable.
const getPreviewUrl = ( popupId: number ): string | null => {
	const { homeUrl, previewNonce } = window.popupMakerBlockEditor || {};

	if ( ! homeUrl || ! previewNonce || ! popupId ) {
		return null;
	}

	const url = new URL( homeUrl );
	url.searchParams.set( 'popup_preview', previewNonce );
	url.searchParams.set( 'popup', String( popupId ) );

	return url.href;
};

/**
 * Popup Preview menu item component.
 */
const PopupPreviewMenuItem = (): JSX.Element | null => {
	// Handle to the opened preview tab, reused across clicks and saves.
	const previewWindow = useRef< Window | null >( null );

	const { postType, postId, isNew, isDirty, isSaving, didSaveSucceed } =
		useSelect( ( sel ) => {
			const editor = sel( 'core/editor' ) as EditorSelect;
			return {
				postType: editor.getCurrentPostType(),
				postId: editor.getCurrentPostId(),
				isNew: editor.isEditedPostNew(),
				isDirty: editor.isEditedPostDirty(),
				isSaving: editor.isSavingPost() && ! editor.isAutosavingPost(),
				didSaveSucceed: editor.didPostSaveRequestSucceed(),
			};
		}, [] );

	const { savePost } = useDispatch( 'core/editor' ) as EditorDispatch;

	// Refresh an already-open preview tab when a (non-auto) save succeeds.
	const wasSaving = useRef( false );
	useEffect( () => {
		const justSaved = wasSaving.current && ! isSaving && didSaveSucceed;
		wasSaving.current = isSaving;

		const win = previewWindow.current;
		if ( justSaved && win && ! win.closed ) {
			const url = getPreviewUrl( postId );
			if ( url ) {
				win.location.replace( url );
			}
		}
	}, [ isSaving, didSaveSucceed, postId ] );

	// Only on popups, and only once the popup has been saved (a new,
	// never-saved popup has no front-end record to preview yet).
	if ( postType !== 'popup' || isNew || ! getPreviewUrl( postId ) ) {
		return null;
	}

	const openOrFocusPreview = () => {
		const url = getPreviewUrl( postId );
		if ( ! url ) {
			return;
		}

		// A named target reuses the same tab on repeat clicks; assigning the
		// returned handle lets a later save refresh it. Omit `noopener` so the
		// handle is retained (admin-only, same-origin preview).
		previewWindow.current = window.open( url, `pum-preview-${ postId }` );
		previewWindow.current?.focus();
	};

	const handlePreview = () => {
		// Open (or focus) the tab immediately for a responsive click. If there
		// are unsaved edits, kick off a save — the save effect refreshes the
		// already-open tab once it succeeds, so the preview stays current.
		openOrFocusPreview();
		if ( isDirty ) {
			savePost();
		}
	};

	return (
		<PluginPreviewMenuItem icon={ seen } onClick={ handlePreview }>
			{ __( 'Preview Popup', 'popup-maker' ) }
		</PluginPreviewMenuItem>
	);
};

registerPlugin( 'popup-maker-preview-button', {
	render: PopupPreviewMenuItem,
} );

export default PopupPreviewMenuItem;
