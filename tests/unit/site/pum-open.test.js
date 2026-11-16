/**
 * Opening a popup whose markup is not on the page must not lock page scroll.
 */
// WordPress provides jQuery at runtime; the test loads the workspace copy.
// eslint-disable-next-line import/no-extraneous-dependencies
const jQuery = require( 'jquery' );

global.jQuery = window.jQuery = global.$ = window.$ = jQuery;

// jQuery UI's .position() is not part of this test environment.
jQuery.fn.position = function () {
	return this;
};

require( '../../../assets/js/src/site/plugins/pum' );
require( '../../../assets/js/src/site/plugins/pum-callbacks' );
require( '../../../assets/js/src/site/plugins/pum-defaults' );
require( '../../../assets/js/src/site/plugins/pum-hooks' );
require( '../../../assets/js/src/site/plugins/pum-animations' );

const lockClasses = [ 'pum-open', 'pum-open-overlay', 'pum-open-scrollable' ];

function htmlLockClasses() {
	return lockClasses.filter( ( name ) =>
		document.documentElement.classList.contains( name )
	);
}

describe( 'popmake open', () => {
	beforeEach( () => {
		document.documentElement.className = '';
		document.body.innerHTML = '';
		window.pum_popups = {};
	} );

	it( 'does not lock scrolling when the popup is not on the page', () => {
		expect( () => window.PUM.open( 999999 ) ).not.toThrow();
		expect( htmlLockClasses() ).toEqual( [] );
	} );

	it( 'does not lock scrolling for an empty collection', () => {
		expect( () => jQuery().popmake( 'open' ) ).not.toThrow();
		expect( htmlLockClasses() ).toEqual( [] );
	} );

	it( 'still opens a popup that is on the page', () => {
		document.body.innerHTML =
			'<div id="pum-5" class="pum pum-overlay"><div class="popmake pum-container"><div class="pum-content"></div></div></div>';
		window.pum_popups[ 'pum-5' ] = { id: 5, location: 'center' };

		jQuery( '#pum-5' ).popmake( 'open' );

		expect( htmlLockClasses() ).toEqual( lockClasses );
		expect( jQuery( '#pum-5' ).hasClass( 'pum-active' ) ).toBe( true );
	} );
} );
