import React from 'react';
import { action } from '@storybook/addon-actions';

import type { Meta, StoryObj } from '@storybook/react';

import * as Icons from '../lib/index';

import '../lib/editor.scss';

interface IconProps {
	name: string;
	icon: JSX.Element;
	size?: number;
	color?: string;
}

interface IconGridProps {
	size?: number;
	color?: string;
}

const Icon: React.FC< IconProps > = ( { icon, size, color } ) => (
	<div
		style={ {
			width: size,
			height: size,
			aspectRatio: '1 / 1',
			color,
		} }
	>
		{ icon }
	</div>
);

const GridIcon: React.FC< IconProps > = ( { icon, name, size, color } ) => (
	<>
		<Icon name={ name } icon={ icon } size={ size } color={ color } />
		<div
			style={ {
				marginTop: '10px',
				fontSize: '14px',
				textAlign: 'center',
			} }
		>
			{ name }
		</div>
	</>
);

const IconGrid: React.FC< IconGridProps > = ( { size, color } ) => {
	const [ copiedIcon, setCopiedIcon ] = React.useState< string | null >(
		null
	);

	return (
		<div
			style={ {
				display: 'grid',
				gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))',
				gap: '10px',
				padding: '0',
			} }
		>
			{ Object.entries( Icons ).map( ( [ name, icon ] ) => (
				<button
					key={ name }
					style={ {
						display: 'flex',
						justifyContent: 'center',
						flexDirection: 'column',
						alignItems: 'center',
						border: '1px solid #eee',
						backgroundColor:
							copiedIcon === name ? '#e6ffe6' : '#f5f5f5',
						borderRadius: '8px',
						aspectRatio: '1 / 1',
						padding: '10px',
						cursor: 'pointer',
						transition: 'all 0.2s ease',
						position: 'relative',
					} }
					onClick={ () => {
						navigator.clipboard.writeText( `<${ name } />` );
						action( 'Copied to clipboard' )( `<${ name } />` );
						setCopiedIcon( name );
						setTimeout( () => setCopiedIcon( null ), 1000 );
					} }
				>
					<GridIcon
						icon={ icon }
						name={ name }
						size={ size }
						color={ color }
					/>
					{ copiedIcon === name && (
						<div
							style={ {
								position: 'absolute',
								bottom: '5px',
								fontSize: '12px',
								color: '#4CAF50',
							} }
						>
							Copied!
						</div>
					) }
				</button>
			) ) }
		</div>
	);
};

const meta: Meta = {
	title: 'Popup Maker/Icons',
	component: IconGrid,
	parameters: {},
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
};

export default meta;

export const AllIcons: StoryObj< typeof IconGrid > = {
	args: {
		size: 40,
		color: '#000000',
	},
	parameters: {
		docs: {
			source: {
				code: `import { Block } from '@popup-maker/icons';\n\n<Block />`,
			},
		},
	},
};
