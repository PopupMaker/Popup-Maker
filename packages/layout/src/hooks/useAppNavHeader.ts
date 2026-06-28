import { useCallback, useMemo } from '@wordpress/element';

import {
	getNavTabs,
	mapNavTabsForHeader,
	navigateNavTab,
} from '../utils/layoutVars';

/**
 * Resolve AppHeader tabs from PHP-localized layout nav.
 * @param navTabId Active nav tab id for the current admin page.
 */
export const useAppNavHeader = ( navTabId: string ) => {
	const navTabs = useMemo( () => getNavTabs(), [] );
	const usesLayoutNav = navTabs.length > 0;
	const tabs = useMemo( () => mapNavTabsForHeader(), [] );

	const onTabChange = useCallback( ( tabName: string ) => {
		navigateNavTab( tabName );
	}, [] );

	return {
		tabs,
		currentTab: navTabId,
		onTabChange,
		usesLayoutNav,
	};
};
