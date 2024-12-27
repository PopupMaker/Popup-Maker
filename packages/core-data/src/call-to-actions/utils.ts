import type { ApiCallToAction, CallToAction } from './types';

/**
 * Get resuorce path for various configs.
 *
 * @param {number} id Call to action id.
 * @return {string} Resulting resource path.
 */
export const getResourcePath = (
	id: CallToAction[ 'id' ] | undefined = undefined
): string => {
	const root = `popup-maker/v2/ctas`;

	return id ? `${ root }/${ id }` : root;
};

export const convertApiCallToAction = ( {
	title,
	// content,
	excerpt,
	...callToAction
}: ApiCallToAction ): CallToAction => {
	const newCallToAction = {
		...callToAction,
		title: typeof title === 'string' ? title : title.raw,
		// content: typeof content === 'string' ? content : content.raw,
		description: typeof excerpt === 'string' ? excerpt : excerpt.raw,
	};

	return newCallToAction;
};

export const convertCallToActionToApi = ( {
	description,
	...callToAction
}: Partial< CallToAction > ): Partial< ApiCallToAction > => {
	const newCallToAction = {
		...callToAction,
		excerpt: description,
	};

	return newCallToAction;
};
