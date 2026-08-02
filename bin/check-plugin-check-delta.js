#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );

/**
 * Normalize a Plugin Check path for stable comparisons.
 *
 * @param {string} file File path.
 * @return {string} Normalized plugin-relative path.
 */
function normalizeFile( file ) {
	return file
		.replace( /\\/g, '/' )
		.replace( /^\.\//, '' )
		.replace( /^popup-maker\//, '' );
}

/**
 * Extract the strict JSON payload from wp-env's command wrapper output.
 *
 * @param {string} output Captured wp-env output.
 * @return {Array<Object>} Plugin Check findings.
 */
function parsePluginCheckOutput( output ) {
	const jsonStart = output.search( /^\[/m );
	const jsonEnd = output.lastIndexOf( ']' );

	if ( -1 === jsonStart || jsonEnd < jsonStart ) {
		throw new Error( 'Plugin Check output did not contain a JSON array.' );
	}

	const findings = JSON.parse( output.slice( jsonStart, jsonEnd + 1 ) );

	if ( ! Array.isArray( findings ) ) {
		throw new Error( 'Plugin Check output must contain a JSON array.' );
	}

	return findings.map( ( finding ) => {
		for ( const field of [ 'file', 'type', 'code', 'message' ] ) {
			if ( 'string' !== typeof finding[ field ] ) {
				throw new Error(
					`Plugin Check finding is missing string field: ${ field }.`
				);
			}
		}

		return {
			...finding,
			file: normalizeFile( finding.file ),
			line: Number.isInteger( finding.line ) ? finding.line : 0,
			column: Number.isInteger( finding.column ) ? finding.column : 0,
		};
	} );
}

/**
 * Create a stable fingerprint that deliberately excludes line and column.
 *
 * @param {Object} finding Plugin Check finding.
 * @return {string} Finding fingerprint.
 */
function findingFingerprint( finding ) {
	return JSON.stringify( [
		normalizeFile( finding.file ),
		finding.type,
		finding.code,
		finding.message,
	] );
}

/**
 * Convert findings to a fingerprint multiset.
 *
 * @param {Array<Object>} findings Plugin Check findings.
 * @return {Map<string, {count:number,findings:Array<Object>}>} Finding groups.
 */
function groupFindings( findings ) {
	const groups = new Map();

	for ( const finding of findings ) {
		const fingerprint = findingFingerprint( finding );
		const group = groups.get( fingerprint ) || { count: 0, findings: [] };
		group.count += 1;
		group.findings.push( finding );
		groups.set( fingerprint, group );
	}

	return groups;
}

/**
 * Parse and validate a checked-in Plugin Check baseline.
 *
 * @param {string} contents Baseline JSON.
 * @return {{metadata:Object,groups:Map<string, {count:number,finding:Object}>}} Parsed baseline.
 */
function parseBaseline( contents ) {
	const baseline = JSON.parse( contents );

	if ( 1 !== baseline.version || ! Array.isArray( baseline.findings ) ) {
		throw new Error( 'Unsupported Plugin Check baseline schema.' );
	}

	const groups = new Map();

	for ( const finding of baseline.findings ) {
		for ( const field of [ 'file', 'type', 'code', 'message' ] ) {
			if ( 'string' !== typeof finding[ field ] ) {
				throw new Error(
					`Baseline finding is missing string field: ${ field }.`
				);
			}
		}

		if ( ! Number.isInteger( finding.count ) || finding.count < 1 ) {
			throw new Error(
				'Baseline finding count must be a positive integer.'
			);
		}

		const normalized = {
			...finding,
			file: normalizeFile( finding.file ),
		};
		const fingerprint = findingFingerprint( normalized );

		if ( groups.has( fingerprint ) ) {
			throw new Error(
				'Plugin Check baseline contains a duplicate finding.'
			);
		}

		groups.set( fingerprint, {
			count: normalized.count,
			finding: normalized,
		} );
	}

	return { metadata: baseline, groups };
}

/**
 * Ensure a proposed baseline only removes resolved legacy findings.
 *
 * @param {Map<string, Object>} currentGroups  Proposed baseline groups.
 * @param {Map<string, Object>} previousGroups Target-branch baseline groups.
 * @return {string[]} Validation failures.
 */
function validateBaselineReduction( currentGroups, previousGroups ) {
	const failures = [];

	for ( const [ fingerprint, current ] of currentGroups ) {
		const previous = previousGroups.get( fingerprint );

		if ( ! previous ) {
			failures.push(
				`${ current.finding.file }: baseline adds ${ current.finding.code }.`
			);
			continue;
		}

		if ( current.count > previous.count ) {
			failures.push(
				`${ current.finding.file }: baseline increases ${ current.finding.code } from ${ previous.count } to ${ current.count }.`
			);
		}

		if ( failures.length >= 20 ) {
			break;
		}
	}

	return failures;
}

/**
 * Compare current Plugin Check findings with the approved legacy baseline.
 *
 * @param {Array<Object>}       findings       Current findings.
 * @param {Map<string, Object>} baselineGroups Approved baseline groups.
 * @return {{newFindings:Array<Object>,resolved:Array<Object>,currentCount:number,baselineCount:number}} Comparison.
 */
function compareFindings( findings, baselineGroups ) {
	const currentGroups = groupFindings( findings );
	const newFindings = [];
	const resolved = [];
	let baselineCount = 0;

	for ( const baseline of baselineGroups.values() ) {
		baselineCount += baseline.count;
	}

	for ( const [ fingerprint, current ] of currentGroups ) {
		const allowedCount = baselineGroups.get( fingerprint )?.count || 0;
		newFindings.push( ...current.findings.slice( allowedCount ) );
	}

	for ( const [ fingerprint, baseline ] of baselineGroups ) {
		const currentCount = currentGroups.get( fingerprint )?.count || 0;
		if ( currentCount < baseline.count ) {
			resolved.push( {
				...baseline.finding,
				count: baseline.count - currentCount,
			} );
		}
	}

	return {
		newFindings,
		resolved,
		currentCount: findings.length,
		baselineCount,
	};
}

/**
 * Escape a value used in a GitHub workflow annotation.
 *
 * @param {string} value Annotation value.
 * @return {string} Escaped value.
 */
function escapeAnnotation( value ) {
	return String( value )
		.replace( /%/g, '%25' )
		.replace( /\r/g, '%0D' )
		.replace( /\n/g, '%0A' );
}

/**
 * Read a named command-line argument.
 *
 * @param {string[]} args     Command-line arguments.
 * @param {string}   name     Argument name.
 * @param {boolean}  required Whether the argument is required.
 * @return {string|null} Argument value.
 */
function getArgument( args, name, required = true ) {
	const index = args.indexOf( name );
	const value = -1 === index ? null : args[ index + 1 ];

	if ( required && ( ! value || value.startsWith( '--' ) ) ) {
		throw new Error( `Missing required argument: ${ name }.` );
	}

	return value;
}

function main() {
	try {
		const args = process.argv.slice( 2 );
		const resultsPath = getArgument( args, '--results' );
		const baselinePath = getArgument( args, '--baseline' );
		const previousBaselinePath = getArgument(
			args,
			'--previous-baseline',
			false
		);
		const findings = parsePluginCheckOutput(
			fs.readFileSync( resultsPath, 'utf8' )
		);
		const baseline = parseBaseline(
			fs.readFileSync( baselinePath, 'utf8' )
		);

		if ( previousBaselinePath ) {
			const previousBaseline = parseBaseline(
				fs.readFileSync( previousBaselinePath, 'utf8' )
			);
			const baselineFailures = validateBaselineReduction(
				baseline.groups,
				previousBaseline.groups
			);

			if ( baselineFailures.length ) {
				for ( const failure of baselineFailures ) {
					console.error(
						`::error title=Plugin Check baseline expanded::${ escapeAnnotation(
							failure
						) }`
					);
				}
				throw new Error(
					'The legacy baseline may only shrink as findings are remediated.'
				);
			}
		}

		const comparison = compareFindings( findings, baseline.groups );

		for ( const finding of comparison.newFindings.slice( 0, 50 ) ) {
			const location = [
				`file=${ escapeAnnotation( finding.file ) }`,
				finding.line > 0 ? `line=${ finding.line }` : null,
				finding.column > 0 ? `col=${ finding.column }` : null,
			]
				.filter( Boolean )
				.join( ',' );
			console.error(
				`::error ${ location },title=${ escapeAnnotation(
					finding.code
				) }::${ escapeAnnotation( finding.message ) }`
			);
		}

		if ( comparison.resolved.length ) {
			const resolvedCount = comparison.resolved.reduce(
				( total, finding ) => total + finding.count,
				0
			);
			console.error(
				`::error title=Plugin Check baseline is stale::${ resolvedCount } legacy finding(s) were resolved. Reduce .github/plugin-check-baseline.json in this remediation PR.`
			);
		}

		if ( comparison.newFindings.length || comparison.resolved.length ) {
			process.exitCode = 1;
			return;
		}

		console.log(
			`Plugin Check delta passed: ${ comparison.currentCount } known finding(s), 0 new.`
		);
	} catch ( error ) {
		console.error( `Plugin Check delta failed: ${ error.message }` );
		process.exitCode = 1;
	}
}

if ( require.main === module ) {
	main();
}

module.exports = {
	compareFindings,
	escapeAnnotation,
	findingFingerprint,
	groupFindings,
	normalizeFile,
	parseBaseline,
	parsePluginCheckOutput,
	validateBaselineReduction,
};

/* eslint-enable no-console */
