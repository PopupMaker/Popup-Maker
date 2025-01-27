import type {
	CallToAction,
	ExportedCallToAction,
} from '@popup-maker/core-data';

const cleanCallToActionData = (
	callToAction: CallToAction< 'edit' >
): ExportedCallToAction => {
	const { id, slug, status, title, excerpt, settings } = callToAction;

	return {
		id,
		slug,
		status,
		title: title.raw,
		excerpt: excerpt.raw,
		settings,
	};
};

export { cleanCallToActionData };
