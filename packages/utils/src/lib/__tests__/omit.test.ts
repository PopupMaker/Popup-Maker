import omit from '../omit';

describe( 'omit', () => {
	const obj = { a: 1, b: 2, c: 3, d: 4 };

	it( 'returns an object without the specified keys', () => {
		const result = omit( obj, 'a', 'b' );
		expect( result ).toEqual( { c: 3, d: 4 } );
	} );

	it( 'keeps keys that were not specified', () => {
		const result = omit( obj, 'a' );
		expect( result ).toEqual( { b: 2, c: 3, d: 4 } );
	} );

	it( 'returns a copy when no keys are specified', () => {
		const result = omit( obj );
		expect( result ).toEqual( { a: 1, b: 2, c: 3, d: 4 } );
	} );

	it( 'handles a single key', () => {
		const result = omit( obj, 'c' );
		expect( result ).toEqual( { a: 1, b: 2, d: 4 } );
	} );

	it( 'returns empty object when all keys omitted', () => {
		const result = omit( obj, 'a', 'b', 'c', 'd' );
		expect( result ).toEqual( {} );
	} );

	it( 'does not mutate the original object', () => {
		omit( obj, 'a', 'b' );
		expect( obj ).toEqual( { a: 1, b: 2, c: 3, d: 4 } );
	} );

	it( 'works with string values', () => {
		const strObj = { name: 'test', value: 'hello', extra: 'world' };
		const result = omit( strObj, 'name', 'value' );
		expect( result ).toEqual( { extra: 'world' } );
	} );
} );
