import domReady from '@wordpress/dom-ready';
import { doAction } from '@wordpress/hooks';

import initEditor from './init';

export * from './hooks';
export * from './editor';
export * from './registry';

export type * from './hooks';
export type * from './editor';
export type * from './registry';

/**
 * Initialize the editor.
 */
initEditor();

domReady( () => {
	initEditor();
	doAction( 'popupMaker.ctaEditor.init' );
} );
