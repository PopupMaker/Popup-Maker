import './editor.scss';

import { __ } from '@popup-maker/i18n';

import Header from './header';
import List from '../list';
import Notices from './notices';
import { getGlobalVars } from '../../utils';
import {
	Editor as BaseEditor,
	withModal,
	withQueryParams,
} from '@popup-maker/cta-editor';

/**
 * Generates the Call To Actions tab component & sub-app.
 */
const CallToActionsView = () => {
	const { permissions = { edit_ctas: false } } = getGlobalVars();
	const { edit_ctas: userCanEditCallToActions } = permissions;

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

	const Editor = withQueryParams( withModal( BaseEditor ) );

	return (
		<div className="call-to-action-list">
			<Notices />
			<Header />
			<List />
			<Editor />
		</div>
	);
};

export default CallToActionsView;
