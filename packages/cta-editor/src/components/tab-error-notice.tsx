import React, { useEffect, useState, useRef } from 'react';
import { Notice } from '@wordpress/components';
import { __ } from '@popup-maker/i18n';
import { useSelect } from '@wordpress/data';
import { DEBUG_MODE } from '../index';
import { useTabErrors } from '../hooks';
import { callToActionStore } from '@popup-maker/core-data';

interface TabErrorNoticeProps {
	tabName: string;
	message?: string;
}

/**
 * A notice component that shows tab errors and auto-dismisses after a delay.
 *
 * @param {TabErrorNoticeProps} props         - The component props.
 * @param {string}              props.tabName - The name of the tab to check for errors.
 * @param {string}              props.message - The error message to display.
 * @return {React.ReactNode} The rendered notice or null.
 */
export const TabErrorNotice: React.FC< TabErrorNoticeProps > = ( {
	tabName,
	message = __( 'Please fix the errors below.', 'popup-maker' ),
} ) => {
	const { hasErrors: hasError } = useTabErrors( tabName );
	const [ showNotice, setShowNotice ] = useState( false );
	const timerRef = useRef< NodeJS.Timeout | null >( null );
	const lastHasError = useRef( false );
	const wasSaving = useRef( false );

	// Track save state to detect save attempts
	const isSaving = useSelect(
		( select ) =>
			select( callToActionStore ).isResolving( 'updateCallToAction' ),
		[]
	);

	useEffect( () => {
		// Clear any existing timer
		if ( timerRef.current ) {
			clearTimeout( timerRef.current );
			timerRef.current = null;
		}

		// Detect when errors appear or when save completes with errors
		const errorsJustAppeared = hasError && ! lastHasError.current;
		const saveJustCompleted = wasSaving.current && ! isSaving;

		if ( hasError && ( errorsJustAppeared || saveJustCompleted ) ) {
			// Show the notice
			setShowNotice( true );

			// Set timer to hide it
			if ( ! DEBUG_MODE ) {
				timerRef.current = setTimeout( () => {
					setShowNotice( false );
				}, 3000 );
			}
		} else if ( ! hasError ) {
			// No errors - hide immediately
			setShowNotice( false );
		}

		// Update refs for next render
		lastHasError.current = hasError;
		wasSaving.current = isSaving;

		// Cleanup timer on unmount
		return () => {
			if ( timerRef.current ) {
				clearTimeout( timerRef.current );
			}
		};
	}, [ hasError, isSaving ] );

	// Only render if we should show the notice
	if ( ! showNotice ) {
		return null;
	}

	return (
		<Notice status="error" isDismissible={ false }>
			{ message }
		</Notice>
	);
};

export default TabErrorNotice;
