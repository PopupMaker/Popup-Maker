const {
	compareFindings,
	escapeAnnotation,
	parseBaseline,
	parsePluginCheckOutput,
	validateBaselineReduction,
} = require( '../../../bin/check-plugin-check-delta' );

const knownFinding = {
	file: 'classes/Legacy.php',
	line: 10,
	column: 4,
	type: 'ERROR',
	code: 'legacy_error',
	message: 'Known legacy issue.',
};

function baselineFor( findings ) {
	return parseBaseline(
		JSON.stringify( {
			version: 1,
			findings,
		} )
	);
}

describe( 'Plugin Check delta gate', () => {
	test( 'parses strict JSON from wp-env wrapper output', () => {
		const output = `Starting command.\n${ JSON.stringify( [
			knownFinding,
		] ) }Completed command.`;

		expect( parsePluginCheckOutput( output ) ).toEqual( [ knownFinding ] );
	} );

	test( 'rejects malformed Plugin Check output', () => {
		expect( () =>
			parsePluginCheckOutput( 'No report was emitted.' )
		).toThrow( 'Plugin Check output did not contain a JSON array.' );
	} );

	test( 'ignores line and column movement for known findings', () => {
		const baseline = baselineFor( [ { ...knownFinding, count: 1 } ] );
		const movedFinding = { ...knownFinding, line: 90, column: 12 };
		const comparison = compareFindings( [ movedFinding ], baseline.groups );

		expect( comparison.newFindings ).toEqual( [] );
		expect( comparison.resolved ).toEqual( [] );
	} );

	test( 'reports only occurrences above the known count', () => {
		const baseline = baselineFor( [ { ...knownFinding, count: 1 } ] );
		const addedFinding = { ...knownFinding, line: 20 };
		const comparison = compareFindings(
			[ knownFinding, addedFinding ],
			baseline.groups
		);

		expect( comparison.newFindings ).toEqual( [ addedFinding ] );
		expect( comparison.resolved ).toEqual( [] );
	} );

	test( 'reports resolved findings so the baseline cannot become stale', () => {
		const baseline = baselineFor( [ { ...knownFinding, count: 2 } ] );
		const comparison = compareFindings( [ knownFinding ], baseline.groups );

		expect( comparison.newFindings ).toEqual( [] );
		expect( comparison.resolved ).toEqual( [
			{ ...knownFinding, count: 1 },
		] );
	} );

	test( 'allows a baseline to shrink but never expand', () => {
		const previous = baselineFor( [ { ...knownFinding, count: 2 } ] );
		const reduced = baselineFor( [ { ...knownFinding, count: 1 } ] );
		const increased = baselineFor( [ { ...knownFinding, count: 3 } ] );
		const newEntry = baselineFor( [
			{ ...knownFinding, count: 2 },
			{
				...knownFinding,
				file: 'classes/New.php',
				count: 1,
			},
		] );

		expect(
			validateBaselineReduction( reduced.groups, previous.groups )
		).toEqual( [] );
		expect(
			validateBaselineReduction( increased.groups, previous.groups )
		).toEqual( [
			'classes/Legacy.php: baseline increases legacy_error from 2 to 3.',
		] );
		expect(
			validateBaselineReduction( newEntry.groups, previous.groups )
		).toEqual( [ 'classes/New.php: baseline adds legacy_error.' ] );
	} );

	test( 'escapes workflow command content', () => {
		expect( escapeAnnotation( '100%\nunsafe' ) ).toBe( '100%25%0Aunsafe' );
	} );
} );
