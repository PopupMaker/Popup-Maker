import type { Settings } from './settings';

/* Global Var Declarations */
declare global {
	const wpApiSettings: {
		root: string;
		nonce: string;
	};

	const popupMakerCoreData: {
		currentSettings: Settings;
	};
}

export * from './types';
export * from './controls';
export { default as localControls } from './controls';
export * from './utils';

export * from './license';
export * from './call-to-actions';
export * from './settings';
export * from './popups';
export * from './url-search';
export * from './constants';

export * from './hooks';
