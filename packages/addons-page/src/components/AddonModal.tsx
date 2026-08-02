import React from 'react';
import { Icon, Modal } from '@wordpress/components';
import { check } from '@wordpress/icons';
import { __ } from '@popup-maker/i18n';

import { getStatusKey } from '../lib/status';
import ActionButton from './ActionButton';
import AddonIcon from './AddonIcon';
import StatusIndicator from './StatusIndicator';

import type { Addon, AddonAction, AddonsPageConfig } from '../types';

interface Props {
	addon: Addon;
	busy: boolean;
	config: AddonsPageConfig;
	onAction: ( addon: Addon, action: AddonAction ) => void;
	onClose: () => void;
}

const AddonModal = ( { addon, busy, config, onAction, onClose }: Props ) => {
	const status = getStatusKey( addon, busy );

	const meta = addon.isProPlus
		? __( 'Pro+', 'popup-maker' )
		: __( 'Pro', 'popup-maker' );

	return (
		<Modal
			title={ addon.name || addon.slug }
			className="pum-addons__modal"
			overlayClassName="pum-addons__modal-overlay"
			onRequestClose={ onClose }
		>
			<div className="pum-addons__modal-lede">
				<AddonIcon addon={ addon } />
				<p className="pum-addons__modal-meta">{ meta }</p>
			</div>
			<p className="pum-addons__modal-copy">
				{ addon.longDescription || addon.description }
			</p>
			{ addon.features.length > 0 && (
				<ul className="pum-addons__features">
					{ addon.features.map( ( feature ) => (
						<li key={ feature }>
							<Icon icon={ check } size={ 18 } />
							<span>{ feature }</span>
						</li>
					) ) }
				</ul>
			) }
			<div className="pum-addons__modal-footer">
				<StatusIndicator
					status={ status }
					proPlus={ addon.isProPlus }
				/>
				<ActionButton
					addon={ addon }
					busy={ busy }
					config={ config }
					large
					onAction={ onAction }
				/>
			</div>
		</Modal>
	);
};

export default AddonModal;
