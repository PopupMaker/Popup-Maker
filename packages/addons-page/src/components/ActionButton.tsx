import React from 'react';
import clsx from 'clsx';
import { Button } from '@wordpress/components';

import { getActionDescriptor } from '../lib/status';

import type { Addon, AddonAction, AddonsPageConfig } from '../types';

interface Props {
	addon: Addon;
	busy: boolean;
	config: AddonsPageConfig;
	large?: boolean;
	onAction: ( addon: Addon, action: AddonAction ) => void;
}

const ActionButton = ( { addon, busy, config, large, onAction }: Props ) => {
	const { action, disabled, label } = getActionDescriptor(
		addon,
		busy,
		config
	);

	if ( ! action && ! disabled ) {
		return null;
	}

	const className = clsx( 'pum-addons__action', {
		'pum-addons__action--lg': large,
		'pum-addons__action--upgrade': 'upgrade' === action,
		'pum-addons__action--pro-plus': 'upgrade' === action && addon.isProPlus,
	} );

	if ( 'upgrade' === action ) {
		return (
			<Button
				variant="primary"
				className={ className }
				href={
					addon.isProPlus
						? config.proPlusUpgradeUrl
						: config.proUpgradeUrl
				}
				target="_blank"
				rel="noopener noreferrer"
			>
				{ label }
			</Button>
		);
	}

	// Only Activate is a filled lifecycle action; Deactivate is outlined.
	const isPrimary = 'activate' === action;

	return (
		<Button
			variant={ isPrimary ? 'primary' : 'secondary' }
			className={ className }
			disabled={ disabled }
			isBusy={ busy }
			onClick={ ( event: React.MouseEvent ) => {
				event.stopPropagation();

				if ( action ) {
					onAction( addon, action );
				}
			} }
		>
			{ label }
		</Button>
	);
};

export default ActionButton;
