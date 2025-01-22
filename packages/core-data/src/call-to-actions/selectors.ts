import { applyFilters } from '@wordpress/hooks';
import { createSelector } from '@wordpress/data';

import { defaultValues } from './constants';
import { createNoticeSelectors, createPostTypeSelectors } from '../utils';

import type { CallToAction } from './types';
import type { State } from './reducer';

/**
 * Generate entity & notice selectors.
 */
const entitySelectors =
	createPostTypeSelectors< CallToAction< 'edit' > >( 'pum_cta' );
const noticeSelectors = createNoticeSelectors( 'pum-cta-editor' );

/**
 * Check if the editor is active.
 */
const getEditorId = createSelector(
	( state: State ) => state?.editorId,
	( state: State ) => [ state.editorId ]
);

/**
 * Check if the editor is active.
 */
const isEditorActive = createSelector(
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
const getEntityDefaults = ( _state: State ) => {
	return applyFilters(
		'popupMaker.callToAction.defaultValues',
		defaultValues
	) as CallToAction< 'edit' >;
};

const selectors = {
	...entitySelectors,
	...noticeSelectors,
	getEditorId,
	isEditorActive,
	getEntityDefaults,
};

export default selectors;
