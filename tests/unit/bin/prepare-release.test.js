const {
	getReleasePullRequestDisposition,
} = require( '../../../bin/prepare-release' );

describe( 'release PR disposition', () => {
	test.each( [
		[ 'OPEN', 'reuse' ],
		[ 'CLOSED', 'reopen' ],
		[ 'MERGED', 'merged' ],
	] )( 'classifies a %s PR targeting master', ( state, expected ) => {
		expect(
			getReleasePullRequestDisposition( {
				state,
				baseRefName: 'master',
			} )
		).toBe( expected );
	} );

	test( 'creates a master PR when the existing PR targets another branch', () => {
		expect(
			getReleasePullRequestDisposition( {
				state: 'OPEN',
				baseRefName: 'develop',
			} )
		).toBe( 'create' );
	} );
} );
