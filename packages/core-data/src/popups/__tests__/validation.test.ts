jest.mock( '@popup-maker/i18n', () => ( {
	__: ( str: string ) => str,
} ), { virtual: true } );

import { validatePopup } from '../validation';

describe( 'validatePopup', () => {
	it( 'returns error when popup is null/undefined', () => {
		const result = validatePopup( null as any );
		expect( result ).toEqual( {
			message: 'Popup not found',
		} );
	} );

	// BUG: Empty string '' is falsy, so `popup.title && ...` short-circuits
	// to false and the validation passes. Empty titles are never caught.
	// See BUGS-FOUND-BY-TESTS.md #4.
	it( 'BUG: does NOT catch empty string title', () => {
		const result = validatePopup( { title: '' } as any );
		// Should return an error object, but returns true due to the bug.
		expect( result ).toBe( true );
	} );

	it( 'returns error when publish status and no conditions', () => {
		const result = validatePopup( {
			status: 'publish',
			settings: {
				conditions: { logicalOperator: 'or', items: [] },
			},
		} as any );

		expect( result ).toEqual( {
			message:
				'Please provide at least one condition for this popup before enabling it.',
			tabName: 'content',
		} );
	} );

	it( 'returns true for valid popup with conditions and publish status', () => {
		const result = validatePopup( {
			title: 'My Popup',
			status: 'publish',
			settings: {
				conditions: {
					logicalOperator: 'or',
					items: [
						{
							id: '1',
							type: 'rule',
							name: 'is_front_page',
						},
					],
				},
			},
		} as any );

		expect( result ).toBe( true );
	} );

	it( 'returns true for draft popup without conditions', () => {
		const result = validatePopup( {
			title: 'Draft Popup',
			status: 'draft',
			settings: {
				conditions: { logicalOperator: 'or', items: [] },
			},
		} as any );

		expect( result ).toBe( true );
	} );

	it( 'returns true when title is provided and has length', () => {
		const result = validatePopup( {
			title: 'Valid Title',
		} as any );

		expect( result ).toBe( true );
	} );

	it( 'returns true when no title property is provided', () => {
		const result = validatePopup( {
			status: 'draft',
		} as any );

		expect( result ).toBe( true );
	} );

	it( 'returns condition error when publish with no settings', () => {
		// When settings is undefined, the optional chain evaluates to
		// undefined, !undefined is true, so the conditions check triggers.
		const result = validatePopup( {
			title: 'Test',
			status: 'publish',
		} as any );

		expect( result ).toEqual( {
			message: 'Please provide at least one condition for this popup before enabling it.',
			tabName: 'content',
		} );
	} );
} );
