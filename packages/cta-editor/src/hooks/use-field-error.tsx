import { callToActionStore, NOTICE_CONTEXT } from '@popup-maker/core-data';
import { store as noticesStore } from '@wordpress/notices';
import { useSelect } from '@wordpress/data';

/**
 * Hook to get the error message for a specific field.
 *
 * @param {string} fieldId The field ID to check for errors.
 * @return {string|null} The error message if the field has an error, null otherwise.
 */
export const useFieldError = ( fieldId: string ): string | null => {
	const ctaId = useSelect(
		( select ) => select( callToActionStore ).getEditorId(),
		[]
	);

	const error = useSelect(
		( select ) => {
			const notices = select( noticesStore ).getNotices( NOTICE_CONTEXT );
			const fieldNotice = notices.find(
				( notice ) =>
					notice.id === `field-error-${ ctaId || 'new' }-${ fieldId }`
			);
			return fieldNotice?.content || null;
		},
		[ ctaId, fieldId ]
	);

	return error;
};

/**
 * Hook to check if a tab has any field errors.
 *
 * @param {string} tabName The tab name to check for errors.
 * @return {boolean} True if the tab has any field errors, false otherwise.
 */
export const useTabHasError = ( tabName: string ): boolean => {
	const ctaId = useSelect(
		( select ) => select( callToActionStore ).getEditorId(),
		[]
	);

	return useSelect(
		( select ) => {
			const notices = select( noticesStore ).getNotices( NOTICE_CONTEXT );
			// For now, we'll need a field-to-tab mapping
			// This should eventually come from PHP via localization
			const fieldTabMap: Record< string, string > = {
				url: 'general',
				title: 'general',
				description: 'general',
				type: 'general',
				popup_id: 'targeting',
				// Add more mappings as needed
			};

			return notices.some( ( notice ) => {
				if (
					! notice.id?.startsWith(
						`field-error-${ ctaId || 'new' }-`
					)
				) {
					return false;
				}

				// Extract field name from notice ID
				const fieldMatch = notice.id.match( /field-error-\d+-(.+)$/ );
				const field = fieldMatch?.[ 1 ];

				return field && fieldTabMap[ field ] === tabName;
			} );
		},
		[ ctaId, tabName ]
	);
};

export default useFieldError;
