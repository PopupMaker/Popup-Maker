/**
 * Tests for the debug utility module.
 *
 * We test getDebugConfig parsing logic and debug method gating
 * by manipulating localStorage and verifying console output.
 */

// Mock window.addEventListener to capture the storage listener.
const storageListeners: Array< () => void > = [];
const origAddEventListener = window.addEventListener;
window.addEventListener = jest.fn( ( event: string, handler: any ) => {
	if ( event === 'storage' ) {
		storageListeners.push( handler );
	}
} );

// We need to re-import debug fresh for each describe block to capture
// the module-level getDebugConfig call with the right localStorage state.
// Use isolateModules for this.

describe( 'debug', () => {
	let consoleSpy: {
		groupCollapsed: jest.SpyInstance;
		groupEnd: jest.SpyInstance;
		log: jest.SpyInstance;
	};

	beforeEach( () => {
		consoleSpy = {
			groupCollapsed: jest
				.spyOn( console, 'groupCollapsed' )
				.mockImplementation(),
			groupEnd: jest.spyOn( console, 'groupEnd' ).mockImplementation(),
			log: jest.spyOn( console, 'log' ).mockImplementation(),
		};
	} );

	afterEach( () => {
		jest.restoreAllMocks();
		localStorage.clear();
		storageListeners.length = 0;
	} );

	describe( 'with all debugging disabled (default)', () => {
		let debugModule: typeof import( '../debug' );

		beforeEach( () => {
			localStorage.clear();
			jest.isolateModules( () => {
				debugModule = require( '../debug' );
			} );
		} );

		it( 'does not log for component when disabled', () => {
			debugModule.debug.component( 'TestComponent', 'data' );
			expect( consoleSpy.groupCollapsed ).not.toHaveBeenCalled();
		} );

		it( 'does not log for effect when disabled', () => {
			debugModule.debug.effect( 'TestComponent', 'mount', 'data' );
			expect( consoleSpy.groupCollapsed ).not.toHaveBeenCalled();
		} );

		it( 'does not log for selector when disabled', () => {
			debugModule.debug.selector( 'getItems', 'data' );
			expect( consoleSpy.groupCollapsed ).not.toHaveBeenCalled();
		} );

		it( 'does not log for action when disabled', () => {
			debugModule.debug.action( 'updateItem', 'data' );
			expect( consoleSpy.groupCollapsed ).not.toHaveBeenCalled();
		} );

		it( 'does not log for resolver when disabled', () => {
			debugModule.debug.resolver( 'fetchItems', 'data' );
			expect( consoleSpy.groupCollapsed ).not.toHaveBeenCalled();
		} );

		it( 'does not log for state when disabled', () => {
			debugModule.debug.state( 'reducer', 'data' );
			expect( consoleSpy.groupCollapsed ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'with wildcard pum:* enabled', () => {
		let debugModule: typeof import( '../debug' );

		beforeEach( () => {
			localStorage.setItem( 'debug', 'pum:*' );
			jest.isolateModules( () => {
				debugModule = require( '../debug' );
			} );
		} );

		it( 'logs component debug messages', () => {
			debugModule.debug.component( 'MyComponent', { prop: 1 } );
			expect( consoleSpy.groupCollapsed ).toHaveBeenCalledTimes( 1 );
			expect( consoleSpy.groupCollapsed.mock.calls[ 0 ][ 0 ] ).toMatch(
				/Component:MyComponent/
			);
		} );

		it( 'logs effect debug messages', () => {
			debugModule.debug.effect( 'MyComponent', 'useMount' );
			expect( consoleSpy.groupCollapsed ).toHaveBeenCalledTimes( 1 );
			expect( consoleSpy.groupCollapsed.mock.calls[ 0 ][ 0 ] ).toMatch(
				/Effect:MyComponent:useMount/
			);
		} );

		it( 'logs selector debug messages', () => {
			debugModule.debug.selector( 'getPopups' );
			expect( consoleSpy.groupCollapsed.mock.calls[ 0 ][ 0 ] ).toMatch(
				/Select:getPopups/
			);
		} );

		it( 'logs action debug messages', () => {
			debugModule.debug.action( 'createPopup' );
			expect( consoleSpy.groupCollapsed.mock.calls[ 0 ][ 0 ] ).toMatch(
				/Action:createPopup/
			);
		} );

		it( 'logs resolver debug messages', () => {
			debugModule.debug.resolver( 'fetchPopups' );
			expect( consoleSpy.groupCollapsed.mock.calls[ 0 ][ 0 ] ).toMatch(
				/Resolver:fetchPopups/
			);
		} );

		it( 'logs state debug messages', () => {
			debugModule.debug.state( 'popupReducer' );
			expect( consoleSpy.groupCollapsed.mock.calls[ 0 ][ 0 ] ).toMatch(
				/State:popupReducer/
			);
		} );

		it( 'includes stack trace when stack debugging is enabled', () => {
			debugModule.debug.component( 'Test' );
			// With pum:* stack is true, so console.log should be called for the stack.
			expect( consoleSpy.log ).toHaveBeenCalled();
		} );
	} );

	describe( 'with selective feature flags', () => {
		let debugModule: typeof import( '../debug' );

		beforeEach( () => {
			localStorage.setItem( 'debug', 'pum:component,pum:actions' );
			jest.isolateModules( () => {
				debugModule = require( '../debug' );
			} );
		} );

		it( 'logs component messages when pum:component is set', () => {
			debugModule.debug.component( 'Selective' );
			expect( consoleSpy.groupCollapsed ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'logs action messages when pum:actions is set', () => {
			debugModule.debug.action( 'doSomething' );
			expect( consoleSpy.groupCollapsed ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'does not log effects when pum:effects is not set', () => {
			debugModule.debug.effect( 'Component', 'effect' );
			expect( consoleSpy.groupCollapsed ).not.toHaveBeenCalled();
		} );

		it( 'does not log selectors when pum:selectors is not set', () => {
			debugModule.debug.selector( 'getSomething' );
			expect( consoleSpy.groupCollapsed ).not.toHaveBeenCalled();
		} );

		it( 'does not include stack when pum:stack is not set', () => {
			debugModule.debug.component( 'NoStack' );
			expect( consoleSpy.log ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'timestamp format', () => {
		let debugModule: typeof import( '../debug' );

		beforeEach( () => {
			localStorage.setItem( 'debug', 'pum:component' );
			jest.isolateModules( () => {
				debugModule = require( '../debug' );
			} );
		} );

		it( 'includes a timestamp in the log output', () => {
			debugModule.debug.component( 'TimestampTest' );
			const firstArg = consoleSpy.groupCollapsed.mock.calls[ 0 ][ 0 ];
			// Timestamp format: HH:MM:SS.mmmZ
			expect( firstArg ).toMatch( /\[\d{2}:\d{2}:\d{2}/ );
		} );
	} );

	describe( 'localStorage error handling', () => {
		it( 'returns all-false config when localStorage throws', () => {
			const getItemSpy = jest
				.spyOn( Storage.prototype, 'getItem' )
				.mockImplementation( () => {
					throw new Error( 'Access denied' );
				} );

			let debugModule: typeof import( '../debug' );
			jest.isolateModules( () => {
				debugModule = require( '../debug' );
			} );

			// All methods should be silent since config defaults to all false.
			debugModule!.debug.component( 'Test' );
			debugModule!.debug.action( 'Test' );
			debugModule!.debug.effect( 'Test', 'Test' );
			expect( consoleSpy.groupCollapsed ).not.toHaveBeenCalled();

			getItemSpy.mockRestore();
		} );
	} );
} );
