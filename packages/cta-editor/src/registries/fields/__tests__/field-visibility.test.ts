import { describe, expect, it } from '@jest/globals';

import { getFieldDefaults, shouldHideField } from '../field-visibility';

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
} );
