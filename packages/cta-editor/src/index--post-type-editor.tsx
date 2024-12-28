import './styles.scss';

import { registry } from '@popup-maker/data';
import domReady from '@wordpress/dom-ready';
import { createRoot, useEffect } from '@wordpress/element';
import { RegistryProvider } from '@wordpress/data';

import SettingsEditor from './settings-editor';

const ctaRenderer = ( App: () => JSX.Element ) => {
	return (
		<RegistryProvider value={ registry }>
			<App />
		</RegistryProvider>
	);
};

// Placeholder for full CTA editor ( as opposed to only CTA settings editor ).
const CallToActionEditor = () => {
	return <></>;
};

const CallToActionSettingsEditor = ( { ctaId }: { ctaId: number } ) => {
	const { setEditorId } = useEditor();

	useEffect( () => {
		setEditorId( ctaId );
	}, [ ctaId, setEditorId ] );

	return <SettingsEditor ctaId={ ctaId } />;
};

export const settingsInit = () => {
	const containerId = 'popup-maker-cta-settings-root-container';
	const root = document.getElementById( containerId );

	// Get from the URL edit={id} or #post_ID input
	const ctaId = root?.getAttribute( 'data-cta-id' );

	if ( ! root || ! root?.hasAttribute( 'data-cta-id' ) ) {
		return;
	}

	// createRoot was added in WP 6.2, so we need to check for it first.
	createRoot( root ).render(
		ctaRenderer( () => <SettingsEditor ctaId={ Number( ctaId ) } /> )
	);
};

domReady( settingsInit );
