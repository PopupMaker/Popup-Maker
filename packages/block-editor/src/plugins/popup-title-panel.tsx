/**
 * Popup Title Panel for Block Editor.
 *
 * Saves popup_title via apiFetch after block editor save completes.
 * Module-level state avoids stale closures and focus loss.
 * This field will eventually be replaced by heading blocks with full editor styling.
 */

import { __ } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TextControl } from '@wordpress/components';
import { useSelect, subscribe, select, dispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

interface EditorStore {
	getCurrentPostType: () => string;
	getCurrentPostId: () => number;
	getEditedPostAttribute: < T >( attribute: string ) => T;
	isSavingPost: () => boolean;
	isAutosavingPost: () => boolean;
	didPostSaveRequestSucceed: () => boolean;
}

// Module-level storage for pending title (survives re-renders).
const pendingValues: {
	postId: number | null;
	title: string;
	hasChanges: boolean;
} = {
	postId: null,
	title: '',
	hasChanges: false,
};

// Module-level save subscription (set up once).
let saveSubscriptionActive = false;

/**
 * Subscribe to editor save events outside React render cycle.
 * Fires apiFetch to persist popup_title after successful save.
 */
function setupSaveSubscription() {
	if ( saveSubscriptionActive ) {
		return;
	}
	saveSubscriptionActive = true;

	let wasSaving = false;

	subscribe( () => {
		const editor = select( 'core/editor' ) as EditorStore;

		const isSaving = editor.isSavingPost();
		const isAutosaving = editor.isAutosavingPost();
		const didSaveSucceed = editor.didPostSaveRequestSucceed();

		const justFinishedSaving = wasSaving && ! isSaving && ! isAutosaving;
		wasSaving = isSaving && ! isAutosaving;

		if (
			justFinishedSaving &&
			didSaveSucceed &&
			pendingValues.hasChanges &&
			pendingValues.postId
		) {
			apiFetch( {
				path: `/popup-maker/v2/popups/${ pendingValues.postId }`,
				method: 'POST',
				data: {
					popup_title: pendingValues.title,
				},
			} )
				.then( () => {
					pendingValues.hasChanges = false;
				} )
				.catch( ( error ) => {
					// eslint-disable-next-line no-console
					console.error( 'Failed to save popup title:', error );
					dispatch( 'core/notices' ).createErrorNotice(
						__( 'Failed to save popup title.', 'popup-maker' ),
						{ type: 'snackbar' }
					);
				} );
		}
	} );
}

/**
 * Popup Title Panel Component.
 */
const PopupTitlePanel = () => {
	const [ localTitle, setLocalTitle ] = useState( '' );
	const [ isInitialized, setIsInitialized ] = useState( false );

	const { postType, postId, popupTitle } = useSelect( ( sel ) => {
		const editor = sel( 'core/editor' ) as EditorStore;
		return {
			postType: editor.getCurrentPostType(),
			postId: editor.getCurrentPostId(),
			popupTitle:
				editor.getEditedPostAttribute< string >( 'popup_title' ) ?? '',
		};
	}, [] );

	// Initialize from server data (once).
	useEffect( () => {
		if ( postType === 'popup' && ! isInitialized && postId ) {
			const title = popupTitle || '';

			setLocalTitle( title );
			setIsInitialized( true );

			pendingValues.postId = postId;
			pendingValues.title = title;
			pendingValues.hasChanges = false;

			setupSaveSubscription();
		}
	}, [ postType, postId, popupTitle, isInitialized ] );

	if ( postType !== 'popup' ) {
		return null;
	}

	const handleTitleChange = ( value: string ) => {
		setLocalTitle( value );
		pendingValues.title = value;
		pendingValues.hasChanges = true;
	};

	return (
		<PluginDocumentSettingPanel
			name="popup-title-panel"
			title={ __( 'Popup Title', 'popup-maker' ) }
			className="popup-maker-title-panel"
		>
			<TextControl
				label={ __( 'Title', 'popup-maker' ) }
				value={ localTitle }
				onChange={ handleTitleChange }
				help={ __(
					'Shown as headline inside the popup.',
					'popup-maker'
				) }
			/>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'popup-maker-title-panel', {
	render: PopupTitlePanel,
	icon: null,
} );

export default PopupTitlePanel;
