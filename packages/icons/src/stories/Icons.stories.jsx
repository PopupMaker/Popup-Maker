import React from 'react';
import BlockManager from '../lib/block-manager';
import Block from '../lib/block';
import CheckAll from '../lib/check-all';
import CustomRedirect from '../lib/custom-redirect';
import FilterLines from '../lib/filter-lines';
import Incognito from '../lib/incognito';
import LicenseKey from '../lib/license-key';
import LockedUser from '../lib/locked-user';
import Monitor from '../lib/monitor';
import Permissions from '../lib/permissions';
import ProtectedMessage from '../lib/protected-message';
import Upgrade from '../lib/upgrade';

const IconGrid = ( { icon: Icon, name } ) => (
	<div
		style={ {
			padding: '20px',
			display: 'flex',
			flexDirection: 'column',
			alignItems: 'center',
			border: '1px solid #eee',
			borderRadius: '8px',
			margin: '10px',
		} }
	>
		<div style={ { width: '48px', height: '48px' } }>{ Icon }</div>
		<div style={ { marginTop: '10px', fontSize: '14px' } }>{ name }</div>
	</div>
);

const IconDisplay = () => {
	const icons = {
		BlockManager,
		Block,
		CheckAll,
		CustomRedirect,
		FilterLines,
		Incognito,
		LicenseKey,
		LockedUser,
		Monitor,
		Permissions,
		ProtectedMessage,
		Upgrade,
	};

	return (
		<div
			style={ {
				display: 'grid',
				gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))',
				gap: '20px',
				padding: '20px',
			} }
		>
			{ Object.entries( icons ).map( ( [ name, Icon ] ) => (
				<IconGrid key={ name } icon={ Icon } name={ name } />
			) ) }
		</div>
	);
};

// Storybook configuration
export default {
	title: 'Popup Maker/Icons',
	component: IconDisplay,
	parameters: {
		layout: 'fullscreen',
	},
};

export const AllIcons = {
	render: () => <IconDisplay />,
};
