import { createSelector } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

import { defaultValues } from './constants';

import type { CallToAction, State } from './types';

/**
 * Check if the editor is active.
 */
export const getEditorId = createSelector(
	( state: State ) => state?.editorId,
	( state: State ) => [ state.editorId ]
);

/**
 * Check if the editor is active.
 */
export const isEditorActive = createSelector(
	( state: State ): boolean => {
		const editorId = state?.editorId;

		if ( typeof editorId === 'string' && editorId === 'new' ) {
			return true;
		}

		return typeof editorId === 'number' && editorId > 0;
	},
	( state: State ) => [ state.editorId ]
);

/**
 * Get default entity values.
 */
export const getEntityDefaults = ( _state: State ) => {
	return applyFilters(
		'popupMaker.callToAction.defaultValues',
		defaultValues
	) as CallToAction;
};
