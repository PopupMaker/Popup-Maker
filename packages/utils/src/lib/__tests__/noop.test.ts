import noop from '../noop';

describe( 'noop', () => {
	it( 'returns undefined', () => {
		expect( noop() ).toBeUndefined();
	} );

	it( 'returns undefined with arguments', () => {
		expect( noop( 1, 'two', { three: 3 } ) ).toBeUndefined();
	} );

	it( 'is a function', () => {
		expect( typeof noop ).toBe( 'function' );
	} );

	it( 'accepts any number of arguments without error', () => {
		expect( () => noop() ).not.toThrow();
		expect( () => noop( 1 ) ).not.toThrow();
		expect( () => noop( 1, 2, 3, 4, 5 ) ).not.toThrow();
	} );
} );
