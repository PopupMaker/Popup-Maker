import {
	BooleanParam,
	NumberParam,
	StringParam,
	useQueryParams,
} from 'use-query-params';

import { useEffect, useCallback } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { callToActionStore } from '@popup-maker/core-data';

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

	// Allow initiating the editor directly from a url.
	const [ queryParams, setQueryParams ] = useQueryParams( {
		edit: NumberParam,
		add: BooleanParam,
		tab: StringParam,
	} );

	// Quick helper to reset all query params.
	const clearEditorParams = () =>
		setQueryParams( {
			add: undefined,
			edit: undefined,
			tab: undefined,
		} );

	// Extract params with usable names.
	const { edit, add, tab } = queryParams;

	// Initialize on mount if URL has an ID
	useEffect( () => {
if ( initialized ) {
			return;
		}
		initialized = true;

		const urlId = edit && edit > 0 ? edit : undefined;
			changeEditorId( urlId );
		} );

	/**
	 * Set the editor to edit a specific call to action.
	 *
	 * This both updates the editorId & sets matching url params.
	 *
	 * NOTE: It is important that both get updated at the same time, to prevent
	 * infinite state updates via useEffect above.
	 *
	 * @param {number|undefined} id Id to edit.
	 */
	const setEditorId = useCallback(
		( id: number | undefined ) => {
		changeEditorId( id );
		setQueryParams( {
			edit: id,
			} );
		},
		[ changeEditorId, setQueryParams ]
	);

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
