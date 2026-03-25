import omit from '../omit';

describe( 'omit', () => {
	const obj = { a: 1, b: 2, c: 3, d: 4 };

	// BUG: omit() is typed as Omit<T, K> but actually PICKS the specified keys
	// instead of omitting them. It behaves like pick(). See BUGS-FOUND-BY-TESTS.md #3.
	// These tests assert the current (buggy) behavior.

	it( 'BUG: returns only the specified keys (picks instead of omitting)', () => {
		const result = omit( obj, 'a', 'b' );
		// Should be { c: 3, d: 4 } if it actually omitted.
		expect( result ).toEqual( { a: 1, b: 2 } );
	} );

	it( 'BUG: returns only the single specified key', () => {
		const result = omit( obj, 'a' );
		// Should be { b: 2, c: 3, d: 4 } if it actually omitted.
		expect( result ).toEqual( { a: 1 } );
	} );

	it( 'returns empty object when no keys are specified', () => {
		const result = omit( obj );
		expect( result ).toEqual( {} );
	} );

	it( 'BUG: returns only the specified key', () => {
		const result = omit( obj, 'c' );
		// Should be { a: 1, b: 2, d: 4 } if it actually omitted.
		expect( result ).toEqual( { c: 3 } );
	} );

	it( 'BUG: returns all keys when all specified (acts as identity pick)', () => {
		const result = omit( obj, 'a', 'b', 'c', 'd' );
		// Should be {} if it actually omitted.
		expect( result ).toEqual( { a: 1, b: 2, c: 3, d: 4 } );
	} );

	it( 'does not mutate the original object', () => {
		omit( obj, 'a', 'b' );
		expect( obj ).toEqual( { a: 1, b: 2, c: 3, d: 4 } );
	} );

	it( 'BUG: returns only specified keys with string values', () => {
		const strObj = { name: 'test', value: 'hello', extra: 'world' };
		const result = omit( strObj, 'name', 'value' );
		// Should be { extra: 'world' } if it actually omitted.
		expect( result ).toEqual( { name: 'test', value: 'hello' } );
	} );
} );
