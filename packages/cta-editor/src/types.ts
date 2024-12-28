export type TabComponent = {
	name: string;
	title: string | JSX.Element;
	badge?: string | JSX.Element;
	className: string;
	pageTitle: string;
	heading: string;
	comp?: () => JSX.Element;
	onClick?: () => void | false;
};
