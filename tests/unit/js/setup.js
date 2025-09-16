/**
 * Jest setup file for Pro Upgrader JavaScript unit tests.
 *
 * This file runs before each test and sets up the global environment,
 * mocks, and utilities needed for testing.
 */

// Import Jest DOM matchers
import '@testing-library/jest-dom';

// Mock performance API for timing tests
global.performance = {
	now: jest.fn( () => Date.now() ),
	mark: jest.fn(),
	measure: jest.fn(),
	getEntriesByName: jest.fn( () => [] ),
	getEntriesByType: jest.fn( () => [] ),
	clearMarks: jest.fn(),
	clearMeasures: jest.fn(),
};

// Mock console methods to reduce noise during testing
global.console = {
	...console,
	// Keep error and warn for debugging but don't spam output
	log: jest.fn(),
	debug: jest.fn(),
	info: jest.fn(),
	warn: jest.fn(),
	error: jest.fn(),
};

// Mock window.location
delete window.location;
window.location = {
	href: 'http://localhost/wp-admin/',
	origin: 'http://localhost',
	pathname: '/wp-admin/',
	search: '',
	hash: '',
	assign: jest.fn(),
	replace: jest.fn(),
	reload: jest.fn(),
};

// Mock localStorage
const localStorageMock = {
	getItem: jest.fn(),
	setItem: jest.fn(),
	removeItem: jest.fn(),
	clear: jest.fn(),
	length: 0,
	key: jest.fn(),
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
	getItem: jest.fn(),
	setItem: jest.fn(),
	removeItem: jest.fn(),
	clear: jest.fn(),
	length: 0,
	key: jest.fn(),
};
global.sessionStorage = sessionStorageMock;

// Mock XMLHttpRequest
global.XMLHttpRequest = jest.fn( () => ( {
	open: jest.fn(),
	send: jest.fn(),
	setRequestHeader: jest.fn(),
	addEventListener: jest.fn(),
	removeEventListener: jest.fn(),
	abort: jest.fn(),
	readyState: 4,
	status: 200,
	statusText: 'OK',
	responseText: '{}',
	response: {},
} ) );

// Mock fetch
global.fetch = jest.fn( () =>
	Promise.resolve( {
		ok: true,
		status: 200,
		statusText: 'OK',
		json: () => Promise.resolve( {} ),
		text: () => Promise.resolve( '' ),
		blob: () => Promise.resolve( new Blob() ),
		arrayBuffer: () => Promise.resolve( new ArrayBuffer( 0 ) ),
	} )
);

// Mock URL and URLSearchParams
global.URL = class URL {
	constructor( url ) {
		this.href = url;
		this.origin = 'http://localhost';
		this.pathname = '/';
		this.search = '';
		this.hash = '';
	}
};

global.URLSearchParams = class URLSearchParams {
	constructor( params = '' ) {
		this.params = new Map();
		if ( typeof params === 'string' ) {
			params.split( '&' ).forEach( ( param ) => {
				const [ key, value ] = param.split( '=' );
				if ( key ) {
					this.params.set( key, value || '' );
				}
			} );
		}
	}

	get( key ) {
		return this.params.get( key );
	}
	set( key, value ) {
		this.params.set( key, value );
	}
	has( key ) {
		return this.params.has( key );
	}
	delete( key ) {
		this.params.delete( key );
	}
	toString() {
		return Array.from( this.params.entries() )
			.map( ( [ key, value ] ) => `${ key }=${ value }` )
			.join( '&' );
	}
};

// Mock IntersectionObserver
global.IntersectionObserver = class IntersectionObserver {
	constructor( callback ) {
		this.callback = callback;
	}

	observe() {}
	unobserve() {}
	disconnect() {}
};

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
	constructor( callback ) {
		this.callback = callback;
	}

	observe() {}
	unobserve() {}
	disconnect() {}
};

// Mock MutationObserver
global.MutationObserver = class MutationObserver {
	constructor( callback ) {
		this.callback = callback;
	}

	observe() {}
	disconnect() {}
	takeRecords() {
		return [];
	}
};

// Helper function to create mock DOM elements
global.createMockElement = ( tag, attributes = {}, children = [] ) => {
	const element = document.createElement( tag );

	Object.keys( attributes ).forEach( ( key ) => {
		if ( key === 'className' ) {
			element.className = attributes[ key ];
		} else if ( key === 'innerHTML' ) {
			element.innerHTML = attributes[ key ];
		} else if ( key === 'textContent' ) {
			element.textContent = attributes[ key ];
		} else {
			element.setAttribute( key, attributes[ key ] );
		}
	} );

	children.forEach( ( child ) => {
		if ( typeof child === 'string' ) {
			element.appendChild( document.createTextNode( child ) );
		} else {
			element.appendChild( child );
		}
	} );

	return element;
};

// Helper function to simulate events
global.simulateEvent = ( element, eventType, eventData = {} ) => {
	const event = new Event( eventType, { bubbles: true, cancelable: true } );
	Object.assign( event, eventData );
	element.dispatchEvent( event );
	return event;
};

// Helper function to wait for next tick
global.nextTick = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

// Helper function to wait for specified time
global.wait = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

// Setup global error handling for unhandled promise rejections in tests
process.on( 'unhandledRejection', ( reason, promise ) => {
	console.error( 'Unhandled Rejection at:', promise, 'reason:', reason ); // eslint-disable-line no-console
} );

// Reset mocks after each test
afterEach( () => {
	jest.clearAllMocks();

	// Clear localStorage and sessionStorage
	localStorage.clear();
	sessionStorage.clear();

	// Reset console mocks
	console.log.mockClear(); // eslint-disable-line no-console
	console.warn.mockClear(); // eslint-disable-line no-console
	console.error.mockClear(); // eslint-disable-line no-console

	// Clean up DOM
	document.body.innerHTML = '';
	document.head.innerHTML = '';
} );

// Global test utilities
global.testUtils = {
	// Create a mock WordPress REST API response
	createRestResponse: ( data, success = true, status = 200 ) => ( {
		success,
		status,
		data,
		headers: {
			'Content-Type': 'application/json',
		},
	} ),

	// Create a mock WordPress AJAX response
	createAjaxResponse: ( data, success = true ) => ( {
		success,
		data,
	} ),

	// Create a mock license response
	createLicenseResponse: ( overrides = {} ) => ( {
		license_key: 'test-license-key',
		status: 'valid',
		status_data: {
			success: true,
			license: 'valid',
			item_id: 480187,
			item_name: 'Popup Maker Pro',
			license_limit: 5,
			site_count: 1,
			expires: '2025-12-31 23:59:59',
			activations_left: 4,
			customer_name: 'Test Customer',
			customer_email: 'test@example.com',
		},
		is_active: true,
		is_pro_installed: false,
		is_pro_active: false,
		can_upgrade: true,
		connect_info: {
			url: 'https://connect.example.com/install',
			back_url: 'http://localhost/wp-admin/',
		},
		...overrides,
	} ),

	// Create a mock error response
	createErrorResponse: (
		code = 'unknown_error',
		message = 'An error occurred',
		status = 500
	) => ( {
		code,
		message,
		data: { status },
	} ),
};
