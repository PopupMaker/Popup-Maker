import React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
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

interface IconDisplayProps {
	size?: number;
	color?: string;
}

const IconGrid: React.FC<
	{ icon: JSX.Element; name: string } & IconDisplayProps
> = ( { icon, name, size = 40, color = 'var(--wp-admin-theme-color)' } ) => (
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
		<div
			style={ {
				width: size,
				height: size,
				color: color,
			} }
		>
			{ React.cloneElement( icon, {
				style: {
					width: '100%',
					height: '100%',
				},
				stroke: 'currentColor',
				fill: 'currentColor',
				className: `pum-icon ${ icon.props.className || '' }`,
			} ) }
		</div>
		<div style={ { marginTop: '10px', fontSize: '14px' } }>{ name }</div>
		<div style={ { marginTop: '5px', fontSize: '12px', color: '#666' } }>
			{ `<${ name } />` }
		</div>
	</div>
);

const IconDisplay: React.FC< IconDisplayProps > = ( {
	size = 40,
	color = 'var(--wp-admin-theme-color)',
} ) => {
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
			{ Object.entries( icons ).map( ( [ name, icon ] ) => (
				<IconGrid
					key={ name }
					icon={ icon }
					name={ name }
					size={ size }
					color={ color }
				/>
			) ) }
		</div>
	);
};

const meta = {
	title: 'Popup Maker/Icons',
	component: IconDisplay,
	parameters: {
		layout: 'fullscreen',
	},
	argTypes: {
		size: {
			control: { type: 'range', min: 16, max: 128, step: 8 },
			description: 'Size of the icon in pixels',
		},
		color: {
			control: { type: 'color' },
			description: 'Color of the icon',
		},
	},
} satisfies Meta< typeof IconDisplay >;

export default meta;
type Story = StoryObj< typeof IconDisplay >;

export const AllIcons: Story = {
	args: {
		size: 40,
		color: 'var(--wp-admin-theme-color)',
	},
};
