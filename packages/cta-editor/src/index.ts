import domReady from '@wordpress/dom-ready';
import { doAction } from '@wordpress/hooks';

import initEditor from './init';
import type { OldField } from '@popup-maker/fields';

export * from './hooks';
export * from './editor';
export * from './registry';

export type * from './hooks';
export type * from './editor';
export type * from './registry';

declare global {
	interface Window {
		popupMakerCtaEditor: {
			cta_types: {
				key: string;
				label: string;
				fields: OldField[];
			}[];
		};
	}
}

/**
 * Initialize the editor.
 */
initEditor();

domReady( () => {
	initEditor();
	doAction( 'popupMaker.ctaEditor.init' );
} );
