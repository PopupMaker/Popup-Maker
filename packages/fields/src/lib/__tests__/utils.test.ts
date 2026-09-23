import { parseOldArgsToProps } from '../utils';
import { parseNumberControlValue } from '../number';

import type { OldCustomSelectField } from '../../types/old-field';

describe( 'legacy field conversion', () => {
	it( 'preserves multiple selection for custom selects', () => {
		const field = {
			id: 'audience',
			type: 'customselect',
			entityType: 'audience',
			multiple: true,
			std: 'basic,premium',
		} as OldCustomSelectField;

		expect( parseOldArgsToProps( field ) ).toMatchObject( {
			type: 'customselect',
			multiple: true,
			entityType: 'audience',
		} );
	} );
} );

describe( 'number control values', () => {
	it( 'preserves fractional values', () => {
		expect( parseNumberControlValue( '1.5' ) ).toBe( 1.5 );
	} );
} );
