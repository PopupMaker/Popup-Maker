import './editor.scss';

import { BrowserRouter } from 'react-router-dom';
import { QueryParamProvider } from 'use-query-params';
import { ReactRouter6Adapter } from 'use-query-params/adapters/react-router-6';

import { createRoot } from '@wordpress/element';
import { RegistryProvider } from '@wordpress/data';

import { registry } from '@popup-maker/data';

import App from './App';
import domReady from '@wordpress/dom-ready';

declare global {
	interface Window {
		popupMaker: {
			globalVars: {
				assetsUrl: string;
				wpVersion: number;
				pluginUrl: string;
				adminUrl: string;
				version: string;
				permissions: {
					edit_ctas: boolean;
					edit_popups: boolean;
					edit_popup_themes: boolean;
					mange_settings: boolean;
				};
				isProInstalled?: '1' | '';
				isProActivated?: '1' | '';
			};
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
