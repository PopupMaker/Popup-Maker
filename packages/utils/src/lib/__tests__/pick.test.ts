import pick from '../pick';

describe( 'pick', () => {
	const obj = { a: 1, b: 2, c: 3, d: 4 };

	it( 'picks specified keys from an object', () => {
		expect( pick( obj, 'a', 'c' ) ).toEqual( { a: 1, c: 3 } );
	} );

	it( 'picks a single key', () => {
		expect( pick( obj, 'b' ) ).toEqual( { b: 2 } );
	} );

	it( 'returns undefined for non-existent keys', () => {
		const result = pick( obj as any, 'a', 'z' as any );
		expect( result ).toEqual( { a: 1, z: undefined } );
	} );

	it( 'returns an empty object when no keys specified', () => {
		expect( pick( obj ) ).toEqual( {} );
	} );

	it( 'picks all keys when all are specified', () => {
		expect( pick( obj, 'a', 'b', 'c', 'd' ) ).toEqual( obj );
	} );

	it( 'does not mutate the original object', () => {
		pick( obj, 'a', 'b' );
		expect( obj ).toEqual( { a: 1, b: 2, c: 3, d: 4 } );
	} );

	it( 'works with mixed value types', () => {
		const mixed = { str: 'hello', num: 42, bool: true, arr: [ 1, 2 ] };
		expect( pick( mixed, 'str', 'arr' ) ).toEqual( {
			str: 'hello',
			arr: [ 1, 2 ],
		} );
	} );
} );
