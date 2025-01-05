import './editor.scss';
import './style.scss';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
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
