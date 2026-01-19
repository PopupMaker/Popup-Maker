/**
 * Popup Title Panel for Block Editor.
 *
 * Uses proper React state for UI + saves via direct API call when Block Editor saves.
 * This approach avoids the anti-patterns that caused focus loss and stale closures.
 */

import { __ } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TextControl, ToggleControl } from '@wordpress/components';
import { useSelect, subscribe } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

interface PopupSettings {
	display_title?: boolean;
	[ key: string ]: unknown;
}

interface EditorStore {
	getCurrentPostType: () => string;
	getCurrentPostId: () => number;
	getEditedPostAttribute: < T >( attribute: string ) => T;
	isSavingPost: () => boolean;
	isAutosavingPost: () => boolean;
	didPostSaveRequestSucceed: () => boolean;
}

// Module-level storage for pending values (survives re-renders).
const pendingValues: {
	postId: number | null;
	title: string;
	displayTitle: boolean;
	settings: PopupSettings;
	hasChanges: boolean;
} = {
	postId: null,
	title: '',
	displayTitle: true,
	settings: {},
	hasChanges: false,
};

// Module-level save subscription (set up once).
let saveSubscriptionActive = false;

/**
 * Set up save subscription outside of React render cycle.
 * This prevents stale closures and ensures we always have current values.
 */
function setupSaveSubscription() {
	if ( saveSubscriptionActive ) {
		return;
	}
	saveSubscriptionActive = true;

	let wasSaving = false;

	subscribe( () => {
		const editor = ( window as typeof window & { wp?: { data?: { select: ( store: string ) => EditorStore } } } ).wp?.data?.select(
			'core/editor'
		) as EditorStore | null;

		if ( ! editor ) {
			return;
		}

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
					settings: {
						...pendingValues.settings,
						display_title: pendingValues.displayTitle,
					},
				},
			} )
				.then( () => {
					pendingValues.hasChanges = false;
				} )
				.catch( ( error ) => {
					// eslint-disable-next-line no-console
					console.error( 'Failed to save popup title settings:', error );
				} );
		}
	} );
}

/**
 * Popup Title Panel Component.
 *
 * Uses proper React state for controlled inputs to avoid focus loss.
 */
const PopupTitlePanel = () => {
	// Proper React state for controlled inputs.
	const [ localTitle, setLocalTitle ] = useState( '' );
	const [ localDisplayTitle, setLocalDisplayTitle ] = useState( true );
	const [ isInitialized, setIsInitialized ] = useState( false );

	const { postType, postId, popupTitle, settings } = useSelect(
		( select ) => {
			const editor = select( 'core/editor' ) as EditorStore;
			return {
				postType: editor.getCurrentPostType(),
				postId: editor.getCurrentPostId(),
				popupTitle:
					editor.getEditedPostAttribute< string >( 'popup_title' ) ??
					'',
				settings:
					editor.getEditedPostAttribute< PopupSettings >(
						'settings'
					) ?? {},
			};
		},
		[]
	);

	// Initialize from server data (once).
	useEffect( () => {
		if ( postType === 'popup' && ! isInitialized && postId ) {
			const title = popupTitle || '';
			const displayTitle = settings?.display_title ?? true;

			setLocalTitle( title );
			setLocalDisplayTitle( displayTitle );
			setIsInitialized( true );

			// Initialize pending values for the save subscription.
			pendingValues.postId = postId;
			pendingValues.title = title;
			pendingValues.displayTitle = displayTitle;
			pendingValues.settings = settings || {};
			pendingValues.hasChanges = false;

			// Set up save subscription (only happens once per page load).
			setupSaveSubscription();
		}
	}, [ postType, postId, popupTitle, settings, isInitialized ] );

	// Only render for popup post type.
	if ( postType !== 'popup' ) {
		return null;
	}

	/**
	 * Handle title change.
	 *
	 * @param value New title value.
	 */
	const handleTitleChange = ( value: string ) => {
		setLocalTitle( value );
		pendingValues.title = value;
		pendingValues.hasChanges = true;
	};

	/**
	 * Handle display toggle change.
	 *
	 * @param value New display value.
	 */
	const handleDisplayChange = ( value: boolean ) => {
		setLocalDisplayTitle( value );
		pendingValues.displayTitle = value;
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
			<ToggleControl
				label={ __( 'Display on frontend', 'popup-maker' ) }
				checked={ localDisplayTitle }
				onChange={ handleDisplayChange }
				help={ __(
					'Show the popup title on the frontend.',
					'popup-maker'
				) }
			/>
		</PluginDocumentSettingPanel>
	);
};

// Register the plugin.
registerPlugin( 'popup-maker-title-panel', {
	render: PopupTitlePanel,
	icon: null,
} );

export default PopupTitlePanel;
