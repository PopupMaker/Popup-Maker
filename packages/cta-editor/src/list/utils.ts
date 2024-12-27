import type { CallToAction } from '@popup-maker/core-data';

const cleanCallToActionData = ( callToAction: CallToAction ) => {
	const { id, slug, status, title, description, settings } = callToAction;

	return {
		id,
		slug,
		status,
		title,
		description,
		settings,
	};
};

export { cleanCallToActionData };
