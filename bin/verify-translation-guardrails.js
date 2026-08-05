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
const baseBranchMatch = workflow.match(
	/^\s{4}TRANSLATION_BASE_BRANCH:\s*([^\s#]+)\s*$/m
);
const baseBranch = baseBranchMatch ? baseBranchMatch[ 1 ] : '';
const escapedBaseBranch = baseBranch.replace(
	/[.*+?^$()|[\]\\]/g,
	'\\$&'
);
const potCommandIndex = workflow.indexOf( 'wp i18n make-pot' );
const potDomainIndex = workflow.indexOf( '--domain=$PLUGIN_SLUG', potCommandIndex );
const potExcludeIndex = workflow.indexOf( '--exclude=', potDomainIndex );
const scopeStepIndex = workflow.indexOf( 'Configure translation scope', potExcludeIndex );

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

if (
	potCommandIndex < 0 ||
	potDomainIndex < potCommandIndex ||
	potExcludeIndex < potDomainIndex ||
	scopeStepIndex < potExcludeIndex
) {
	failures.push(
		'The complete POT generation command must precede translation scope configuration.'
	);
}

if ( ! baseBranch ) {
	failures.push( 'Translation must declare its canonical base branch.' );
} else {
	requirePattern(
		new RegExp(
			`^\\s{4}push:[\\s\\S]*?branches:\\s*\\[\\s*${ escapedBaseBranch }\\s*\\]`,
			'm'
		),
		`Translation must run immediately after changes reach ${ baseBranch }.`
	);
}
requirePattern(
	/^\s{4}workflow_dispatch:/m,
	'Translation must retain a reviewed manual dispatch.'
);
requirePattern(
	/dry_run:[\s\S]*?default:\s*true/,
	'Manual translation dispatches must default to dry-run mode.'
);
requirePattern(
	/languages:[\s\S]*?Languages to prime, comma-separated/,
	'Manual translation dispatches must support an explicit language subset.'
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
	/MAX_PRIME_PAID_COST:\s*['"]0\.60['"]/,
	'Manual catalog priming must retain the hard $0.60 estimated ceiling.'
);
requirePattern(
	/MAX_PRIME_PAID_RUNS_PER_24_HOURS:\s*['"]2['"]/,
	'Manual catalog priming must remain limited to two paid runs per day.'
);
requirePattern(
	/MAX_MISSING_PER_LANGUAGE:\s*['"]75['"]/,
	'Automatic translation must retain the 75-string per-language ceiling.'
);
requirePattern(
	/MAX_PRIME_MISSING_PER_LANGUAGE:\s*['"]200['"]/,
	'Manual catalog priming must retain the 200-string per-language ceiling.'
);
requirePattern(
	/MAX_PRIME_STRINGS_PER_JOB:\s*['"]200['"]/,
	'Manual catalog priming must allow its complete approved per-language batch.'
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
	/GITHUB_EVENT_NAME" == "push"[\s\S]*?CATALOG_COUNT[\s\S]*?EXPECTED_LANGUAGE_COUNT[\s\S]*?reviewed manual paid dispatch[\s\S]*?should_run=false/,
	'First-time catalog generation must require a reviewed manual paid dispatch.'
);
requirePattern(
	/AUTOMATION_BRANCH:\s*automation\/i18n-[A-Za-z0-9._-]+/,
	'Automatic translation must use one consolidated pull-request branch.'
);
requirePattern(
	/translation-automation-blocked/,
	'Automatic translation must honor a persistent blocker issue.'
);
requirePattern(
	/gh pr list --repo "\$GITHUB_REPOSITORY"/,
	'The checkout-free safety gate must explicitly target its repository.'
);
requirePattern(
	/gh issue list --repo "\$GITHUB_REPOSITORY"/,
	'The checkout-free blocker gate must explicitly target its repository.'
);
requirePattern(
	/steps\.preflight\.outputs\.within_limits != 'true'/,
	'Catalog size limits must be enforced before any provider call.'
);
requirePattern(
	/steps\.postflight\.outputs\.total_missing[\s\S]*?steps\.postflight\.outputs\.within_limits/,
	'Paid runs must verify that every missing translation was completed.'
);
requirePattern(
	/EXPECTED_CATALOG_LOCALES:[\s\S]*?configure-translation-scope\.sh[\s\S]*?"\$EXPECTED_CATALOG_LOCALES"[\s\S]*?prepare-translation-catalogs\.sh[\s\S]*?steps\.scope\.outputs\.expected_catalog_locales/,
	'Catalog preflight must validate the exact configured locale set.'
);
requirePattern(
	/configure-translation-scope\.sh[\s\S]*?steps\.scope\.outputs\.target_languages[\s\S]*?steps\.scope\.outputs\.expected_catalog_locales/,
	'Manual catalog priming must validate and reuse its selected locale scope.'
);
requirePattern(
	/--max-strings-per-job\s+"\$\{\{ steps\.scope\.outputs\.max_strings_per_job \}\}"/,
	'The provider string limit must use the reviewed automatic or prime scope.'
);
requirePattern(
	/display_title == "AI Translate \(paid manual\)"/,
	'Manual rate limiting must count paid dispatches only.'
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
	/MISSING_CATALOGS[\s\S]*?SOURCE_STRING_COUNT[\s\S]*?TOTAL_MISSING/,
	'Missing catalogs must be costed as a full source catalog before translation.'
);
requirePreparationPattern(
	/UNEXPECTED_CATALOGS/,
	'Unexpected locale catalogs must be rejected.'
);
requirePreparationPattern(
	/missing_catalogs=\$MISSING_CATALOGS[\s\S]*?unexpected_catalogs=\$UNEXPECTED_CATALOGS[\s\S]*?needs_translation=\$NEEDS_TRANSLATION[\s\S]*?within_limits=\$WITHIN_LIMITS/,
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
