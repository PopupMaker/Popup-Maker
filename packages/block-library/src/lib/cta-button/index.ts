import './editor.scss';
import './style.scss';

/**
 * WordPress dependencies
 */
import { __ } from '@popup-maker/i18n';
import { button as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import initBlock from '../utils/init-block';
import deprecated from './deprecated';
import edit from './edit';
import metadata from './block.json';
import save from './save';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	example: {
		attributes: {
			className: 'is-style-fill',
			text: __( 'Call to Action', 'popup-maker' ),
		},
	},
	edit,
	save,
	deprecated,
	merge: ( a: { text: string }, { text = '' }: { text: string } ) => ( {
		...a,
		text: ( a.text || '' ) + text,
	} ),
};

export const init = () => initBlock( { name, metadata, settings } );
