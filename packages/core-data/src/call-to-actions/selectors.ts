import { applyPatch } from 'fast-json-patch';

import { applyFilters } from '@wordpress/hooks';
import { store as noticesStore } from '@wordpress/notices';
import { createRegistrySelector, createSelector } from '@wordpress/data';

import { DispatchStatus } from '../constants';
import { defaultValues, NOTICE_CONTEXT } from './constants';

import type { Updatable } from '@wordpress/core-data';
import type { Notice } from '../types';
import type { State } from './reducer';
import type { CallToAction } from './types';

/*****************************************************
 * SECTION: Entity selectors
 *****************************************************/
const entitySelectors = {
	/**
	 * Get all entities.
	 *
	 * @return {CallToAction[]} All entities.
	 */
	getCallToActions: createSelector(
		( state: State ) => state.allIds.map( ( id ) => state.byId[ id ] ),
		( state: State ) => [ state.allIds, Object.keys( state.byId ) ]
	),

	/**
	 * Get a single entity.
	 *
	 * @param {number} id - The ID of the entity to get.
	 *
	 * @return {CallToAction} The entity.
	 */
	getCallToAction: createSelector(
		( state: State, id: number ) => {
			const record = state?.byId?.[ id ];
			return record;
		},
		( state: State, id: number ) => [ state, id ]
	),

	/**
	 * Get filtered entities.
	 *
	 * @param {State}    state         The state.
	 * @param {Function} predicate     The predicate to filter the entities.
	 * @param {boolean}  maintainOrder Whether to maintain the order of the entities.
	 *
	 * @return {CallToAction< 'edit' >[]} The filtered entities.
	 */
	getFiltered: (
		state: State,
		predicate: ( item: CallToAction< 'edit' > ) => boolean,
		maintainOrder: boolean = false
	) => {
		if ( ! maintainOrder ) {
			return state.allIds
				.map( ( id ) => state.byId[ id ] )
				.filter( predicate );
		}

		return entitySelectors
			.getFilteredIds( state, predicate )
			.map( ( id ) => state.byId[ id ] );
	},

	/**
	 * Get filtered entity IDs.
	 *
	 * @param {State}    state     The state.
	 * @param {Function} predicate The predicate to filter the entities.
	 *
	 * @return {number[]} The filtered entity IDs.
	 */
	getFilteredIds: (
		state: State,
		predicate: ( item: CallToAction< 'edit' > ) => boolean
	) => state.allIds.filter( ( id ) => predicate( state.byId[ id ] ) ),
};

/*****************************************************
 * SECTION: Editor selectors
 *****************************************************/
