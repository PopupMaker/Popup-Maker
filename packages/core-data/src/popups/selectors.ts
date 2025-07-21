import { applyPatch } from 'fast-json-patch';

import { applyFilters } from '@wordpress/hooks';
import { store as noticesStore } from '@wordpress/notices';
import { createRegistrySelector, createSelector } from '@wordpress/data';

import { DispatchStatus } from '../constants';
import { defaultValues, NOTICE_CONTEXT } from './constants';

import type { Notice } from '../types';
import type { State } from './reducer';
import type { Popup, EditablePopup } from './types';

/*****************************************************
 * SECTION: Entity selectors
 *****************************************************/

/**
 * Get all entities.
 *
 * @return {Popup[]} All entities.
 */
export const getPopups = createSelector(
	( state: State ) => state.allIds.map( ( id ) => state.byId[ id ] ),
	( state: State ) => [ state.allIds, state.byId ]
);

/**
 * Get a single entity.
 *
 * @param {number} id - The ID of the entity to get.
 *
 * @return {Popup} The entity.
 */
export const getPopup = createSelector(
	( state: State, id: number ) => {
		const record = state?.byId?.[ id ];
		return record;
	},
	( state: State, id: number ) => [ state, id ]
);

/**
 * Get the error for a specific entity.
 */
export const getFetchError = createSelector(
	( state: State, id?: number ) => {
		if ( typeof id === 'number' ) {
			return state.errors.byId[ id ];
		}
		return state.errors.global;
	},
	( state: State, id: number | string ) => [ state.errors, id ]
);

/**
 * Get filtered entities.
 *
 * TODO Should this be a createSelector?
 *
 * @param {State}    state         The state.
 * @param {Function} predicate     - The predicate to filter the entities.
 * @param {boolean}  maintainOrder - Whether to maintain the order of the entities.
 *
 * @return {Popup< 'edit' >[]} The filtered entities.
 */
export const getFiltered = (
	state: State,
	predicate: ( item: Popup< 'edit' > ) => boolean,
	maintainOrder: boolean = false
) => {
	if ( ! maintainOrder ) {
		return state.allIds
			.map( ( id ) => state.byId[ id ] )
			.filter( predicate );
	}

	return getFilteredIds( state, predicate ).map( ( id ) => state.byId[ id ] );
};

/**
 * Get filtered entity IDs.
 *
 * TODO Should this be a createSelector?
 *
 * @param {State}    state     The state.
 * @param {Function} predicate The predicate to filter the entities.
 *
 * @return {number[]} The filtered entity IDs.
 */
export const getFilteredIds = (
	state: State,
	predicate: ( item: Popup< 'edit' > ) => boolean
) => state.allIds.filter( ( id ) => predicate( state.byId[ id ] ) );

/*****************************************************
 * SECTION: Editor selectors
 *****************************************************/

/**
 * Get the editor ID.
 *
 * @return {number | string} The editor ID.
 */
export const getEditorId = createSelector(
	( state: State ) => state?.editorId,
	( state: State ) => [ state.editorId ]
);

/**
 * Check if the editor is active.
 *
 * @return {boolean} Whether the editor is active.
 */
export const isEditorActive = createSelector(
	( state: State ): boolean => {
		const editorId = state?.editorId;

		// TODO Support non-presaved new entities.
		if ( typeof editorId === 'string' && editorId === 'new' ) {
			return true;
		}

		return typeof editorId === 'number' && editorId > 0;
	},
	( state: State ) => [ state.editorId ]
);

/**
 * Get the current editor values.
 *
 * @return {EditablePopup | undefined} The current editor values.
 */
export const getCurrentEditorValues = createSelector(
	( state: State ) => {
		const editorId = state?.editorId;

		if ( typeof editorId === 'undefined' ) {
			return undefined;
		}

		return getEditedPopup( state, editorId );
	},
	( state: State ) => [
		state.editedEntities?.[ state.editorId || 0 ],
		state.editHistoryIndex?.[ state.editorId || 0 ],
		state.editHistory?.[ state.editorId || 0 ],
		state.editorId,
	]
);

