import { store as noticesStore } from '@wordpress/notices';

import { createNoticeActions, createPostTypeActions } from '../utils';
import { ACTION_TYPES } from './constants';

import type { EditorId } from '../types';
import type { CallToAction, ThunkAction } from './types';
import type { ReducerAction } from './reducer';

const { EDITOR_CHANGE_ID } = ACTION_TYPES;

/**
 * Generate notice & entityactions.
 */
const entityActions =
	createPostTypeActions< CallToAction< 'edit' > >( 'pum_cta' );
const noticeActions = createNoticeActions( 'pum-cta-editor' );

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

				let _entityRecord: CallToAction< 'edit' > | undefined;

				if ( editorId === 'new' ) {
					_entityRecord = select.getEntityDefaults();
				} else if ( typeof editorId === 'number' && editorId > 0 ) {
					_entityRecord = await select.getById( editorId );
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
};

export default actions;
