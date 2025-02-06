import { addFilter, removeFilter } from '@wordpress/hooks';
import * as tabs from './tabs';

import { initFields } from './fields';

import {
	registerEditorHeaderAction,
	registerEditorHeaderOption,
} from '../../registry';

import * as headerActions from './editor-header-actions';
import * as headerOptions from './editor-header-options';

export const initHeaderActions = () => {
	// Register core editor header actions.
	Object.values( headerActions ).forEach( ( action ) => {
		registerEditorHeaderAction( action );
	} );

	// Register core editor header options.
	Object.values( headerOptions ).forEach( ( option ) => {
		registerEditorHeaderOption( option );
	} );
};

export const initTabs = () => {
	addFilter(
		'popupMaker.callToActionEditor.tabs',
		'popup-maker/cta-editor/tabs',
		( registeredTabs ) => [ ...registeredTabs, ...Object.values( tabs ) ]
	);
};

export const deinitTabs = () => {
	removeFilter(
		'popupMaker.callToActionEditor.tabs',
		'popup-maker/cta-editor/tabs'
	);
};

let initialized = false;

const initEditor = () => {
	if ( initialized ) {
		return;
	}

	initTabs();
	initFields();
	initHeaderActions();

	initialized = true;
};

export default initEditor;
