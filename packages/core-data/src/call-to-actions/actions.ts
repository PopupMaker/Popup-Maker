import { ACTION_TYPES } from './constants';
import { callToActionStore } from '.';

import type { EditorId } from '../types';
import type { CallToAction } from './types';

const { EDITOR_CHANGE_ID } = ACTION_TYPES;

// Refactored changeEditorId using thunk and our selectors
export const changeEditorId =
	( editorId: EditorId ) =>
	( { select, dispatch } ) => {
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

				let entityRecord: CallToAction | undefined;

				if ( editorId === 'new' ) {
					entityRecord = select.getEntityDefaults();
				} else if ( typeof editorId === 'number' && editorId > 0 ) {
					// TODO These are not getting properly typed :(..
					entityRecord =
						await select( callToActionStore ).getById( editorId );
				}

				dispatch( {
					type: EDITOR_CHANGE_ID,
					editorId,
					editorValues: entityRecord,
				} );
			} catch ( error ) {
				console.error( 'Failed to change editor ID:', error );
				dispatch( callToActionStore ).createNotice(
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
