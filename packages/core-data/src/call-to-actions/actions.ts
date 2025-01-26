import { store as noticesStore } from '@wordpress/notices';

import { createNoticeActions, createPostTypeActions } from '../utils';
import { ACTION_TYPES } from './constants';

import type { EditorId } from '../types';
import type { CallToAction, ThunkAction } from './types';
import type { ReducerAction } from './reducer';
import type { Updatable } from '@wordpress/core-data';

const { EDITOR_CHANGE_ID } = ACTION_TYPES;

const entityActionMapping = {
	createRecord: 'createCallToAction',
	updateRecord: 'updateCallToAction',
	editRecord: 'editCallToAction',
	saveRecord: 'saveCallToAction',
	deleteRecord: 'deleteCallToAction',
} as const;

type EntityActionMappingType = typeof entityActionMapping;

/**
 * Generate notice & entity actions.
 */
const entityActions = createPostTypeActions<
	CallToAction< 'edit' >,
	EntityActionMappingType
>( 'pum_cta', entityActionMapping );

const noticeActions = createNoticeActions( 'pum-cta-editor' );

const updateEditorValues =
	( values: Updatable< CallToAction< 'edit' > > ): ThunkAction< void > =>
	async ( { dispatch, select } ) => {
		const editorId = select.getEditorId();

		if ( typeof editorId === 'undefined' ) {
			return;
		}

		if ( typeof editorId === 'string' && editorId === 'new' ) {
			return;
		}

		await dispatch.editCallToAction( editorId, values );
	};

const saveEditorValues =
	(): ThunkAction< void > =>
	async ( { dispatch, select } ) => {
		const editorId = select.getEditorId();
		const editorValues = select.currentEditorValues();

		if ( ! editorValues ) {
			return;
		}

		if ( typeof editorId === 'string' && editorId === 'new' ) {
			await dispatch.createCallToAction( editorValues );
		} else {
			await dispatch.saveCallToAction( Number( editorId ) );
		}
	};

// Refactored changeEditorId using thunk and our selectors
const changeEditorId =
	( editorId: EditorId ): ThunkAction< void > =>
	( { select, dispatch, registry } ) => {
		return ( async () => {
			try {
				if ( typeof editorId === 'undefined' ) {
					const action: ReducerAction = {
						type: EDITOR_CHANGE_ID,
						editorId: undefined,
					};

					dispatch( action );
					return;
				}

				// TODO REVIEW: This might not be needed to await the editor values chaning.
				// @ts-ignore Not using now, testing if needed for future.
				let _entityRecord: Updatable< CallToAction > | undefined;

				if ( editorId === 'new' ) {
					_entityRecord = select.getDefaultValues();
				} else if ( typeof editorId === 'number' && editorId > 0 ) {
					// Await is needed, if no editor values set, it resolves from the API with existing.
					_entityRecord = await select.getEditedCallToAction( editorId );
				}

				dispatch( {
					type: EDITOR_CHANGE_ID,
					editorId,
				} );
			} catch ( error ) {
				console.error( 'Failed to change editor ID:', error );
				registry
					.dispatch( noticesStore )
					.createNotice(
						'error',
						error instanceof Error
							? error.message
							: 'Failed to change editor',
						{
							id: 'editor-change-error',
						}
					);
			}
		} )();
	};

const actions = {
	...entityActions,
	...noticeActions,
	changeEditorId,
	updateEditorValues,
	saveEditorValues,
};

export default actions;
