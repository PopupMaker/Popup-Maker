const {
	getReleasePullRequestDisposition,
	selectReleasePullRequest,
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

	test( 'selects the master PR safely when a branch has multiple PRs', () => {
		expect(
			selectReleasePullRequest( [
				{ number: 10, state: 'CLOSED' },
				{ number: 11, state: 'MERGED' },
				{ number: 12, state: 'OPEN' },
			] )
		).toEqual( { number: 11, state: 'MERGED' } );
	} );
} );
