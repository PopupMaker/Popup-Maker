import { describe, expect, it } from '@jest/globals';

import {
	getFieldDefaults,
	getMissingFieldDefaults,
	normalizeFieldDefault,
	shouldHideField,
} from '../field-visibility';

import type { FieldProps } from '@popup-maker/fields';
import type { CallToAction } from '@popup-maker/core-data';

const dependentField = {
	type: 'text',
	dependencies: { discountSource: 'existing' },
} as FieldProps;

describe( 'custom CTA field visibility', () => {
	it( 'uses a controlling field default before settings are persisted', () => {
		const fields = {
			general: {
				discountSource: {
					type: 'select',
					default: 'existing',
				},
				discountId: dependentField,
			},
		} as Record< string, Record< string, FieldProps > >;

		expect(
			shouldHideField(
				dependentField,
				{} as CallToAction[ 'settings' ],
				getFieldDefaults( fields )
			)
		).toBe( false );
	} );

	it( 'uses a legacy PHP field std value before settings are persisted', () => {
		const fields = {
			general: {
				discountSource: {
					type: 'select',
					std: 'existing',
				},
				discountId: dependentField,
			},
		} as unknown as Record< string, Record< string, FieldProps > >;

		expect(
			shouldHideField(
				dependentField,
				{} as CallToAction[ 'settings' ],
				getFieldDefaults( fields )
			)
		).toBe( false );
	} );

	it( 'prefers a persisted setting over the declared default', () => {
		expect(
			shouldHideField(
				dependentField,
				{ discountSource: 'generated' },
				{ discountSource: 'existing' }
			)
		).toBe( true );
	} );

	it( 'ignores invalid placeholder entries when collecting defaults', () => {
		const fields = {
			general: {
				missing: null,
				disabled: false,
				discountSource: {
					type: 'select',
					default: 'existing',
				},
			},
		};

		expect( getFieldDefaults( fields ) ).toEqual( {
			discountSource: 'existing',
		} );
	} );

	it( 'returns every declared default missing from editable settings', () => {
		expect(
			getMissingFieldDefaults(
				{ type: 'link', existingValue: 'saved' },
				{
					existingValue: 'default',
					discountSource: 'existing',
					allowStacking: false,
				}
			)
		).toEqual( {
			discountSource: 'existing',
			allowStacking: false,
		} );
	} );

	it( 'treats persisted null values as missing defaults', () => {
		expect(
			getMissingFieldDefaults(
				{ discountSource: null },
				{ discountSource: 'existing' }
			)
		).toEqual( { discountSource: 'existing' } );
		expect(
			shouldHideField(
				dependentField,
				{ discountSource: null },
				{ discountSource: 'existing' }
			)
		).toBe( false );
	} );

	it.each( [ '0', 'false', 'no', 0, false ] )(
		'normalizes the legacy false checkbox default %p',
		( value ) => {
			expect(
				normalizeFieldDefault( value, {
					type: 'checkbox',
				} as FieldProps )
			).toBe( false );
		}
	);

	it.each( [ '1', 'yes', 'true', 1, true ] )(
		'normalizes the legacy true checkbox default %p',
		( value ) => {
			expect(
				normalizeFieldDefault( value, {
					type: 'checkbox',
				} as FieldProps )
			).toBe( true );
		}
	);

	it( 'collects normalized checkbox defaults for dependency checks', () => {
		const fields = {
			general: {
				allowStacking: {
					type: 'checkbox',
					std: '0',
				},
			},
		} as unknown as Record< string, Record< string, FieldProps > >;

		expect( getFieldDefaults( fields ) ).toEqual( {
			allowStacking: false,
		} );
	} );

	it.each( [
		[ '0', 0 ],
		[ '12', 12 ],
		[ '1.5', 1.5 ],
	] )( 'normalizes the legacy number default %p', ( value, expected ) => {
		expect(
			normalizeFieldDefault( value, {
				type: 'number',
			} as FieldProps )
		).toBe( expected );
	} );

	it.each( [
		[ 'basic,premium', [ 'basic', 'premium' ] ],
		[ '', [] ],
	] )(
		'normalizes the legacy multi-option default %p',
		( value, expected ) => {
			expect(
				normalizeFieldDefault( value, {
					type: 'multicheck',
				} as FieldProps )
			).toEqual( expected );
			expect(
				normalizeFieldDefault( value, {
					type: 'multiselect',
				} as FieldProps )
			).toEqual( expected );
		}
	);
} );
