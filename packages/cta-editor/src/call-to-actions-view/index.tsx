import './editor.scss';
import { CALL_TO_ACTION_STORE } from '@popup-maker/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import Header from './header';
import List from '../list';
import Notices from './notices';
import { getGlobalVars } from '../utils';
import {
	Editor as BaseEditor,
	withModal,
	withQueryParams,
} from '../components';

const Editor = withQueryParams( withModal( BaseEditor ) );

/**
 * Generates the Call To Actions tab component & sub-app.
 */
const CallToActionsView = () => {
	const { permissions = { edit_ctas: false } } = getGlobalVars();
	const { edit_ctas: userCanEditCallToActions } = permissions;

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
			{ isEditorActive && <Editor /> }
		</div>
	);
};

export default CallToActionsView;
