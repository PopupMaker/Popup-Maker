/**
 * Remove developer-only sections from changelog content.
 *
 * Developer sections remain in CHANGELOG.md for maintainers, but are omitted
 * from user-facing release notes and the WordPress.org readme.
 *
 * @param {string} content Changelog content.
 * @return {string} Changelog content without developer-only sections.
 */
function stripDeveloperSections( content ) {
	const newline = content.includes( '\r\n' ) ? '\r\n' : '\n';
	const lines = content.split( /\r?\n/ );
	const filteredLines = [];
	let isDeveloperSection = false;

	for ( const line of lines ) {
		if (
			/^\s*(?:\*\*Developers\*\*|#{3,6}\s+Developers)\s*$/i.test( line )
		) {
			isDeveloperSection = true;
			continue;
		}

		if (
			isDeveloperSection &&
			/^\s*(?:\*\*[^*]+\*\*|#{2,6}\s+\S)/.test( line )
		) {
			isDeveloperSection = false;
		}

		if ( ! isDeveloperSection ) {
			filteredLines.push( line );
		}
	}

	return filteredLines
		.join( newline )
		.replace( /(?:\r?\n){3,}/g, `${ newline }${ newline }` )
		.trim();
}

module.exports = { stripDeveloperSections };
