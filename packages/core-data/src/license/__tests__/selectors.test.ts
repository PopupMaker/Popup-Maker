jest.mock( '@wordpress/data', () => ( {
	createSelector: ( selector: Function ) => selector,
} ) );

import {
	getLicenseData,
	getLicenseKey,
	getLicenseStatus,
	getConnectInfo,
	getDispatchStatus,
	isDispatching,
	hasDispatched,
	getDispatchError,
} from '../selectors';
import { initialState, licenseStatusDefaults } from '../constants';
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

const stateWith = ( overrides: Partial< State > ): State => ( {
	...initialState,
	...overrides,
} );

describe( 'license selectors', () => {
	describe( 'getLicenseData', () => {
		it( 'returns the full license object', () => {
			const result = getLicenseData( initialState );
			expect( result ).toEqual( initialState.license );
		} );

		it( 'returns updated license', () => {
			const state = stateWith( {
				license: { key: 'test', status: validStatus },
			} );
			expect( getLicenseData( state ).key ).toBe( 'test' );
		} );
	} );

	describe( 'getLicenseKey', () => {
		it( 'returns empty string from initial state', () => {
			expect( getLicenseKey( initialState ) ).toBe( '' );
		} );

		it( 'returns the license key', () => {
			const state = stateWith( {
				license: { key: 'my-key', status: licenseStatusDefaults },
			} );
			expect( getLicenseKey( state ) ).toBe( 'my-key' );
		} );
	} );

	describe( 'getLicenseStatus', () => {
		it( 'returns defaults merged with status', () => {
			const result = getLicenseStatus( initialState );
			expect( result ).toEqual( licenseStatusDefaults );
		} );

		it( 'merges custom status with defaults', () => {
			const partialStatus = { license: 'valid', success: true } as any;
			const state = stateWith( {
				license: { key: 'x', status: partialStatus },
			} );

			const result = getLicenseStatus( state );
			expect( result.license ).toBe( 'valid' );
			expect( result.success ).toBe( true );
			// Defaults fill in the rest.
			expect( result.license_limit ).toBe( 1 );
		} );
	} );

	describe( 'getConnectInfo', () => {
		it( 'returns undefined when no connect info', () => {
			expect( getConnectInfo( initialState ) ).toBeUndefined();
		} );

		it( 'returns connect info when set', () => {
			const state = stateWith( {
				connectInfo: {
					url: 'https://example.com',
					back_url: 'https://example.com/back',
				},
			} );

			expect( getConnectInfo( state )?.url ).toBe(
				'https://example.com'
			);
		} );
	} );

	describe( 'getDispatchStatus', () => {
		it( 'returns undefined when no dispatch status', () => {
			expect(
				getDispatchStatus( initialState, 'activateLicense' as any )
			).toBeUndefined();
		} );

		it( 'returns the status string for a dispatched action', () => {
			const state = stateWith( {
				dispatchStatus: {
					activateLicense: {
						status: DispatchStatus.Resolving,
						error: '',
					},
				},
			} as any );

			expect(
				getDispatchStatus( state, 'activateLicense' as any )
			).toBe( DispatchStatus.Resolving );
		} );
	} );

	describe( 'isDispatching', () => {
		it( 'returns false when no dispatch status', () => {
			expect(
				isDispatching( initialState, 'activateLicense' as any )
			).toBe( false );
		} );

		it( 'returns true when action is resolving', () => {
			const state = stateWith( {
				dispatchStatus: {
					activateLicense: {
						status: DispatchStatus.Resolving,
						error: '',
					},
				},
			} as any );

			expect(
				isDispatching( state, 'activateLicense' as any )
			).toBe( true );
		} );

		it( 'returns false when action has completed', () => {
			const state = stateWith( {
				dispatchStatus: {
					activateLicense: {
						status: DispatchStatus.Success,
						error: '',
					},
				},
			} as any );

			expect(
				isDispatching( state, 'activateLicense' as any )
			).toBe( false );
		} );

		it( 'handles array of action names', () => {
			const state = stateWith( {
				dispatchStatus: {
					activateLicense: {
						status: DispatchStatus.Idle,
						error: '',
					},
					deactivateLicense: {
						status: DispatchStatus.Resolving,
						error: '',
					},
				},
			} as any );

			expect(
				isDispatching( state, [
					'activateLicense',
					'deactivateLicense',
				] as any )
			).toBe( true );
		} );

		it( 'returns false when no actions in array are resolving', () => {
			const state = stateWith( {
				dispatchStatus: {
					activateLicense: {
						status: DispatchStatus.Success,
						error: '',
					},
					deactivateLicense: {
						status: DispatchStatus.Error,
						error: 'fail',
					},
				},
			} as any );

			expect(
				isDispatching( state, [
					'activateLicense',
					'deactivateLicense',
				] as any )
			).toBe( false );
		} );
	} );

	describe( 'hasDispatched', () => {
		it( 'returns false when no dispatch status', () => {
			expect(
				hasDispatched( initialState, 'activateLicense' as any )
			).toBe( false );
		} );

		it( 'returns true when action succeeded', () => {
			const state = stateWith( {
				dispatchStatus: {
					activateLicense: {
						status: DispatchStatus.Success,
						error: '',
					},
				},
			} as any );

			expect(
				hasDispatched( state, 'activateLicense' as any )
			).toBe( true );
		} );

		it( 'returns true when action errored', () => {
			const state = stateWith( {
				dispatchStatus: {
					activateLicense: {
						status: DispatchStatus.Error,
						error: 'nope',
					},
				},
			} as any );

			expect(
				hasDispatched( state, 'activateLicense' as any )
			).toBe( true );
		} );

		it( 'returns false when action is still resolving', () => {
			const state = stateWith( {
				dispatchStatus: {
					activateLicense: {
						status: DispatchStatus.Resolving,
						error: '',
					},
				},
			} as any );

			expect(
				hasDispatched( state, 'activateLicense' as any )
			).toBe( false );
		} );
	} );

	describe( 'getDispatchError', () => {
		it( 'returns undefined when no dispatch status', () => {
			expect(
				getDispatchError( initialState, 'activateLicense' as any )
			).toBeUndefined();
		} );

		it( 'returns error string when present', () => {
			const state = stateWith( {
				dispatchStatus: {
					activateLicense: {
						status: DispatchStatus.Error,
						error: 'Invalid license key',
					},
				},
			} as any );

			expect(
				getDispatchError( state, 'activateLicense' as any )
			).toBe( 'Invalid license key' );
		} );
	} );
} );
