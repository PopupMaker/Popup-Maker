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
	let developerHeadingDepth = 0;

	for ( const line of lines ) {
		const developerHeading = line.match(
			/^\s*(?:\*\*Developers\*\*|(#{2,6})\s+Developers)\s*$/i
		);

		if ( developerHeading ) {
			isDeveloperSection = true;
			developerHeadingDepth = developerHeading[ 1 ]
				? developerHeading[ 1 ].length
				: 0;
			continue;
		}

		if ( isDeveloperSection ) {
			const markdownHeading = line.match( /^\s*(#{2,6})\s+\S/ );
			const isBoldSectionHeading = /^\s*\*\*[^*]+\*\*\s*$/.test( line );
			const isPeerMarkdownHeading =
				markdownHeading &&
				markdownHeading[ 1 ].length <= ( developerHeadingDepth || 2 );

			if ( isBoldSectionHeading || isPeerMarkdownHeading ) {
				isDeveloperSection = false;
			}
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
