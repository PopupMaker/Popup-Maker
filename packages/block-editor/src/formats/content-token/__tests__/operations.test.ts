import type { RichTextValue } from '@wordpress/rich-text';
import {
	applyContentToken,
	ATOMIC_FORMAT,
	EDITABLE_FORMAT,
	getContentTokenSelection,
	removeContentToken,
	unlinkContentToken,
} from '../operations';

const valueWithEditableToken = (
	formatType = EDITABLE_FORMAT
): RichTextValue => {
	const text = 'Before Token after';
	const format = {
		type: formatType,
		attributes: { valueId: 'lead-magnet.title' },
	};
	const formats = Array.from( { length: text.length }, () => [] );
	for ( let index = 7; index < 12; index++ ) {
		formats[ index ] = [ format ];
	}

	return {
		text,
		formats,
		replacements: [],
		start: 9,
		end: 9,
	} as unknown as RichTextValue;
};

const valueWithAtomicToken = (): RichTextValue =>
	( {
		text: 'Before \ufffc after',
		formats: [],
		replacements: [
			,
			,
			,
			,
			,
			,
			,
			{
				type: ATOMIC_FORMAT,
				attributes: { valueId: 'lead-magnet.title' },
				innerHTML: 'Token preview',
			},
		],
		start: 7,
		end: 8,
	} ) as unknown as RichTextValue;

describe( 'content token RichText operations', () => {
	it( 'finds the complete editable token around a collapsed caret', () => {
		expect( getContentTokenSelection( valueWithEditableToken() ) ).toEqual(
			{
				kind: 'editable',
				formatType: EDITABLE_FORMAT,
				valueId: 'lead-magnet.title',
				start: 7,
				end: 12,
				text: 'Token',
			}
		);
	} );

	it( 'keeps selected author text when applying an editable token', () => {
		const value = {
			text: 'Custom label',
			formats: [],
			replacements: [],
			start: 0,
			end: 12,
		} as unknown as RichTextValue;
		const changed = applyContentToken(
			value,
			'lead-magnet.description',
			'editable',
			'Default preview'
		);

		expect( changed.text ).toBe( 'Custom label' );
		expect(
			getContentTokenSelection( { ...changed, start: 4, end: 4 } )
		).toMatchObject( {
			valueId: 'lead-magnet.description',
			text: 'Custom label',
		} );
	} );

	it( 'inserts atomic tokens with the canonical format', () => {
		const value = {
			text: 'Before  after',
			formats: [],
			replacements: [],
			start: 7,
			end: 7,
		} as unknown as RichTextValue;
		const changed = applyContentToken(
			value,
			'lead-magnet.asset-id',
			'atomic',
			'Asset 12'
		);

		expect( changed.replacements.find( Boolean ) ).toMatchObject( {
			type: ATOMIC_FORMAT,
			attributes: { valueId: 'lead-magnet.asset-id' },
			innerHTML: 'Asset 12',
		} );
	} );

	it( 'changes an editable token source without replacing its text', () => {
		const value = valueWithEditableToken();
		const changed = applyContentToken(
			value,
			'lead-magnet.description',
			'editable',
			'Description',
			getContentTokenSelection( value )
		);

		expect( changed.text ).toBe( value.text );
		expect(
			getContentTokenSelection( { ...changed, start: 9, end: 9 } )
		).toMatchObject( {
			valueId: 'lead-magnet.description',
			text: 'Token',
		} );
	} );

	it( 'does not absorb an adjacent token with a different value id', () => {
		const value = valueWithEditableToken();
		const next = {
			type: EDITABLE_FORMAT,
			attributes: { valueId: 'lead-magnet.description' },
		};
		for ( let index = 12; index < 17; index++ ) {
			value.formats[ index ] = [ next ];
		}
		value.start = 12;
		value.end = 12;

		expect( getContentTokenSelection( value ) ).toMatchObject( {
			valueId: 'lead-magnet.description',
			start: 12,
			end: 17,
		} );
	} );

	it( 'converts an old atomic token to editable text', () => {
		const value = valueWithAtomicToken();
		const changed = applyContentToken(
			value,
			'lead-magnet.title',
			'editable',
			'Fallback',
			getContentTokenSelection( value )
		);

		expect( changed.text ).toBe( 'Before Token preview after' );
		expect(
			getContentTokenSelection( { ...changed, start: 10, end: 10 } )
		).toMatchObject( { kind: 'editable', text: 'Token preview' } );
	} );

	it( 'unlinks while preserving text and removes the full token on demand', () => {
		const value = valueWithEditableToken();
		const selection = getContentTokenSelection( value );
		expect( selection ).not.toBeNull();
		if ( ! selection ) {
			return;
		}

		const unlinked = unlinkContentToken( value, selection, 'Fallback' );
		expect( unlinked.text ).toBe( value.text );
		expect(
			getContentTokenSelection( { ...unlinked, start: 9, end: 9 } )
		).toBeNull();

		const removed = removeContentToken( value, selection );
		expect( removed.text ).toBe( 'Before  after' );
	} );

	it( 'removes an atomic token without leaving its preview behind', () => {
		const value = valueWithAtomicToken();
		const selection = getContentTokenSelection( value );
		expect( selection ).not.toBeNull();
		if ( ! selection ) {
			return;
		}

		expect( removeContentToken( value, selection ).text ).toBe(
			'Before  after'
		);
	} );
} );
