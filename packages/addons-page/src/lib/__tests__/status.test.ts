import { getActionDescriptor, getStatusKey, matchesFilter } from '../status';

import type { Addon, AddonsPageConfig } from '../../types';

const config: AddonsPageConfig = {
	restPath: '/popup-maker/v2/addons',
	canActivate: true,
	proUpgradeUrl: 'https://example.com/pro',
	proPlusUpgradeUrl: 'https://example.com/pro-plus',
	supportUrl: 'https://example.com/support',
	pluginsUrl: '/wp-admin/plugins.php',
	categories: {},
	planTitle: '',
	planSubtitle: '',
	upgradeLabel: '',
	upgradeUrl: '',
	upgradeExternal: true,
};

const addon = ( overrides: Partial< Addon > = {} ): Addon => ( {
	slug: 'popup-maker-videos',
	name: 'Videos',
	description: 'Video popups.',
	longDescription: 'Video popups with playback controls.',
	features: [],
	category: 'content',
	image: '',
	isProPlus: false,
	isUpsell: true,
	pluginBasename: '',
	version: '',
	installed: false,
	activated: false,
	networkActivated: false,
	...overrides,
} );

describe( 'Core add-on status and actions', () => {
	it( 'offers only an external upgrade for an absent extension', () => {
		expect( getStatusKey( addon(), false ) ).toBe( 'locked' );
		expect( getActionDescriptor( addon(), false, config ) ).toMatchObject( {
			action: 'upgrade',
			label: 'Get Pro',
		} );
	} );

	it( 'uses the Pro+ upsell for a Pro+ catalog item', () => {
		expect(
			getActionDescriptor( addon( { isProPlus: true } ), false, config )
		).toMatchObject( {
			action: 'upgrade',
			label: 'Get Pro+',
		} );
	} );

	it( 'activates an already-installed extension instead of upselling it', () => {
		const installed = addon( {
			installed: true,
			pluginBasename: 'popup-maker-videos/popup-maker-videos.php',
		} );

		expect( getStatusKey( installed, false ) ).toBe( 'installed' );
		expect( getActionDescriptor( installed, false, config ) ).toMatchObject(
			{
				action: 'activate',
				label: 'Activate',
			}
		);
	} );

	it( 'deactivates an active extension and never manages network-active ones', () => {
		expect(
			getActionDescriptor(
				addon( { installed: true, activated: true } ),
				false,
				config
			)
		).toMatchObject( { action: 'deactivate' } );

		expect(
			getActionDescriptor(
				addon( {
					installed: true,
					activated: true,
					networkActivated: true,
				} ),
				false,
				config
			)
		).toMatchObject( { action: null, disabled: true } );
	} );

	it( 'groups every active state under the installed filter', () => {
		expect( matchesFilter( 'active', 'installed' ) ).toBe( true );
		expect( matchesFilter( 'network', 'installed' ) ).toBe( true );
		expect( matchesFilter( 'installed', 'installed' ) ).toBe( true );
		expect( matchesFilter( 'locked', 'installed' ) ).toBe( false );
	} );
} );
