import {
	help,
	lifesaver,
	login,
	pages,
	people,
	wordpress,
} from '@wordpress/icons';

const SUPPORT_MENU_ICONS: Record< string, JSX.Element > = {
	pages,
	people,
	login,
	lifesaver,
	help,
	wordpress,
};

/**
 * Resolve a localized icon slug to a menu icon component.
 * @param slug
 */
export const resolveSupportMenuIcon = (
	slug?: string
): JSX.Element | undefined => {
	if ( ! slug ) {
		return undefined;
	}

	return SUPPORT_MENU_ICONS[ slug ];
};