/**
 * Check if the entity has been edited.
 *
 * @param {number} id - The ID of the entity to check.
 *
 * @return {boolean} Whether the entity has been edited.
 */
export const hasEditedEntity = createSelector(
	( state: State, id: number ) => {
		return !! state?.editedEntities?.[ id ];
	},
	( state: State, id: number ) => [ state.editedEntities?.[ id ], id ]
);

/**
 * Get the edited entity.
 *
 * @param {number} id - The ID of the entity to get.
 *
 * @return {EditablePopup} The edited entity.
 */
export const getEditedEntity = createSelector(
	( state: State, id: number ) => {
		return state?.editedEntities?.[ id ];
	},
	( state: State, id: number ) => [ state.editedEntities?.[ id ], id ]
);

/**
 * Get the edit history for an entity.
 *
 * @param {number} id - The ID of the entity to get.
 *
 * @return {Operation[][]} The edit history operations.
 */
export const getEntityEditHistory = createSelector(
	( state: State, id: number ) => {
		return state?.editHistory?.[ id ];
	},
	( state: State, id: number ) => [ state.editHistory?.[ id ], id ]
);

/**
 * Get the current edit history index.
 *
 * @param {number} id - The ID of the entity to get.
 *
 * @return {number} The current edit history index.
 */
export const getCurrentEditHistoryIndex = createSelector(
	( state: State, id: number ) => {
		return state.editHistoryIndex?.[ id ];
	},
	( state: State, id: number ) => [ state.editHistoryIndex?.[ id ], id ]
);

/**
 * Check if the entity has edits.
 *
 * @param {number} id - The ID of the entity to check.
 *
 * @return {boolean} Whether the entity has edits.
 */
export const hasEdits = createSelector(
	( state: State, id: number ) => {
		return state.editHistory?.[ id ]?.length > 0;
	},
	( state: State, id: number ) => [ state.editHistory?.[ id ], id ]
);

/**
 * Check if the entity can be undone.
 *
 * @param {number} id - The ID of the entity to check.
 *
 * @return {boolean} Whether the entity can be undone.
 */
export const hasUndo = createSelector(
	( state: State, id: number ) => {
		if (
			typeof state.editHistoryIndex?.[ id ] !== 'number' ||
			typeof state.editHistory?.[ id ] !== 'object'
		) {
			return false;
		}

		return state.editHistoryIndex?.[ id ] >= 0;
	},
	( state: State, id: number ) => [ state.editHistoryIndex?.[ id ], id ]
);

/**
 * Check if the entity can be redone.
 *
 * @param {number} id - The ID of the entity to check.
 *
 * @return {boolean} Whether the entity can be redone.
 */
export const hasRedo = createSelector(
	( state: State, id: number ) => {
		if (
			typeof state.editHistoryIndex?.[ id ] !== 'number' ||
			typeof state.editHistory?.[ id ] !== 'object'
		) {
			return false;
		}

		return (
			state.editHistoryIndex?.[ id ] <
			state.editHistory?.[ id ]?.length - 1
		);
	},
	( state: State, id: number ) => [ state.editHistoryIndex?.[ id ], id ]
);

/**
 * Get the edited popup.
 *
 * This applies all edits to the entity up to the current edit history index.
 *
 * @param {number} id - The ID of the entity to get.
 *
 * @return {EditablePopup} The current entity values.
 */
export const getEditedPopup = createSelector(
	( state: State, id: number ) => {
		const baseEntity = state.editedEntities?.[ id ];
		const editHistory = state.editHistory?.[ id ];
		const editHistoryIndex = state.editHistoryIndex?.[ id ];

		if ( ! baseEntity ) {
			return undefined;
		}

		// If index is -1, return base entity without edits
		if ( editHistoryIndex === -1 ) {
			return baseEntity;
		}

		if ( ! editHistory?.length ) {
			return baseEntity;
		}

		// Get the edits up to the current index
		const editsToApply = editHistory.slice( 0, editHistoryIndex + 1 );

		// Apply each patch set in sequence
		const editedEntity = editsToApply.reduce( ( entity, patchSet ) => {
			const patchArray = Array.isArray( patchSet )
				? patchSet
				: [ patchSet ];

			const patched = applyPatch( entity, patchArray, true, false );
			return patched.newDocument;
		}, baseEntity );

		return editedEntity;
	},
	( state: State, id: number ) => [
		state.editedEntities?.[ id ],
		state.editHistoryIndex?.[ id ],
		state.editHistory?.[ id ],
		id,
	]
);

