import type { TabComponent } from '@popup-maker/types';

export interface AppLayoutProps {
	className?: string;
	children: React.ReactNode;
}

export interface AppHeaderProps {
	title?: string;
	brandingUrl?: string;
	/** In-app tabs when no PHP nav tabs are localized. */
	tabs?: TabComponent[];
	currentTab?: string;
	onTabChange?: ( tabName: string ) => void;
	/** Active cross-page nav tab id from `layoutVars.navTabs`. */
	navTabId?: string;
	supportMenuItems?: SupportMenuItem[];
	showSupport?: boolean;
}

export interface AppContentProps {
	className?: string;
	children: React.ReactNode;
}

export interface SupportMenuItem {
	icon?: JSX.Element;
	label: string;
	href?: string;
	onClick?: () => void;
	target?: string;
	group?: string;
}
