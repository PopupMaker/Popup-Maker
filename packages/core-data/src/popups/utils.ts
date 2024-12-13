import type { ApiPopup, Popup } from './types';

/**
 * Get resuource path for various configs.
 *
 * @param {number} id Popup id.
 * @return {string} Resulting resource path.
 */
export const getResourcePath = (
	id: Popup[ 'id' ] | undefined = undefined
): string => {
	const root = `popup-maker/v2/popups`;

	return id ? `${ root }/${ id }` : root;
};

export const convertApiPopup = ( {
	title,
	// content,
	excerpt,
	...popup
}: ApiPopup ): Popup => {
	const newPopup = {
		...popup,
		title: typeof title === 'string' ? title : title.raw,
		// content: typeof content === 'string' ? content : content.raw,
		description: typeof excerpt === 'string' ? excerpt : excerpt.raw,
	};

	return newPopup;
};

export const convertPopupToApi = ( {
	description,
	...popup
}: Partial< Popup > ): Partial< ApiPopup > => {
	const newPopup = {
		...popup,
		excerpt: description,
	};

	return newPopup;
};
