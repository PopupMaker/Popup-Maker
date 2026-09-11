jest.mock( '@medv/finder', () => ( {
	finder: jest.fn(),
} ) );

import $ from 'jquery';
import { AdminBar } from '../AdminBar';

const popupId = '123';

function addToolbarAction( action: string ): JQuery< HTMLElement > {
	const $item = $( `
		<li class="pum-toolbar-action">
			<a class="ab-item" href="#pum-toolbar-action__${ action }--${ popupId }">
				<span>Action</span>
			</a>
		</li>
	` );

	$( '#wpadminbar' ).append( $item );
	return $item;
}

describe( 'AdminBar popup controls', () => {
	const PUM: Window[ 'PUM' ] = {
		open: jest.fn(),
		close: jest.fn(),
		checkConditions: jest.fn(),
		clearCookies: jest.fn(),
	};

	beforeEach( () => {
		document.body.innerHTML = '<div id="wpadminbar"></div>';
		window.PUM = PUM;
		Object.defineProperty( globalThis, 'popupMakerAdminBar', {
			configurable: true,
			value: {
				i18n: {
					instructions: 'Choose an element.',
					results: 'Selector',
					copy: 'Copy',
					close: 'Close',
					copied: 'Copied to clipboard',
				},
			},
		} );
		jest.clearAllMocks();
		new AdminBar();
	} );

	afterEach( () => {
		$( document ).off();
		$( '#wpadminbar' ).off();
	} );

	it.each( [
		[ 'open', 'open' ],
		[ 'close', 'close' ],
	] as const )( 'runs the %s control', ( action, method ) => {
		const $item = addToolbarAction( action );

		$item.find( 'span' ).trigger( 'click' );

		expect( PUM[ method ] ).toHaveBeenCalledWith( popupId );
	} );

	it( 'checks popup conditions and reports the result', () => {
		jest.mocked( PUM.checkConditions ).mockReturnValue( true );
		const $item = addToolbarAction( 'check-conditions' );

		$item.find( 'a' ).trigger( 'click' );

		expect( PUM.checkConditions ).toHaveBeenCalledWith( popupId );
		expect(
			document.querySelector( '.pum-modal-body' )?.textContent
		).toContain( 'The conditions were met.' );
	} );

	it( 'clears popup cookies and reports success', () => {
		const $item = addToolbarAction( 'reset-cookies' );

		$item.find( 'a' ).trigger( 'click' );

		expect( PUM.clearCookies ).toHaveBeenCalledWith( popupId );
		expect(
			document.querySelector( '.pum-modal-body' )?.textContent
		).toContain( 'The cookies were reset successfully.' );
	} );

	it( 'leaves the Edit Popup link to normal browser navigation', () => {
		const event = $.Event( 'click' );
		$( '#wpadminbar' ).append(
			'<li><a class="ab-item" href="/wp-admin/post.php?post=123&action=edit">Edit Popup</a></li>'
		);

		$( '#wpadminbar a' ).trigger( event );

		expect( event.isDefaultPrevented() ).toBe( false );
	} );

	it.each( [
		'#pum-toolbar-action__open--not-a-number',
		'#pum-toolbar-action__open--123-extra',
		'#pum-toolbar-action__unknown--123',
		'#malformed',
	] )( 'ignores an invalid action href: %s', ( href ) => {
		const $item = addToolbarAction( 'open' );
		$item.children( 'a' ).attr( 'href', href );

		$item.find( 'a' ).trigger( 'click' );

		expect( PUM.open ).not.toHaveBeenCalled();
		expect( PUM.close ).not.toHaveBeenCalled();
		expect( PUM.checkConditions ).not.toHaveBeenCalled();
		expect( PUM.clearCookies ).not.toHaveBeenCalled();
	} );
} );
