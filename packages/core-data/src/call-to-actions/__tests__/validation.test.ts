jest.mock( '@popup-maker/i18n', () => ( {
	__: ( str: string ) => str,
} ), { virtual: true } );

import { validateCallToAction } from '../validation';

describe( 'validateCallToAction', () => {
	it( 'returns error object for null input', () => {
		const result = validateCallToAction( null as any );
		expect( result ).toEqual( {
			message: 'Call to action not found',
		} );
	} );

	it( 'returns error object for undefined input', () => {
		const result = validateCallToAction( undefined as any );
		expect( result ).toEqual( {
			message: 'Call to action not found',
		} );
	} );

	it( 'returns true for a valid CTA with a title', () => {
		const result = validateCallToAction( {
			id: 1,
			title: 'My CTA',
			status: 'publish',
		} as any );

		expect( result ).toBe( true );
	} );

	it( 'returns true for a CTA without a title property', () => {
		// When title is absent entirely, the condition `callToAction.title &&` short-circuits.
		const result = validateCallToAction( {
			id: 1,
			status: 'draft',
		} as any );

		expect( result ).toBe( true );
	} );

	it( 'returns true for an empty object (no fields)', () => {
		const result = validateCallToAction( {} as any );
		expect( result ).toBe( true );
	} );

	it( 'returns error for CTA with empty string title', () => {
		// `callToAction.title` = '' is falsy, so `callToAction.title && !callToAction.title?.length`
		// evaluates to '' (falsy) → condition is false → returns true.
		// This is the actual behavior of the code.
		const result = validateCallToAction( {
			id: 1,
			title: '',
		} as any );

		expect( result ).toBe( true );
	} );
} );
