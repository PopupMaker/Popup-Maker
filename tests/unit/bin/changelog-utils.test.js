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

	test( 'removes a trailing developer section', () => {
		const changelog = `**Fixes**

- Fixed popup rendering.

### Developers

- Added regression coverage.`;

		expect( stripDeveloperSections( changelog ) ).toBe( `**Fixes**

- Fixed popup rendering.` );
	} );

	test( 'leaves changelog content without developer sections unchanged', () => {
		const changelog = `**Improvements**

- Improved PHP compatibility.`;

		expect( stripDeveloperSections( changelog ) ).toBe( changelog );
	} );
} );
