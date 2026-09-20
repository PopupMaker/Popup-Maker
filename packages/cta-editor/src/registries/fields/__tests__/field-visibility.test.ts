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
				emptyType: { type: '', default: 'not-rendered' },
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

	it( 'treats null defaults as undeclared', () => {
		const fields = {
			general: {
				modern: { type: 'text', default: null },
				legacy: { type: 'select', std: null },
			},
		} as unknown as Record< string, Record< string, FieldProps > >;

		expect( getFieldDefaults( fields ) ).toEqual( {} );
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

	it.each( [
		[ 'string', '' ],
		[ 'boolean', false ],
		[ 'number', 0 ],
	] )(
		'treats null as an implicit empty %s dependency value',
		( _type, expected ) => {
			expect(
				shouldHideField(
					{
						type: 'text',
						dependencies: { controller: expected },
					} as FieldProps,
					{ controller: null },
					{}
				)
			).toBe( false );
		}
	);

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
		[ '', '' ],
		[ 'not-a-number', 'not-a-number' ],
	] )( 'normalizes the legacy number default %p', ( value, expected ) => {
		expect(
			normalizeFieldDefault( value, {
				type: 'number',
			} as FieldProps )
		).toBe( expected );
	} );

	it.each( [
		[ '0', 0 ],
		[ '12', 12 ],
		[ '1.5', 1.5 ],
		[ '', '' ],
	] )( 'normalizes the legacy range default %p', ( value, expected ) => {
		expect(
			normalizeFieldDefault( value, {
				type: 'rangeslider',
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

	it.each( [
		[ 'basic', [ 'basic' ] ],
		[ 'basic,premium', [ 'basic', 'premium' ] ],
		[ '', [] ],
	] )( 'normalizes a token select default %p', ( value, expected ) => {
		expect(
			normalizeFieldDefault( value, {
				type: 'tokenselect',
			} as FieldProps )
		).toEqual( expected );
	} );

	it.each( [ 'select', 'select2' ] )(
		'normalizes a legacy multiple %s default',
		( type ) => {
			expect(
				normalizeFieldDefault( 'basic,premium', {
					type,
					multiple: true,
				} as FieldProps )
			).toEqual( [ 'basic', 'premium' ] );
		}
	);

	it.each( [
		'color',
		'date',
		'email',
		'hidden',
		'measure',
		'password',
		'radio',
		'select',
		'select2',
		'tel',
		'text',
		'textarea',
		'url',
	] )(
		'normalizes a scalar %s default to the control string shape',
		( type ) => {
			expect(
				normalizeFieldDefault( 0, {
					type,
				} as FieldProps )
			).toBe( '0' );
		}
	);

	it( 'normalizes associative multicheck defaults to option-key strings', () => {
		expect(
			normalizeFieldDefault( [ 1, 2 ], {
				type: 'multicheck',
				options: { 1: 'One', 2: 'Two' },
			} as unknown as FieldProps )
		).toEqual( [ '1', '2' ] );
	} );

	it( 'preserves numeric multicheck defaults for numeric option arrays', () => {
		expect(
			normalizeFieldDefault( [ 1, 2 ], {
				type: 'multicheck',
				options: [
					{ value: 1, label: 'One' },
					{ value: 2, label: 'Two' },
				],
			} as FieldProps )
		).toEqual( [ 1, 2 ] );
	} );

	it.each( [ 'select', 'select2', 'multiselect', 'tokenselect' ] )(
		'normalizes %s array defaults to the control string shape',
		( type ) => {
			expect(
				normalizeFieldDefault( [ 1, 2 ], {
					type,
					multiple: true,
				} as FieldProps )
			).toEqual( [ '1', '2' ] );
		}
	);

	it.each( [ 'objectselect', 'postselect', 'taxonomyselect', 'userselect' ] )(
		'normalizes a legacy multiple %s scalar default',
		( type ) => {
			expect(
				normalizeFieldDefault( 12, {
					type,
					multiple: true,
				} as FieldProps )
			).toEqual( [ 12 ] );
		}
	);

	it.each( [ 'objectselect', 'postselect', 'taxonomyselect', 'userselect' ] )(
		'normalizes a legacy multiple %s array to numeric IDs',
		( type ) => {
			expect(
				normalizeFieldDefault( [ '12', '34' ], {
					type,
					multiple: true,
				} as FieldProps )
			).toEqual( [ 12, 34 ] );
		}
	);

	it.each( [ 'objectselect', 'postselect', 'taxonomyselect', 'userselect' ] )(
		'keeps an absent multiple %s default empty',
		( type ) => {
			expect(
				normalizeFieldDefault( undefined, {
					type,
					multiple: true,
				} as FieldProps )
			).toEqual( [] );
		}
	);

	it.each( [
		[ 12, [ '12' ] ],
		[
			[ 12, 34 ],
			[ '12', '34' ],
		],
		[ '', [] ],
	] )(
		'normalizes a multiple custom select default %p',
		( value, expected ) => {
			expect(
				normalizeFieldDefault( value, {
					type: 'customselect',
					multiple: true,
				} as FieldProps )
			).toEqual( expected );
		}
	);
} );
