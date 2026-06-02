import { applyFilters } from '@wordpress/hooks';
import type {
	LayoutNavTab,
	LayoutSupportMenuItem,
	PopupMakerLayoutVars,
	TabComponent,
} from '@popup-maker/types';

import type { SupportMenuItem } from '../types';
import { resolveSupportMenuIcon } from './supportMenuIcons';

/** JS filter — mirror of PHP `popup_maker/layout_vars`. */
export const LAYOUT_VARS_FILTER = 'popupMaker.layout.vars';

/** PHP filter that localizes into `globalVars.layoutVars`. */
export const LAYOUT_VARS_PHP_FILTER = 'popup_maker/layout_vars';

const EMPTY_LAYOUT_VARS: PopupMakerLayoutVars = {
	navTabs: [],
	supportMenuItems: [],
	showSupport: true,
};

/**
 * Read layout vars localized from PHP before JS filters run.
 */
export const getLocalizedLayoutVars = (): PopupMakerLayoutVars => {
	if ( typeof window === 'undefined' ) {
		return { ...EMPTY_LAYOUT_VARS };
	}

	const vars = window.popupMaker?.globalVars?.layoutVars;

	if ( ! vars || typeof vars !== 'object' ) {
		return { ...EMPTY_LAYOUT_VARS };
	}

	return {
		navTabs: Array.isArray( vars.navTabs )
			? vars.navTabs
			: Array.isArray( ( vars as { shellTabs?: LayoutNavTab[] } ).shellTabs )
				? ( vars as { shellTabs: LayoutNavTab[] } ).shellTabs
				: [],
		supportMenuItems: Array.isArray( vars.supportMenuItems )
			? vars.supportMenuItems
			: [],
		showSupport: vars.showSupport ?? true,
	};
};

/**
 * Resolve layout vars after the JS filter pass.
 */
export const resolveLayoutVars = (): PopupMakerLayoutVars => {
	const localized = getLocalizedLayoutVars();

	return applyFilters(
		LAYOUT_VARS_FILTER,
		localized
	) as PopupMakerLayoutVars;
};

/**
 * Cross-page header nav tabs from layout vars.
 */
export const getNavTabs = (): LayoutNavTab[] =>
	resolveLayoutVars().navTabs ?? [];

/**
 * Map nav tabs into AppHeader's ControlledTabPanel shape.
 */
export const mapNavTabsForHeader = (): TabComponent[] =>
	getNavTabs().map( ( tab ) => ( {
		name: tab.id,
		title: tab.title,
		className: tab.id,
		pageTitle: tab.title,
		heading: tab.title,
		href: tab.href,
	} ) );

/**
 * Navigate to a nav tab when it declares an href.
 * @param tabName Nav tab id.
 */
export const navigateNavTab = ( tabName: string ): void => {
	const tab = getNavTabs().find( ( item ) => item.id === tabName );
	if ( tab?.href ) {
		window.location.assign( tab.href );
	}
};

/**
 * Map PHP-localized support items into AppHeader menu rows.
 * @param items
 */
export const mapLayoutSupportMenuItems = (
	items: LayoutSupportMenuItem[] = []
): SupportMenuItem[] =>
	items
		.filter( ( item ) => item?.label )
		.map( ( item ) => ( {
			label: item.label,
			href: item.href,
			target: item.target,
			group: item.group || 'primary',
			icon: resolveSupportMenuIcon( item.icon ),
		} ) );

/**
 * Support menu items after layout vars + icon resolution.
 */
export const getSupportMenuItems = (): SupportMenuItem[] =>
	mapLayoutSupportMenuItems(
		resolveLayoutVars().supportMenuItems ?? []
	);

/**
 * Whether the support dropdown should render.
 */
export const getShowSupportMenu = (): boolean =>
	resolveLayoutVars().showSupport ?? true;
