import type { TabComponent } from '@popup-maker/types';

export interface AppLayoutProps {
	className?: string;
	children: React.ReactNode;
}

export interface AppHeaderProps {
	title?: string;
	brandingUrl?: string;
	tabs?: TabComponent[];
	currentTab?: string;
	onTabChange?: ( tabName: string ) => void;
	supportMenuItems?: SupportMenuItem[];
	showSupport?: boolean;
	adminUrl?: string;
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
