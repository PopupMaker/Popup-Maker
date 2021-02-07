/**
 * NPM dependencies
 */
import { customAlphabet } from 'nanoid';
const nanoid = customAlphabet( '1234567890abcdef', 10 );

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter, applyFilters } from '@wordpress/hooks';

/**
 * Popup Maker Dependencies
 */
import LogoIcon from '../../icons/logo';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import save from './save';

const { debounce } = _;

metadata.attributes.text.default = __( 'Learn More!', 'popup-maker' );

const { name, category, parent, attributes, supports } = metadata;

export { metadata, name };

export const settings = {
	name,
	title: __( 'CTA Button', 'popup-maker' ),
	description: __(
		'Insert a call to action to let users convert to a specific action.',
		'popup-maker'
	),
	category,
	//  parent,
	icon: LogoIcon,
	attributes,
	supports,
	keywords: applyFilters( 'pum/cta-block/keywords', [
		__( 'link' ),
		__( 'cta', 'popup-maker' ),
		__( 'button', 'popup-maker' ),
		__( 'call to action', 'popup-maker' ),
	] ),
	example: {
		attributes: {
			className: 'is-style-fill',
			backgroundColor: '#8eae1b',
			textColor: '#ffffff',
			text: __( 'Call to Action', 'popup-maker' ),
		},
	},
	styles: [
		{ name: 'fill', label: __( 'Fill' ), isDefault: true },
		{ name: 'outline', label: __( 'Outline' ) },
		{
			name: 'text-only',
			label: __( 'Text Only', 'popup-maker' ),
			textColor: '#333333',
		},
	],
	// variations: applyFilters( 'pum/cta-block/variations', [
	// 	{
	// 		name: 'blue',
	// 		title: __( 'Blue Quote' ),
	// 		isDefault: true,
	// 		attributes: { className: 'is-style-blue-quote' },
	// 	},
	// ] ),
	edit,
	save,
	merge: ( a, { text = '' } ) => ( {
		...a,
		text: ( a.text || '' ) + text,
	} ),
};