const editorSelectors = {
	/**
	 * Get the editor ID.
	 *
	 * @return {number | undefined} The editor ID.
	 */
	getEditorId: createSelector(
		( state: State ) => state?.editorId,
		( state: State ) => [ state.editorId ]
	),

	/**
	 * Check if the editor is active.
	 *
	 * @return {boolean} Whether the editor is active.
	 */
	isEditorActive: createSelector(
		( state: State ): boolean => {
			const editorId = state?.editorId;

			return typeof editorId === 'number' && editorId > 0;
		},
		( state: State ) => [ state.editorId ]
	),

	/**
	 * Get the current editor values.
	 *
	 * @return {EditableCta} The current editor values.
	 */
	getCurrentEditorValues: createSelector(
		( state: State ) => {
			const editorId = state?.editorId;

			if ( typeof editorId === 'undefined' ) {
				return undefined;
			}

			return editorSelectors.getEditedCallToAction( state, editorId );
		},
		( state: State ) => [
			state.editedEntities?.[ state.editorId || 0 ],
			state.editHistoryIndex?.[ state.editorId || 0 ],
			state.editHistory?.[ state.editorId || 0 ],
			state.editorId,
		]
	),

	/**
	 * Check if the entity has been edited.
	 *
	 * @param {number} id - The ID of the entity to check.
	 *
	 * @return {boolean} Whether the entity has been edited.
	 */
	hasEditedEntity: createSelector(
		( state: State, id: number ) => {
			return !! state?.editedEntities?.[ id ];
		},
		( state: State, id: number ) => [ state.editedEntities?.[ id ], id ]
	),

	/**
	 * Get the edited entity.
	 *
	 * @param {number} id - The ID of the entity to get.
	 *
	 * @return {EditableCta} The edited entity.
	 */
	getEditedEntity: createSelector(
		( state: State, id: number ) => {
			return state?.editedEntities?.[ id ];
		},
		( state: State, id: number ) => [ state.editedEntities?.[ id ], id ]
	),

	/**
	 * Get the edit history for an entity.
	 *
	 * @param {number} id - The ID of the entity to get.
	 *
	 * @return {Partial<EditableCta>[]} The edit history.
	 */
	getEntityEditHistory: createSelector(
		( state: State, id: number ) => {
			return state?.editHistory?.[ id ];
		},
		( state: State, id: number ) => [ state.editHistory?.[ id ], id ]
	),

	/**
	 * Get the current edit history index.
	 *
	 * @param {number} id - The ID of the entity to get.
	 *
	 * @return {number} The current edit history index.
	 */
	getCurrentEditHistoryIndex: createSelector(
		( state: State, id: number ) => {
			return state.editHistoryIndex?.[ id ];
		},
		( state: State, id: number ) => [ state.editHistoryIndex?.[ id ], id ]
	),

	/**
	 * Check if the entity has edits.
	 *
	 * @param {number} id - The ID of the entity to check.
	 *
	 * @return {boolean} Whether the entity has edits.
	 */
	hasEdits: createSelector(
		( state: State, id: number ) => {
			return state.editHistory?.[ id ]?.length > 0;
		},
		( state: State, id: number ) => [ state.editHistory?.[ id ], id ]
	),

	/**
	 * Check if the entity can be undone.
	 *
	 * @param {number} id - The ID of the entity to check.
	 *
	 * @return {boolean} Whether the entity can be undone.
	 */
	hasUndo: createSelector(
		( state: State, id: number ) => {
			return state.editHistoryIndex?.[ id ] > 0;
		},
		( state: State, id: number ) => [ state.editHistoryIndex?.[ id ], id ]
	),

	/**
	 * Check if the entity can be redone.
	 *
	 * @param {number} id - The ID of the entity to check.
	 *
	 * @return {boolean} Whether the entity can be redone.
	 */
	hasRedo: createSelector(
		( state: State, id: number ) => {
			return (
				state.editHistoryIndex?.[ id ] <
				state.editHistory?.[ id ]?.length - 1
			);
		},
		( state: State, id: number ) => [ state.editHistoryIndex?.[ id ], id ]
	),

	/**
	 * Get the edited call to action.
	 *
	 * This applies all edits to the entity up to the current edit history index.
	 *
	 * @param {number} id - The ID of the entity to get.
	 *
	 * @return {EditableCta} The current entity values.
	 */
	getEditedCallToAction: createSelector(
		( state: State, id: number ) => {
			const baseEntity = state.editedEntities?.[ id ];
			const editHistory = state.editHistory?.[ id ];
			const editHistoryIndex = state.editHistoryIndex?.[ id ] ?? -1;

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
	),

	/**
	 * Get default entity values.
	 */
	getDefaultValues: createSelector(
		// eslint-disable-next-line @typescript-eslint/no-unused-vars
		( _state: State ) => {
			return applyFilters(
				'popupMaker.callToAction.defaultValues',
				defaultValues
			) as Updatable< CallToAction< 'edit' > >;
		},
		( _state: State ) => [ _state.editorId ]
	),
};

/*****************************************************
 * SECTION: Notice selectors
 *****************************************************/
const noticeSelectors = {
	/**
	 * Get notices.
	 */
	getNotices: createRegistrySelector( ( select ) => () => {
		const notices = select( noticesStore ).getNotices( NOTICE_CONTEXT );
		return ( notices || [] ) as Notice[];
	} ),

	/**
	 * Get notice by id.
	 */
	getNoticeById: createRegistrySelector(
		( select ) =>
			( id: string ): Notice | undefined => {
				const notices =
					select( noticesStore ).getNotices( NOTICE_CONTEXT );
				return notices?.find( ( n ) => n.id === id ) as
					| Notice
					| undefined;
			}
	),
};

/*****************************************************
 * SECTION: Resolution state selectors
 *****************************************************/
const resolutionSelectors = {
	/**
	 * Get resolution state for a specific entity.
	 */
	getResolutionState: createSelector(
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
	),

	/**
	 * Check if a resolution is idle.
	 */
	isIdle: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Idle;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if an entity is currently being resolved.
	 */
	isResolving: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Resolving;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if an entity resolution has completed successfully.
	 */
	hasResolved: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Success;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Check if an entity resolution has failed.
	 */
	hasFailed: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.status === DispatchStatus.Error;
		},
		( _state: State, id: number | string ) => [ id ]
	),

	/**
	 * Get the error for a failed resolution.
	 */
	getResolutionError: createSelector(
		( state: State, id: number | string ) => {
			const resolutionState = resolutionSelectors.getResolutionState(
				state,
				id
			);
			return resolutionState.error;
		},
		( _state: State, id: number | string ) => [ id ]
	),
};

const selectors = {
	// Entity selectors
	...entitySelectors,
	// Editor selectors
	...editorSelectors,
	// Notice selectors
	...noticeSelectors,
	// Resolution state selectors
	...resolutionSelectors,
};

export default selectors;
