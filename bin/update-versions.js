/**
 * Replaces version numbers in files.
 *
 * Usage:
 *   node replace-versions.js <version>
 *
 * Parameters:
 *   <version> The version number to replace.
 *   --dry-run  Prints the files that would be modified without actually modifying them.
 *   --plugin    Replaces the version in plugin files.
 *   --docblock  Replaces the version in docblocks.
 *   --comment   Replaces the version in comments.
 *   --all       Replaces the version in all files.
 */

const fs = require('fs');
const path = require('path');
const glob = require('glob');
const minimist = require('minimist');

const argv = minimist(process.argv.slice(2));

const version = argv._[0];

if (!version) {
	console.error('Please provide a version number.');
	process.exit(1);
}

const dryRun = argv['dry-run'];

let replaceType = 'all';

if (argv['plugin']) {
	replaceType = 'plugin';
} else if (argv['docblock']) {
	replaceType = 'docblock';
}

const excludedDirs = [
	'node_modules/**',
	'vendor/**',
	'vendor-prefixed/**',
	'bin/**',
];

const versionPatterns = [
	// Plugin file header.
	{
		regex: /^([\t ]*\*[\t ]*Version:[\t ]*)(.*)/gm,
		replacement: (newVersion) => `$1${newVersion}`,
	},
	// Plugin main class version.
	{
		regex: /^(.*public[\t ]+static[\t ]+\$VER[\t ]*=[\t ]*['"])(.*)(['"];\s*)$/gm,
		replacement: (newVersion) => `$1${newVersion}$3`,
	},
	// Plugin config array.
	{
		regex: /^([\t ]*'version'[\t ]*=>[\t ]*['"])(.*)(['"],)$/gm,
		replacement: (newVersion) => `$1${newVersion}$3`,
	},
	// Plugin readme.
	{
		regex: /^(Stable tag:[\t ]*)(.*)/gm,
		replacement: (newVersion) => `$1${newVersion}`,
	},
	// Plugin composer & package json.
	{
		regex: /(\s*"version":\s*")(\d+\.\d+\.\d+)(")/gm,
		replacement: (newVersion) => `$1${newVersion}$3`,
	},
];

const docblockPatterns = [
	{
		// Only match if the version is currently X.X.X exactly (the string "@deprecated X.X.X").
		regex: /((@deprecated|@since|@version)\s+)X.X.X/gm,
		replacement: (newVersion) => (_match, tag) => {
			return `${tag}${newVersion}`;
		},
	},
];

const commentPatterns = [
	{
		// Match // single line comments with X.X.X
		regex: /(\/\/.*\s+)X.X.X/gm,
		replacement: (newVersion) => (_match, prefix) => {
			return `${prefix}${newVersion}`;
		},
	},
	{
		// Match /* single line comments with X.X.X */
		regex: /(\/\*.*\s+)X.X.X/gm,
		replacement: (newVersion) => (_match, prefix) => {
			return `${prefix}${newVersion}`;
		},
	},
	{
		// Match /** multi line comments (start with *\s)
		regex: /(\s+\*.*\s+)X.X.X/gm,
		replacement: (newVersion) => (_match, prefix) => {
			return `${prefix}${newVersion}`;
		},
	},
];

/**
 * Update version in specified files with the given patterns.
 *
 * @param {string} filePath - Path to the file.
 * @param {string} newVersion - The new version number.
 * @param {boolean} dryRun - Indicate if this is a dry run.
 * @param {Array} patterns - Array of regex patterns to match and replace.
 */
function updateVersionInFile(filePath, newVersion, dryRun, patterns) {
	if (fs.existsSync(filePath)) {
		const contents = fs.readFileSync(filePath, 'utf8');
		let newContents = contents;

		patterns.forEach((pattern) => {
			newContents = newContents.replace(
				pattern.regex,
				pattern.replacement(newVersion)
			);
		});

		if (newContents !== contents) {
			if (dryRun) {
				console.log(`${filePath}:`);
				console.log(newContents);
			} else {
				fs.writeFileSync(filePath, newContents, 'utf8');
			}
		}
	} else {
		console.log(`No file found at ${filePath}`);
	}
}

if (replaceType === 'all' || replaceType === 'plugin') {
	const pluginSlug = path.basename(process.cwd());
	const pluginFile = process.cwd() + '/' + pluginSlug + '.php';
	const boostrapFile = process.cwd() + '/bootstrap.php';
	const readmeFile = process.cwd() + '/readme.txt';
	const packageJsonFile = process.cwd() + '/' + 'package.json';
	const composerJsonFile = process.cwd() + '/' + 'composer.json';

	if (fs.existsSync(pluginFile)) {
		updateVersionInFile(pluginFile, version, dryRun, versionPatterns);
	}

	if (fs.existsSync(boostrapFile)) {
		updateVersionInFile(boostrapFile, version, dryRun, versionPatterns);
	}

	if (fs.existsSync(readmeFile)) {
		updateVersionInFile(readmeFile, version, dryRun, versionPatterns);
	}

	if (fs.existsSync(packageJsonFile)) {
		updateVersionInFile(packageJsonFile, version, dryRun, versionPatterns);
	}

	if (fs.existsSync(composerJsonFile)) {
		updateVersionInFile(composerJsonFile, version, dryRun, versionPatterns);
	}
}

if (
	replaceType === 'all' ||
	replaceType === 'docblock' ||
	replaceType === 'comment'
) {
	const files = glob.sync('**/*.php', { ignore: excludedDirs });

	// One loop reduces the number of file system calls.
	files.forEach((file) => {
		if (replaceType === 'all' || replaceType === 'docblock') {
			updateVersionInFile(file, version, dryRun, docblockPatterns);
		}

		if (replaceType === 'all' || replaceType === 'comment') {
			updateVersionInFile(file, version, dryRun, commentPatterns);
		}
	});
}
