import clamp from '../clamp';

describe( 'clamp', () => {
	describe( 'two-argument clamping (boundTwo is 0 or falsy)', () => {
		it( 'returns the number when it is less than or equal to boundOne', () => {
			expect( clamp( 5, 10, 0 ) ).toBe( 5 );
		} );

		it( 'returns boundOne when number exceeds it', () => {
			expect( clamp( 15, 10, 0 ) ).toBe( 10 );
		} );

		it( 'returns boundOne when number equals boundOne', () => {
			expect( clamp( 10, 10, 0 ) ).toBe( 10 );
		} );

		it( 'returns negative number when below boundOne of 0', () => {
			expect( clamp( -5, 0, 0 ) ).toBe( -5 );
		} );

		it( 'returns 0 when number is 0 and boundOne is 0', () => {
			expect( clamp( 0, 0, 0 ) ).toBe( 0 );
		} );
	} );

	describe( 'three-argument clamping', () => {
		it( 'returns number when within range', () => {
			expect( clamp( 5, 1, 10 ) ).toBe( 5 );
		} );

		it( 'returns lower bound when number is below range', () => {
			expect( clamp( -5, 1, 10 ) ).toBe( 1 );
		} );

		it( 'returns upper bound when number is above range', () => {
			expect( clamp( 15, 1, 10 ) ).toBe( 10 );
		} );

		it( 'returns boundOne when number equals lower bound', () => {
			expect( clamp( 1, 1, 10 ) ).toBe( 1 );
		} );

		it( 'returns boundTwo when number equals upper bound', () => {
			expect( clamp( 10, 1, 10 ) ).toBe( 10 );
		} );
	} );

	describe( 'negative numbers', () => {
		it( 'clamps within negative range', () => {
			expect( clamp( -5, -10, -1 ) ).toBe( -5 );
		} );

		it( 'clamps below negative lower bound', () => {
			expect( clamp( -15, -10, -1 ) ).toBe( -10 );
		} );

		it( 'clamps above negative upper bound', () => {
			expect( clamp( 0, -10, -1 ) ).toBe( -1 );
		} );
	} );

	describe( 'same bounds', () => {
		it( 'returns the bound value when both bounds are equal and number differs', () => {
			expect( clamp( 0, 5, 5 ) ).toBe( 5 );
		} );

		it( 'returns the value when it equals both bounds', () => {
			expect( clamp( 5, 5, 5 ) ).toBe( 5 );
		} );
	} );
} );
