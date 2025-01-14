import { addFilter, removeFilter } from '@wordpress/hooks';
import * as tabs from './tabs';

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
