import './editor.scss';

import { CALL_TO_ACTION_STORE } from '@popup-maker/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import Edit from './edit';
import Header from './header';
import List from './list';
import Notices from './notices';

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
			};
		};
	}
}

const {
	permissions: { edit_ctas: userCanEditCallToActions },
} = window.popupMaker.globalVars;

/**
 * Generates the Call To Actions tab component & sub-app.
 */
const CallToActionsView = () => {
	// Fetch needed data from the @popup-maker/core-data & @wordpress/data stores.
	const isEditorActive = useSelect(
		( select ) => select( CALL_TO_ACTION_STORE ).isEditorActive(),
		[]
	);

	// If the user doesn't have the manage_settings permission, show a message.
	if ( ! userCanEditCallToActions ) {
		return (
			<div className="call-to-action-list permission-denied">
				<Notices />
				<h3>{ __( 'Permission Denied', 'popup-maker' ) }</h3>
				<p>
					<strong>
						{ __(
							'You do not have permission to manage Call To Actions.',
							'popup-maker'
						) }
					</strong>
				</p>
			</div>
		);
	}

	return (
		<div className="call-to-action-list">
			<Notices />
			<Header />
			<List />
			{ isEditorActive && <Edit /> }
		</div>
	);
};

export default CallToActionsView;
