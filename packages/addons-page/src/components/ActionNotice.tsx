import React, { useEffect } from 'react';
import { Button, Modal, Snackbar } from '@wordpress/components';
import { __ } from '@popup-maker/i18n';

import type { AddonsNotice } from '../hooks/useAddons';
import type { AddonsPageConfig } from '../types';

interface Props {
	notice: AddonsNotice;
	config: AddonsPageConfig;
	onDismiss: () => void;
}

const SUCCESS_TIMEOUT = 4000;

/**
 * Action feedback: successes are transient snackbars; failures interrupt
 * with a modal carrying the technical detail and recovery links.
 *
 * @param props Component properties.
 */
const ActionNotice = ( props: Props ) => {
	const { notice, config, onDismiss } = props;
	const isSuccess = 'success' === notice.type;

	useEffect( () => {
		if ( ! isSuccess ) {
			return;
		}

		const timer = window.setTimeout( onDismiss, SUCCESS_TIMEOUT );

		return () => window.clearTimeout( timer );
	}, [ isSuccess, onDismiss ] );

	if ( isSuccess ) {
		return (
			<div className="pum-addons__snackbar">
				<Snackbar onRemove={ onDismiss }>{ notice.message }</Snackbar>
			</div>
		);
	}

	return (
		<Modal
			title={
				notice.title ?? __( 'The add-on action failed', 'popup-maker' )
			}
			className="pum-addons__modal pum-addons__error-modal"
			overlayClassName="pum-addons__modal-overlay"
			onRequestClose={ onDismiss }
		>
			<div
				className="pum-addons__modal-copy"
				dangerouslySetInnerHTML={ { __html: notice.message } }
			/>
			{ notice.details && (
				<code className="pum-addons__error-details">
					{ notice.details }
				</code>
			) }
			<div className="pum-addons__modal-footer pum-addons__error-modal-footer">
				<Button variant="tertiary" href={ config.pluginsUrl }>
					{ __( 'Manage plugins', 'popup-maker' ) }
				</Button>
				<Button
					variant="primary"
					className="pum-addons__action"
					href={ config.supportUrl }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Get support', 'popup-maker' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default ActionNotice;
