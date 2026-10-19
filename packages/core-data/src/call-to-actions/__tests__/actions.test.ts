import actions from '../actions';
import { fetchFromApi } from '../../utils';

jest.mock( '../../utils', () => ( {
	fetchFromApi: jest.fn().mockResolvedValue( false ),
	getErrorMessage: ( error: unknown ) => String( error ),
} ) );

describe( 'CTA persistence defaults', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( window as any ).popupMakerCtaEditor = {
			cta_types: {
				offer: {
					key: 'offer',
					fields: {
						general: {
							amount: { type: 'number', std: '5' },
							download: { type: 'postselect', std: 12 },
						},
					},
				},
			},
		};
	} );

	afterEach( () => {
		delete ( window as any ).popupMakerCtaEditor;
	} );

	it( 'normalizes untouched declared defaults when creating a record', async () => {
		await actions.createCallToAction(
			{ settings: { type: 'offer' } } as any,
			false,
			false
		)( {
			dispatch: jest.fn(),
			registry: {},
		} as any );
		expect( fetchFromApi ).toHaveBeenCalledWith( 'ctas?context=edit', {
			method: 'POST',
			data: { settings: { type: 'offer', amount: 5, download: [ 12 ] } },
		} );
	} );

	it( 'does not inject schema defaults into a partial update', async () => {
		const partial = { id: 42, settings: { type: 'offer', prefix: 'NEW' } };
		await actions.updateCallToAction(
			partial as any,
			false,
			false
		)( {
			dispatch: jest.fn(),
			registry: {},
			select: {
				getCallToAction: () => ( {
					id: 42,
					settings: { type: 'offer', amount: 25 },
				} ),
			},
		} as any );
		expect( fetchFromApi ).toHaveBeenCalledWith( 'ctas/42', {
			method: 'POST',
			data: partial,
		} );
	} );
} );