/**
 * Get default entity values.
 */
export const getDefaultValues = createSelector(
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	( _state: State ) => {
		return applyFilters(
			'popupMaker.popup.defaultValues',
			defaultValues
		) as EditablePopup;
	},
	( _state: State ) => [ _state.editorId ]
);

/*****************************************************
 * SECTION: Notice selectors
 *****************************************************/

/**
 * Get notices.
 */
export const getNotices = createRegistrySelector( ( select ) => () => {
	const notices = select( noticesStore ).getNotices( NOTICE_CONTEXT );
	return ( notices || [] ) as Notice[];
} );

/**
 * Get notice by id.
 */
export const getNoticeById = createRegistrySelector(
	( select ) =>
		( id: string ): Notice | undefined => {
			const notices = select( noticesStore ).getNotices( NOTICE_CONTEXT );
			return notices?.find( ( n ) => n.id === id ) as Notice | undefined;
		}
);

/*****************************************************
 * SECTION: Resolution state selectors
 *****************************************************/

/**
 * Get resolution state for a specific entity.
 */
export const getResolutionState = createSelector(
	( state: State, id: number | string ) => {
		const resolutionState = state.resolutionState?.[ id ];

		// If no resolution state exists, return idle
		if ( ! resolutionState ) {
			return {
				status: DispatchStatus.Idle,
			};
		}

		return resolutionState;
	},
	( _state: State, id: number | string ) => [ id ]
);

/**
 * Check if a resolution is idle.
 */
export const isIdle = createSelector(
	( state: State, id: number | string ) => {
		const resolutionState = getResolutionState( state, id );
		return resolutionState.status === DispatchStatus.Idle;
	},
	( _state: State, id: number | string ) => [ id ]
);

/**
 * Check if an entity is currently being resolved.
 */
export const isResolving = createSelector(
	( state: State, id: number | string ) => {
		const resolutionState = getResolutionState( state, id );
		return resolutionState.status === DispatchStatus.Resolving;
	},
	( _state: State, id: number | string ) => [ id ]
);

/**
 * Check if an entity resolution has completed successfully.
 */
export const hasResolved = createSelector(
	( state: State, id: number | string ) => {
		const resolutionState = getResolutionState( state, id );
		return resolutionState.status === DispatchStatus.Success;
	},
	( _state: State, id: number | string ) => [ id ]
);

/**
 * Check if an entity resolution has failed.
 */
export const hasFailed = createSelector(
	( state: State, id: number | string ) => {
		const resolutionState = getResolutionState( state, id );
		return resolutionState.status === DispatchStatus.Error;
	},
	( _state: State, id: number | string ) => [ id ]
);

/**
 * Get the error for a failed resolution.
 */
export const getResolutionError = createSelector(
	( state: State, id: number | string ) => {
		const resolutionState = getResolutionState( state, id );
		return resolutionState.error;
	},
	( _state: State, id: number | string ) => [ id ]
);

export default {
	/*****************************************************
	 * SECTION: Entity selectors
	 *****************************************************/
	getPopups,
	getPopup,
	getFetchError,
	getFiltered,
	getFilteredIds,

	/*****************************************************
	 * SECTION: Editor selectors
	 *****************************************************/
	getEditorId,
	isEditorActive,
	getCurrentEditorValues,
	hasEditedEntity,
	getEditedEntity,
	getEntityEditHistory,
	getCurrentEditHistoryIndex,
	hasEdits,
	hasUndo,
	hasRedo,
	getEditedPopup,
	getDefaultValues,

	/*****************************************************
	 * SECTION: Notice selectors
	 *****************************************************/
	getNotices,
	getNoticeById,

	/*****************************************************
	 * SECTION: Resolution state selectors
	 *****************************************************/
	getResolutionState,
	isIdle,
	isResolving,
	hasResolved,
	hasFailed,
	getResolutionError,
};
