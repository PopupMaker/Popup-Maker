import { store as noticesStore } from '@wordpress/notices';

import { createNoticeActions, createPostTypeActions } from '../utils';
import { ACTION_TYPES } from './constants';

import type { EditorId } from '../types';
import type { CallToAction, ThunkAction } from './types';

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
	( { select, dispatch, resolveSelect, registry } ) => {
		return ( async () => {
			try {
				if ( typeof editorId === 'undefined' ) {
					dispatch( {
						type: EDITOR_CHANGE_ID,
						editorId: undefined,
						editorValues: undefined,
					} );
					return;
				}

				let entityRecord: CallToAction< 'edit' > | undefined;

				if ( editorId === 'new' ) {
					entityRecord = select.getEntityDefaults();
				} else if ( typeof editorId === 'number' && editorId > 0 ) {
					entityRecord = await select.getById( editorId );
				}

				dispatch( {
					type: EDITOR_CHANGE_ID,
					editorId,
					editorValues: entityRecord,
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
