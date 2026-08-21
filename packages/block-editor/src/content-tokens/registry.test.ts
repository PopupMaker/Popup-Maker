import {
	contentTokenGroups,
	contentTokens,
	getContentTokenPreview,
	isContentTokenAvailable,
	registerContentToken,
	registerContentTokenGroup,
} from './registry';
import type { ContentTokenContext, ContentTokenDefinition } from './types';

const context: ContentTokenContext = {
	surface: 'block-editor-rich-text',
	postType: 'post',
	blockName: 'core/paragraph',
};

const token = ( overrides: Partial< ContentTokenDefinition > = {} ) => ( {
	id: 'example.headline',
	label: 'Headline',
	...overrides,
} );

describe( 'content token registry', () => {
	beforeEach( () => {
		contentTokens.clear();
		contentTokenGroups.clear();
	} );

	it( 'groups and sorts human-readable catalogs', () => {
		registerContentTokenGroup( {
			id: 'lead-magnet',
			label: 'Lead Magnet',
			priority: 20,
		} );
		registerContentTokenGroup( {
			id: 'visitor',
			label: 'Visitor',
			priority: 10,
		} );
		registerContentToken( token( { group: 'lead-magnet' } ) );
		registerContentToken(
			token( { id: 'visitor.name', label: 'Name', group: 'visitor' } )
		);

		expect( contentTokens.getItems().map( ( item ) => item.id ) ).toEqual( [
			'visitor.name',
			'example.headline',
		] );
	} );

	it( 'applies surface, post, block, attribute, and exclusion conditions', () => {
		const definition = token( {
			conditions: {
				surfaces: [ 'block-editor-rich-text' ],
				postTypes: [ 'post' ],
				blockTypes: [ 'core/paragraph' ],
				attributes: [ 'content' ],
				excludedBlockTypes: [ 'core/code' ],
			},
		} );

		expect(
			isContentTokenAvailable( definition, {
				...context,
				attributeName: 'content',
			} )
		).toBe( true );
		expect( isContentTokenAvailable( definition, context ) ).toBe( false );
		expect(
			isContentTokenAvailable( definition, {
				...context,
				blockName: 'core/code',
				attributeName: 'content',
			} )
		).toBe( false );
	} );

	it( 'fails closed when a runtime availability check throws', () => {
		expect(
			isContentTokenAvailable(
				token( {
					isAvailable: () => {
						throw new Error( 'Unavailable' );
					},
				} ),
				context
			)
		).toBe( false );
	} );

	it( 'uses contextual preview text with the readable label as fallback', () => {
		expect(
			getContentTokenPreview(
				token( { preview: ( editor ) => editor.postType } ),
				context
			)
		).toBe( 'post' );
		expect(
			getContentTokenPreview(
				token( { preview: () => undefined } ),
				context
			)
		).toBe( 'Headline' );
	} );
} );
