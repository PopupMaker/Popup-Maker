import { applyFilters } from '@wordpress/hooks';
import { createRegistrySelector, createSelector } from '@wordpress/data';

import { defaultValues } from './constants';
import { createNoticeSelectors, createPostTypeSelectors } from '../utils';

import type { CallToAction } from './types';
import type { State } from './reducer';
import { callToActionStore } from '.';
import type { Updatable } from '@wordpress/core-data/src/entity-types';

const entitySelectorMapping = {
	getById: 'getCallToAction',
	getAll: 'getCallToActions',
	getEditedById: 'getEditedCallToAction',
} as const;

type EntitySelectorMappingType = typeof entitySelectorMapping;

/**
 * Generate entity & notice selectors.
 */
const entitySelectors = createPostTypeSelectors<
	CallToAction< 'edit' >,
	EntitySelectorMappingType
>( 'pum_cta', entitySelectorMapping );

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
 * Get the current editor values.
 */
const currentEditorValues = createRegistrySelector(
	( select ) => ( state: State ) => {
		const editorId = state?.editorId;

		if ( typeof editorId === 'undefined' ) {
			return undefined;
		}

		if ( typeof editorId === 'string' && editorId === 'new' ) {
			return getDefaultValues( state );
		}

		const record = select( callToActionStore ).getEditorValues( editorId );

		return record;
	}
);

/**
 * Get default entity values.
 */
const getDefaultValues = ( _state: State ) => {
	return applyFilters(
		'popupMaker.callToAction.defaultValues',
		defaultValues
	) as Updatable< CallToAction< 'edit' > >;
};

const selectors = {
	...entitySelectors,
	...noticeSelectors,
	getEditorId,
	isEditorActive,
	getDefaultValues,
	currentEditorValues,
};

export default selectors;
