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

/**
 * Set UUID for any CTA block that is missing it.
 *
 * @param blocks
 */
// addFilter(
// 	'blocks.getBlockAttributes',
// 	'pum/ctas',
// 	( blockAttributes, blockType, innerHTML, attributes ) => {
// 		if ( name === blockType.name ) {
// 			if ( blockAttributes.pid === undefined || ! blockAttributes.pid ) {
// 				blockAttributes.pid = wp.data
// 					.select( 'core/editor' )
// 					.getCurrentPostId();
// 			}

// 			if (
// 				blockAttributes.uuid === undefined ||
// 				! blockAttributes.uuid
// 			) {
// 				blockAttributes.uuid = nanoid();
// 			}
// 			console.log( {
// 				blockAttributes,
// 				blockType,
// 				innerHTML,
// 				attributes,
// 			} );
// 		}
// 		return blockAttributes;
// 	}
// );

/**
 * Index of CTA blocks & UUIDs.
 */
const ctaBlocks = {
	uuidByClientId: {},
	clientIdByUuid: {},
};

/**
 * Process blocks looking for CTAs to validate.
 *
 * @param {Object|Array} blocks List of blocks.
 */
const processBlocks = ( blocks ) => {
	blocks.forEach( ( block ) => {
		if ( [ 'core/column', 'core/columns' ].indexOf( block.name ) >= 0 ) {
			processBlocks( block.innerBlocks );
		} else if ( name === block.name ) {
			let uuid = block.attributes.uuid || false;
			let newUuid = false;

			// if has no uuid, generate it.
			if ( ! uuid ) {
				uuid = newUuid = nanoid();
			}

			// if uuid is duplicate, replace it on most recent* block.
			if (
				undefined !== ctaBlocks.clientIdByUuid[ uuid ] &&
				block.clientId !== ctaBlocks.clientIdByUuid[ uuid ]
			) {
				uuid = newUuid = nanoid();
				ctaBlocks.uuidByClientId[ block.clientId ] = uuid;
				ctaBlocks.clientIdByUuid[ uuid ] = block.clientId;
			}

			ctaBlocks.uuidByClientId[ block.clientId ] = uuid;
			ctaBlocks.clientIdByUuid[ uuid ] = block.clientId;

			// if uuid is duplicate, replace it on most recent* block.
			if (
				block.clientId !== ctaBlocks.clientIdByUuid[ uuid ] ||
				uuid !== ctaBlocks.uuidByClientId[ block.clientId ]
			) {
				uuid = newUuid = nanoid();
				ctaBlocks.uuidByClientId[ block.clientId ] = uuid;
				ctaBlocks.clientIdByUuid[ uuid ] = block.clientId;
			}

			// If new uuid was generated, update the block.
			if ( newUuid ) {
				// Update attributes of another block
				// wp.data.dispatch( 'core/editor' ).updateBlockAttributes( clientID, attributes )
				wp.data
					.dispatch( 'core/editor' )
					.updateBlockAttributes( block.clientId, {
						uuid: newUuid,
					} );
			}
		}
	} );
};

setTimeout( () => {
	let blockState = wp.data.select( 'core/block-editor' ).getBlocks();

	// Process blocks initially.
	processBlocks( blockState );

	wp.data.subscribe(
		debounce( () => {
			const newBlocksState = wp.data
				.select( 'core/block-editor' )
				.getBlocks();
			if (
				blockState.length !== newBlocksState.length ||
				blockState !== newBlocksState
			) {
				processBlocks( newBlocksState );
			}
			// Update reference.
			blockState = newBlocksState;
		}, 300 )
	);
}, 1000 );
