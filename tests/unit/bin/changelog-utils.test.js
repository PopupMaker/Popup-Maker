const { stripDeveloperSections } = require( '../../../bin/changelog-utils' );

describe( 'changelog utilities', () => {
	test( 'removes developer sections while preserving user-facing sections', () => {
		const changelog = `**Improvements**

- Improved PHP compatibility.

**Developers**

- Added PHP 8.5 runtime coverage.

**Fixes**

- Fixed popup rendering.`;

		expect( stripDeveloperSections( changelog ) ).toBe( `**Improvements**

- Improved PHP compatibility.

**Fixes**

- Fixed popup rendering.` );
	} );

	test( 'removes nested headings from a developer section', () => {
		const changelog = `**Fixes**

- Fixed popup rendering.

### Developers

#### Runtime coverage

- Added regression coverage.

### Security

- Hardened popup rendering.`;

		expect( stripDeveloperSections( changelog ) ).toBe( `**Fixes**

- Fixed popup rendering.

### Security

- Hardened popup rendering.` );
		expect( stripDeveloperSections( changelog ) ).not.toContain(
			'Runtime coverage'
		);
	} );

	test( 'leaves changelog content without developer sections unchanged', () => {
		const changelog = `**Improvements**

- Improved PHP compatibility.`;

		expect( stripDeveloperSections( changelog ) ).toBe( changelog );
	} );
} );
