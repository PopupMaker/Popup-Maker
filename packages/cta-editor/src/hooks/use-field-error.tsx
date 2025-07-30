import { callToActionStore, NOTICE_CONTEXT } from '@popup-maker/core-data';
import { store as noticesStore } from '@wordpress/notices';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

import useFields from './use-fields';

/**
 * Hook to get the error message and clear function for a specific field.
 *
 * @param {string} fieldId The field ID to check for errors.
 * @return {Object} Object containing error message and clearError function.
 */
export const useFieldError = (
	fieldId: string
): {
	error: string | null;
	clearError: () => void;
} => {
	const ctaId = useSelect(
		( select ) => select( callToActionStore ).getEditorId(),
		[]
	);
	const { removeNotice } = useDispatch( noticesStore );

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

	const clearError = useCallback( () => {
		if ( ctaId !== undefined ) {
			removeNotice(
				`field-error-${ ctaId || 'new' }-${ fieldId }`,
				NOTICE_CONTEXT
			);
		}
	}, [ ctaId, fieldId, removeNotice ] );

	return { error, clearError };
};

/**
 * Hook to get tab error information and clear function.
 *
 * @param {string} tabName The tab name to check for errors.
 * @return {Object} Object containing hasErrors, errorCount, and clearTabErrors function.
 */
export const useTabErrors = (
	tabName: string
): {
	hasErrors: boolean;
	errorCount: number;
	clearTabErrors: () => void;
} => {
	const ctaId = useSelect(
		( select ) => select( callToActionStore ).getEditorId(),
		[]
	);
	const { removeNotice } = useDispatch( noticesStore );
	const { getTabFields } = useFields();

	const { hasErrors, errorCount, errorIds } = useSelect(
		( select ) => {
			const notices = select( noticesStore ).getNotices( NOTICE_CONTEXT );
			const tabFields = getTabFields( tabName );
			const fieldIds = new Set( tabFields.map( ( f ) => f.id ) );

			const tabErrors = notices.filter( ( notice ) => {
				if (
					! notice.id?.startsWith(
						`field-error-${ ctaId || 'new' }-`
					)
				) {
					return false;
				}
				const fieldMatch = notice.id.match(
					/field-error-(?:\d+|new)-(.+)$/
				);
				const fieldId = fieldMatch?.[ 1 ];
				return fieldId && fieldIds.has( fieldId );
			} );

			return {
				hasErrors: tabErrors.length > 0,
				errorCount: tabErrors.length,
				errorIds: tabErrors.map( ( n ) => n.id ),
			};
		},
		[ ctaId, tabName, getTabFields ]
	);

	const clearTabErrors = useCallback( () => {
		errorIds.forEach( ( id ) => removeNotice( id, NOTICE_CONTEXT ) );
	}, [ errorIds, removeNotice ] );

	return { hasErrors, errorCount, clearTabErrors };
};

/**
 * Legacy hook for backward compatibility.
 *
 * @param {string} tabName The tab name to check for errors.
 * @return {boolean} True if the tab has errors, false otherwise.
 */
export const useTabHasError = ( tabName: string ): boolean => {
	const { hasErrors } = useTabErrors( tabName );
	return hasErrors;
};

/**
 * Hook to manage all field errors across the CTA.
 *
 * @return {Object} Object containing errors map, hasAnyError flag, and clear functions.
 */
export const useAllFieldErrors = (): {
	errors: Record< string, string >;
	hasAnyError: boolean;
	clearAllErrors: () => void;
	clearFieldError: ( fieldId: string ) => void;
} => {
	const ctaId = useSelect(
		( select ) => select( callToActionStore ).getEditorId(),
		[]
	);
	const { removeNotice } = useDispatch( noticesStore );

	const { errors, errorIds } = useSelect(
		( select ) => {
			const notices = select( noticesStore ).getNotices( NOTICE_CONTEXT );
			const fieldErrors: Record< string, string > = {};
			const ids: string[] = [];

			notices.forEach( ( notice ) => {
				const match = notice.id?.match(
					new RegExp( `^field-error-${ ctaId || 'new' }-(.+)$` )
				);
				if ( match ) {
					fieldErrors[ match[ 1 ] ] = notice.content as string;
					ids.push( notice.id );
				}
			} );

			return { errors: fieldErrors, errorIds: ids };
		},
		[ ctaId ]
	);

	const clearAllErrors = useCallback( () => {
		errorIds.forEach( ( id ) => removeNotice( id, NOTICE_CONTEXT ) );
	}, [ errorIds, removeNotice ] );

	const clearFieldError = useCallback(
		( fieldId: string ) => {
			if ( ctaId !== undefined ) {
				removeNotice(
					`field-error-${ ctaId || 'new' }-${ fieldId }`,
					NOTICE_CONTEXT
				);
			}
		},
		[ ctaId, removeNotice ]
	);

	return {
		errors,
		hasAnyError: Object.keys( errors ).length > 0,
		clearAllErrors,
		clearFieldError,
	};
};

export default useFieldError;
