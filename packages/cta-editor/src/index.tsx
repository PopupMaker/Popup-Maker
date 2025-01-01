import './editor.scss';

import { BrowserRouter } from 'react-router-dom';
import { QueryParamProvider } from 'use-query-params';
import { ReactRouter6Adapter } from 'use-query-params/adapters/react-router-6';

import { createRoot } from '@wordpress/element';
import { RegistryProvider } from '@wordpress/data';

import { registry } from '@popup-maker/data';

import App from './App';
import domReady from '@wordpress/dom-ready';

import type { OldField } from '@popup-maker/fields';

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

	// createRoot was added in WP 6.2, so we need to check for it first.
	createRoot( root ).render( renderer() );
};

domReady( init );
