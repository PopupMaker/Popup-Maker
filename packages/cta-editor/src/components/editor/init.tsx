import { addFilter, removeFilter } from '@wordpress/hooks';
import * as tabs from './tabs';

import { initFields } from './fields';

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

	initialized = true;
};

export default initEditor;
