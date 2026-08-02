export interface Addon {
	slug: string;
	name: string;
	description: string;
	longDescription: string;
	features: string[];
	category: string;
	image: string;
	isProPlus: boolean;
	isUpsell: boolean;
	pluginBasename: string;
	version: string;
	installed: boolean;
	activated: boolean;
	networkActivated: boolean;
}

export type AddonStatus =
	| 'working'
	| 'network'
	| 'active'
	| 'locked'
	| 'installed'
	| 'unavailable';

export type AddonAction = 'activate' | 'deactivate' | 'upgrade';

export type FilterKey = 'all' | 'active' | 'installed' | 'locked';

export type PlanState = 'default' | 'warning';

export interface AddonsPageConfig {
	restPath: string;
	canActivate: boolean;
	proUpgradeUrl: string;
	proPlusUpgradeUrl: string;
	supportUrl: string;
	pluginsUrl: string;
	categories: Record< string, string >;
	planLogoUrl: string;
	planState: PlanState;
	planStatus: string;
	planTitle: string;
	planSubtitle: string;
	upgradeLabel: string;
	upgradeUrl: string;
	upgradeExternal: boolean;
}

declare global {
	interface Window {
		popupMakerAddonsPage?: Partial< AddonsPageConfig >;
	}
}

export const getConfig = (): AddonsPageConfig => {
	const config = window.popupMakerAddonsPage ?? {};

	return {
		restPath: config.restPath ?? '/popup-maker/v2/addons',
		canActivate: Boolean( config.canActivate ),
		proUpgradeUrl:
			config.proUpgradeUrl ?? 'https://wppopupmaker.com/pricing/',
		proPlusUpgradeUrl:
			config.proPlusUpgradeUrl ?? 'https://wppopupmaker.com/pricing/',
		supportUrl: config.supportUrl ?? 'https://wppopupmaker.com/support/',
		pluginsUrl: config.pluginsUrl ?? '',
		categories: config.categories ?? {},
		planLogoUrl: config.planLogoUrl ?? '',
		planState: config.planState ?? 'default',
		planStatus: config.planStatus ?? '',
		planTitle: config.planTitle ?? '',
		planSubtitle: config.planSubtitle ?? '',
		upgradeLabel: config.upgradeLabel ?? '',
		upgradeUrl: config.upgradeUrl ?? '',
		upgradeExternal: Boolean( config.upgradeExternal ),
	};
};
