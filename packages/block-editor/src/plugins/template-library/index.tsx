/**
 * Template Library plugin for the popup block editor.
 *
 * Adds a "Popup Templates" document panel with a browse button, and
 * auto-opens the template picker for brand-new, empty popups.
 */

import './editor.scss';

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

import TemplateLibraryModal from './modal';
import { getTemplateLibraryData } from './types';

const TemplateLibraryPlugin = (): JSX.Element | null => {
	const data = getTemplateLibraryData();

	const [ isOpen, setIsOpen ] = useState( false );
	const [ hasAutoOpened, setHasAutoOpened ] = useState( false );

	const { postType, postStatus, isEmpty } = useSelect( ( select ) => {
		const editor = select( 'core/editor' ) as unknown as {
			getCurrentPostType: () => string;
			getEditedPostAttribute: < T >( attribute: string ) => T;
		};
		const blockEditor = select( 'core/block-editor' ) as unknown as {
			getBlockCount: () => number;
		};

		return {
			postType: editor.getCurrentPostType(),
			postStatus:
				editor.getEditedPostAttribute< string >( 'status' ) ?? '',
			isEmpty: blockEditor.getBlockCount() === 0,
		};
	}, [] );

	// Auto-open the picker once for brand-new, empty popups.
	useEffect( () => {
		if (
			! hasAutoOpened &&
			data?.templates.length &&
			'popup' === postType &&
			'auto-draft' === postStatus &&
			isEmpty
		) {
			setHasAutoOpened( true );
			setIsOpen( true );
		}
	}, [ hasAutoOpened, data, postType, postStatus, isEmpty ] );

	if ( 'popup' !== postType || ! data || ! data.templates.length ) {
		return null;
	}

	return (
		<>
			<PluginDocumentSettingPanel
				name="popup-template-library-panel"
				title={ __( 'Popup Templates', 'popup-maker' ) }
				className="pum-template-library-panel"
			>
				<p>
					{ __(
						'Start from a ready-made popup layout with recommended triggers & settings.',
						'popup-maker'
					) }
				</p>
				<Button variant="secondary" onClick={ () => setIsOpen( true ) }>
					{ __( 'Browse Templates', 'popup-maker' ) }
				</Button>
			</PluginDocumentSettingPanel>
			{ isOpen && (
				<TemplateLibraryModal
					data={ data }
					onClose={ () => setIsOpen( false ) }
				/>
			) }
		</>
	);
};

registerPlugin( 'popup-maker-template-library', {
	render: TemplateLibraryPlugin,
} );

export default TemplateLibraryPlugin;
