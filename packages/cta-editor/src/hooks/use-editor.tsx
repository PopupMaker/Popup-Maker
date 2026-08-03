import {
	BooleanParam,
	NumberParam,
	StringParam,
	useQueryParams,
} from '@popup-maker/use-query-params';

import { callToActionStore } from '@popup-maker/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useCallback } from '@wordpress/element';

import type { CtaEditorId } from '@popup-maker/core-data';

let initialized = false;

const useEditor = () => {
	// Fetch needed data from the @popup-maker/core-data & @wordpress/data stores.
	const { isEditorActive, editorId } = useSelect( ( select ) => {
		const storeSelect = select( callToActionStore );

		return {
			// Editor Status.
			editorId: storeSelect.getEditorId(),
			isEditorActive: storeSelect.isEditorActive(),
		};
	}, [] );

	// Grab needed action dispatchers.
	const { changeEditorId } = useDispatch( callToActionStore );

	const [ queryParams, setQueryParams ] = useQueryParams( {
		edit: NumberParam,
		add: BooleanParam,
		tab: StringParam,
	} );

	// Extract params with usable names.
	const { edit, add, tab } = queryParams;

	// Initialize on mount if URL has an ID
	useEffect(
		() => {
			if ( initialized ) {
				return;
			}

			// Only once on app load.
			initialized = true;

			let urlId: CtaEditorId;

			if ( edit && edit > 0 ) {
				urlId = edit;
			} else if ( add ) {
				urlId = 'new';
			}

			// Only initialize if we have a URL ID and no current editor ID
			if ( urlId && ! editorId ) {
				changeEditorId( urlId );
			}
		},
		// Only run on mount.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[]
	);

	/**
	 * Set the editor to edit a specific call to action.
	 *
	 * Pass `'new'` to start an unsaved draft.
	 *
	 * This both updates the editorId & sets matching url params.
	 *
	 * NOTE: It is important that both get updated at the same time, to prevent
	 * infinite state updates via useEffect above.
	 *
	 * @param {CtaEditorId} id Id to edit, or 'new' for a draft.
	 */
	const setEditorId = useCallback(
		( id: CtaEditorId ) => {
			changeEditorId( id );
			setQueryParams( {
				edit: typeof id === 'number' ? id : undefined,
				add: 'new' === id ? true : undefined,
			} );
		},
		[ changeEditorId, setQueryParams ]
	);

	// Quick helper to reset all query params.
	const clearEditorParams = () => {
		changeEditorId( undefined );
		setQueryParams( {
			add: undefined,
			edit: undefined,
			tab: undefined,
		} );
	};

	return {
		tab: tab === null ? undefined : tab,
		setTab: ( newTab: string ) => setQueryParams( { tab: newTab } ),
		setEditorId,
		clearEditorParams,
		editorId,
		isEditorActive,
	};
};

export default useEditor;
