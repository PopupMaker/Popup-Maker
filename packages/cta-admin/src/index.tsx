import './editor.scss';

import { BrowserRouter } from 'react-router-dom';
import { QueryParamProvider } from '@popup-maker/use-query-params';
import { ReactRouter6Adapter } from '@popup-maker/use-query-params/adapters/react-router-6';

import domReady from '@wordpress/dom-ready';
import { doAction } from '@wordpress/hooks';
import { createRoot } from '@wordpress/element';
import { RegistryProvider } from '@wordpress/data';

import { registry } from '@popup-maker/data';

import App from './App';
import initApp from './init';

import type { OldField } from '@popup-maker/fields';

export * from './context';
export * from './registry';
export * from './utils';
export type * from './registry';

declare global {
	interface Window {
		popupMakerCtaAdmin: {
			cta_types: {
				key: string;
				label: string;
				fields: OldField[];
			}[];
		};
	}
}

const renderer = () => {
	return (
		<BrowserRouter>
			<QueryParamProvider adapter={ ReactRouter6Adapter }>
				<RegistryProvider value={ registry }>
					<App />
				</RegistryProvider>
			</QueryParamProvider>
		</BrowserRouter>
	);
};

export const init = () => {
	const root = document.getElementById(
		'popup-maker-call-to-actions-root-container'
	);

	if ( ! root ) {
		return;
	}

	// Initialize the app registries and filters.
	initApp();

	// Allow for other plugins to hook into the admin app.
	doAction( 'popupMaker.ctaAdmin.init' );

	// createRoot was added in WP 6.2, so we need to check for it first.
	createRoot( root ).render( renderer() );
};

domReady( init );
