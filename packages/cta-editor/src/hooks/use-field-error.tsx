import { callToActionStore, NOTICE_CONTEXT } from '@popup-maker/core-data';
import { store as noticesStore } from '@wordpress/notices';
import { useSelect } from '@wordpress/data';

import useFields from './use-fields';

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

	const { getTabFields } = useFields();

	return useSelect(
		( select ) => {
			const notices = select( noticesStore ).getNotices( NOTICE_CONTEXT );

			// Build dynamic field-to-tab mapping from the actual field definitions
			const tabFields = getTabFields( tabName );
			const fieldTabMap: Record< string, string > = {};

			// Map each field ID to this tab
			tabFields.forEach( ( field ) => {
				fieldTabMap[ field.id ] = tabName;
			} );

			// Also check other tabs to build complete mapping
			const allTabs = [ 'general', 'targeting', 'settings' ]; // Add other tab names as needed
			allTabs.forEach( ( tab ) => {
				if ( tab !== tabName ) {
					const otherTabFields = getTabFields( tab );
					otherTabFields.forEach( ( field ) => {
						fieldTabMap[ field.id ] = tab;
					} );
				}
			} );

			return notices.some( ( notice ) => {
				if (
					! notice.id?.startsWith(
						`field-error-${ ctaId || 'new' }-`
					)
				) {
					return false;
				}

				// Extract field name from notice ID
				// Pattern handles both numeric IDs and 'new' for new CTAs
				const fieldMatch = notice.id.match(
					/field-error-(?:\d+|new)-(.+)$/
				);
				const field = fieldMatch?.[ 1 ];

				return field && fieldTabMap[ field ] === tabName;
			} );
		},
		[ ctaId, tabName, getTabFields ]
	);
};

export default useFieldError;
