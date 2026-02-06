import reducer from '../reducer';
import { ACTION_TYPES, initialState, licenseStatusDefaults } from '../constants';
import { DispatchStatus } from '../../constants';

import type { State } from '../reducer';
import type { LicenseStatus } from '../types';

const validStatus: LicenseStatus = {
	success: true,
	license: 'valid',
	license_limit: 5,
	site_count: 1,
	expires: '2027-01-01',
	activations_left: 4,
	price_id: 1,
};

describe( 'license reducer', () => {
	it( 'returns initial state for unknown action', () => {
		const result = reducer( undefined, { type: 'UNKNOWN' } as any );
		expect( result ).toEqual( initialState );
	} );

	describe( 'ACTIVATE_LICENSE', () => {
		it( 'updates license status on activation', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.ACTIVATE_LICENSE,
				licenseStatus: validStatus,
			} as any );

			expect( state.license.status ).toEqual( validStatus );
		} );

		it( 'preserves existing license key', () => {
			const stateWithKey: State = {
				...initialState,
				license: {
					key: 'abc-123',
					status: licenseStatusDefaults,
				},
			};

			const state = reducer( stateWithKey, {
				type: ACTION_TYPES.ACTIVATE_LICENSE,
				licenseStatus: validStatus,
			} as any );

			expect( state.license.key ).toBe( 'abc-123' );
			expect( state.license.status ).toEqual( validStatus );
		} );
	} );

	describe( 'DEACTIVATE_LICENSE', () => {
		it( 'updates license status on deactivation', () => {
			const activeState: State = {
				...initialState,
				license: { key: 'abc', status: validStatus },
			};

			const deactivatedStatus: LicenseStatus = {
				...licenseStatusDefaults,
				license: 'deactivated',
			};

			const state = reducer( activeState, {
				type: ACTION_TYPES.DEACTIVATE_LICENSE,
				licenseStatus: deactivatedStatus,
			} as any );

			expect( state.license.status.license ).toBe( 'deactivated' );
			expect( state.license.key ).toBe( 'abc' );
		} );
	} );

	describe( 'CHECK_LICENSE_STATUS', () => {
		it( 'updates license status from check', () => {
			const expiredStatus: LicenseStatus = {
				...licenseStatusDefaults,
				license: 'expired',
				expires: '2020-01-01',
			};

			const state = reducer( initialState, {
				type: ACTION_TYPES.CHECK_LICENSE_STATUS,
				licenseStatus: expiredStatus,
			} as any );

			expect( state.license.status.license ).toBe( 'expired' );
		} );
	} );

	describe( 'CONNECT_SITE', () => {
		it( 'updates license status and stores connect info', () => {
			const connectInfo = {
				url: 'https://example.com/connect',
				back_url: 'https://example.com/back',
			};

			const state = reducer( initialState, {
				type: ACTION_TYPES.CONNECT_SITE,
				licenseStatus: validStatus,
				connectInfo,
			} as any );

			expect( state.license.status ).toEqual( validStatus );
			expect( state.connectInfo ).toEqual( connectInfo );
		} );

		it( 'preserves existing license key', () => {
			const stateWithKey: State = {
				...initialState,
				license: { key: 'existing-key', status: licenseStatusDefaults },
			};

			const state = reducer( stateWithKey, {
				type: ACTION_TYPES.CONNECT_SITE,
				licenseStatus: validStatus,
				connectInfo: { url: 'http://a.com', back_url: 'http://b.com' },
			} as any );

			expect( state.license.key ).toBe( 'existing-key' );
		} );
	} );

	describe( 'UPDATE_LICENSE_KEY', () => {
		it( 'updates both key and status', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.UPDATE_LICENSE_KEY,
				licenseKey: 'new-key-123',
				licenseStatus: validStatus,
			} as any );

			expect( state.license.key ).toBe( 'new-key-123' );
			expect( state.license.status ).toEqual( validStatus );
		} );

		it( 'replaces previous key', () => {
			const stateWithKey: State = {
				...initialState,
				license: { key: 'old-key', status: licenseStatusDefaults },
			};

			const state = reducer( stateWithKey, {
				type: ACTION_TYPES.UPDATE_LICENSE_KEY,
				licenseKey: 'updated-key',
				licenseStatus: licenseStatusDefaults,
			} as any );

			expect( state.license.key ).toBe( 'updated-key' );
		} );
	} );

	describe( 'REMOVE_LICENSE', () => {
		it( 'clears license key and status', () => {
			const activeState: State = {
				...initialState,
				license: { key: 'abc', status: validStatus },
			};

			const state = reducer( activeState, {
				type: ACTION_TYPES.REMOVE_LICENSE,
			} as any );

			expect( state.license.key ).toBe( '' );
			expect( state.license.status ).toEqual( {} );
		} );

		it( 'is idempotent on already-empty state', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.REMOVE_LICENSE,
			} as any );

			expect( state.license.key ).toBe( '' );
		} );
	} );

	describe( 'HYDRATE_LICENSE_DATA', () => {
		it( 'replaces entire license object', () => {
			const license = { key: 'hydrated-key', status: validStatus };

			const state = reducer( initialState, {
				type: ACTION_TYPES.HYDRATE_LICENSE_DATA,
				license,
			} as any );

			expect( state.license ).toEqual( license );
		} );
	} );

	describe( 'LICENSE_FETCH_ERROR', () => {
		it( 'stores error message', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.LICENSE_FETCH_ERROR,
				message: 'API unreachable',
			} as any );

			expect( state.error ).toBe( 'API unreachable' );
		} );

		it( 'overwrites previous error', () => {
			const stateWithError: State = {
				...initialState,
				error: 'Old error',
			};

			const state = reducer( stateWithError, {
				type: ACTION_TYPES.LICENSE_FETCH_ERROR,
				message: 'New error',
			} as any );

			expect( state.error ).toBe( 'New error' );
		} );
	} );

	describe( 'CHANGE_ACTION_STATUS', () => {
		it( 'stores dispatch status for an action', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.CHANGE_ACTION_STATUS,
				actionName: 'activateLicense',
				status: DispatchStatus.Resolving,
				message: '',
			} as any );

			expect( state.dispatchStatus?.activateLicense ).toEqual( {
				status: DispatchStatus.Resolving,
				error: '',
			} );
		} );

		it( 'stores error message on failure', () => {
			const state = reducer( initialState, {
				type: ACTION_TYPES.CHANGE_ACTION_STATUS,
				actionName: 'activateLicense',
				status: DispatchStatus.Error,
				message: 'Invalid key',
			} as any );

			expect( state.dispatchStatus?.activateLicense ).toEqual( {
				status: DispatchStatus.Error,
				error: 'Invalid key',
			} );
		} );
	} );
} );
