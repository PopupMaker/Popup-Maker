#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );

const workflowPath = path.resolve(
	__dirname,
	'../.github/workflows/translate.yml'
);
const workflow = fs.readFileSync( workflowPath, 'utf8' );
const preparationScriptPath = path.resolve(
	__dirname,
	'prepare-translation-catalogs.sh'
);
const preparationScript = fs.readFileSync( preparationScriptPath, 'utf8' );
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

const requirePreparationPattern = ( pattern, message ) => {
	if ( ! pattern.test( preparationScript ) ) {
		failures.push( message );
	}
};

requirePattern(
	/^\s{4}push:[\s\S]*?branches:\s*\[develop\]/m,
	'Translation must run immediately after changes reach develop.'
);
requirePattern(
	/^\s{4}workflow_dispatch:/m,
	'Translation must retain a reviewed manual dispatch.'
);
requirePattern(
	/dry_run:[\s\S]*?default:\s*true/,
	'Manual translation dispatches must default to dry-run mode.'
);
requirePattern(
	/TRANSLATION_BASE_BRANCH:\s*develop/,
	'Automatic translation must always read the latest develop branch.'
);
requirePattern(
	/ref:\s*\$\{\{ env\.TRANSLATION_BASE_BRANCH \}\}/,
	'Translation checkout must explicitly target the canonical branch.'
);
requirePattern(
	/MAX_PAID_COST:\s*['"]1\.25['"]/,
	'Paid translation must retain the hard $1.25 estimated ceiling.'
);
requirePattern(
	/MAX_MISSING_PER_LANGUAGE:\s*['"]75['"]/,
	'Automatic translation must retain the 75-string per-language ceiling.'
);
requirePattern(
	/MAX_TOTAL_MISSING:\s*['"]2100['"]/,
	'Automatic translation must retain the 2,100-unit aggregate ceiling.'
);
requirePattern(
	/EXPECTED_LANGUAGE_COUNT:\s*['"]28['"]/,
	'Automatic translation must require all 28 primed catalogs.'
);
requirePattern(
	/PAID_CONFIRMATION[\s\S]*?TRANSLATE/,
	'Paid manual translation must require explicit confirmation.'
);
requirePattern(
	/GITHUB_RUN_ATTEMPT[\s\S]*?cannot be re-run/,
	'Workflow attempts must not be re-runnable.'
);
requirePattern(
	/automation\/i18n-develop/,
	'Automatic translation must use one consolidated pull-request branch.'
);
requirePattern(
	/translation-automation-blocked/,
	'Automatic translation must honor a persistent blocker issue.'
);
requirePattern(
	/steps\.preflight\.outputs\.within_limits != 'true'/,
	'Catalog size limits must be enforced before any provider call.'
);
requirePattern(
	/steps\.postflight\.outputs\.total_missing/,
	'Paid runs must verify that every missing translation was completed.'
);

requirePreparationPattern(
	/msgmerge[\s\S]*?--update[\s\S]*?--no-fuzzy-matching/,
	'Catalog preparation must merge the current POT without fuzzy reuse.'
);
requirePreparationPattern(
	/MAX_MISSING_PER_LANGUAGE[\s\S]*?MAX_TOTAL_MISSING/,
	'Catalog preparation must enforce per-language and aggregate limits.'
);
requirePreparationPattern(
	/needs_translation=\$NEEDS_TRANSLATION[\s\S]*?within_limits=\$WITHIN_LIMITS/,
	'Catalog preparation must expose the measured translation state.'
);

if (
	workflow.indexOf( 'Enforce translation-size ceiling' ) >
	workflow.indexOf( 'Run AI translation' )
) {
	failures.push( 'Catalog size limits must run before the provider call.' );
}

forbidPattern(
	/^\s{4}schedule:/m,
	'Translation must not be delayed behind a scheduled run.'
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
