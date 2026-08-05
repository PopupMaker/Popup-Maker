#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );

const workflowPath = path.resolve(
	__dirname,
	'../.github/workflows/translate.yml'
);
const workflow = fs.readFileSync( workflowPath, 'utf8' );
const attributes = fs.readFileSync(
	path.resolve( __dirname, '../.gitattributes' ),
	'utf8'
);
const failures = [];

const requirePattern = ( pattern, message ) => {
	if ( ! pattern.test( workflow ) ) {
		failures.push( message );
	}
};

const forbidPattern = ( pattern, message ) => {
	if ( pattern.test( workflow ) ) {
		failures.push( message );
	}
};

requirePattern(
	/^\s{4}workflow_dispatch:/m,
	'Translation must remain workflow_dispatch-only.'
);
requirePattern(
	/dry_run:[\s\S]*?default:\s*true/,
	'Translation dispatches must default to dry-run mode.'
);
requirePattern(
	/MAX_PAID_COST:\s*['"]0\.25['"]/,
	'Paid translation must retain the hard $0.25 ceiling.'
);
requirePattern(
	/MAX_PAID_RUNS_PER_24_HOURS:\s*['"]1['"]/,
	'Paid translation must remain limited to one run per 24 hours.'
);
requirePattern(
	/MAX_STRINGS_PER_LANGUAGE:\s*['"]250['"]/,
	'Paid translation must retain the per-language string ceiling.'
);
requirePattern(
	/PAID_CONFIRMATION[\s\S]*?TRANSLATE/,
	'Paid translation must require explicit confirmation.'
);
requirePattern(
	/GITHUB_RUN_ATTEMPT[\s\S]*?cannot be re-run/,
	'Paid workflow attempts must not be re-runnable.'
);
requirePattern(
	/actions\/workflows\/translate\.yml\/runs\?event=workflow_dispatch/,
	'Paid translation must enforce its rolling run limit from Actions history.'
);

forbidPattern(
	/^\s{4}(push|schedule):/m,
	'Automatic translation triggers are forbidden.'
);
forbidPattern(
	/force_translate|force-translate/,
	'Force translation is forbidden.'
);
forbidPattern(
	/^\s{12}(max_cost|max_strings_per_job|model):/m,
	'Paid limits and model selection must not be dispatch inputs.'
);
forbidPattern(
	/DEFAULT_TRANSLATION_MAX_COST|DEFAULT_TRANSLATION_MODEL/,
	'Paid limits and model selection must not be repository-variable overrides.'
);

if ( ! /^\*\.mo\s+binary$/m.test( attributes ) ) {
	failures.push(
		'Compiled MO catalogs must be marked binary in .gitattributes.'
	);
}

if ( failures.length > 0 ) {
	console.error( 'Translation guardrail verification failed:' );
	for ( const failure of failures ) {
		console.error( `- ${ failure }` );
	}
	process.exit( 1 );
}

console.log( 'Translation guardrails verified.' );

/* eslint-enable no-console */
