jest.mock( '@wordpress/url', () => ( {
	isURL: ( url: string ) => {
		try {
			new URL( url );
			return true;
		} catch {
			return false;
		}
	},
} ) );

import isURLLike from '../is-url-like';

describe( 'isURLLike', () => {
	describe( 'full URLs', () => {
		it( 'returns true for https URLs', () => {
			expect( isURLLike( 'https://example.com' ) ).toBe( true );
		} );

		it( 'returns true for http URLs', () => {
			expect( isURLLike( 'http://example.com' ) ).toBe( true );
		} );

		it( 'returns true for URLs with paths', () => {
			expect( isURLLike( 'https://example.com/page' ) ).toBe( true );
		} );

		it( 'returns true for URLs with query strings', () => {
			expect(
				isURLLike( 'https://example.com/page?foo=bar' )
			).toBe( true );
		} );
	} );

	describe( 'www. links', () => {
		it( 'returns true for strings containing www.', () => {
			expect( isURLLike( 'www.example.com' ) ).toBeTruthy();
		} );

		it( 'returns true for www. in the middle of a string', () => {
			expect( isURLLike( 'visit www.example.com today' ) ).toBeTruthy();
		} );
	} );

	describe( 'hash/internal links', () => {
		it( 'returns true for hash-only links', () => {
			expect( isURLLike( '#section' ) ).toBeTruthy();
		} );

		it( 'returns true for just a hash', () => {
			expect( isURLLike( '#' ) ).toBeTruthy();
		} );

		it( 'returns true for hash with path-like value', () => {
			expect( isURLLike( '#/route/page' ) ).toBeTruthy();
		} );
	} );

	describe( 'non-URL strings', () => {
		it( 'returns falsy for plain text', () => {
			expect( isURLLike( 'hello world' ) ).toBeFalsy();
		} );

		it( 'returns falsy for an empty string', () => {
			expect( isURLLike( '' ) ).toBeFalsy();
		} );

		it( 'returns falsy for a single word', () => {
			expect( isURLLike( 'notaurl' ) ).toBeFalsy();
		} );

		it( 'returns falsy for a path without protocol', () => {
			expect( isURLLike( '/just/a/path' ) ).toBeFalsy();
		} );
	} );

	describe( 'edge cases', () => {
		it( 'returns true for ftp protocol URLs', () => {
			expect( isURLLike( 'ftp://files.example.com' ) ).toBe( true );
		} );

		it( 'handles mailto protocol', () => {
			expect( isURLLike( 'mailto:user@example.com' ) ).toBe( true );
		} );

		it( 'returns falsy for strings with only spaces', () => {
			expect( isURLLike( '   ' ) ).toBeFalsy();
		} );
	} );
} );
