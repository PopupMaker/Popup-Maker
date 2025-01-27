import './editor.scss';
import './style.scss';

/**
 * WordPress dependencies
 */
import { __ } from '@popup-maker/i18n';
import { buttons as icon } from '@wordpress/icons';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import initBlock from '../utils/init-block';
import deprecated from './deprecated';
import transforms from './transforms';
import edit from './edit';
import metadata from './block.json';
import save from './save';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	example: {
		attributes: {
			layout: {
				type: 'flex',
				justifyContent: 'center',
			},
		},
		innerBlocks: [
			{
				name: 'popup-maker/cta-button',
				attributes: { text: __( 'Buy now' ) },
			},
			{
				name: 'popup-maker/cta-button',
				attributes: { text: __( 'Contact us' ) },
			},
		],
	},
	deprecated,
	transforms,
	edit,
	save,
};

export const init = () => initBlock( { name, metadata, settings } );

// Filter core/buttons to allow transforming to cta-buttons.
addFilter(
	'blocks.registerBlockType',
	'popup-maker/cta-buttons',
	( blockSettings ) => {
		const { name: blockName } = blockSettings;

		switch ( blockName ) {
			case 'core/button':
				return {
					...blockSettings,
					parent: [
						...blockSettings.parent,
						'popup-maker/cta-buttons',
					],
				};
			case 'core/buttons':
				return {
					...blockSettings,
					allowedBlocks: [
						...blockSettings.allowedBlocks,
						'popup-maker/cta-button',
					],
				};
			default:
				return blockSettings;
		}
	}
);
