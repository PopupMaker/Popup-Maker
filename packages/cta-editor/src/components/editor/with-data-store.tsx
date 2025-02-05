import { __ } from '@popup-maker/i18n';
import {
	defaultCtaValues,
	callToActionStore,
	getErrorMessage,
	DispatchStatus,
} from '@popup-maker/core-data';
import { Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState, useRef } from '@wordpress/element';

import type { ComponentType } from 'react';
import type { EditorId, EditableCta } from '@popup-maker/core-data';
import type { BaseEditorProps, EditorTab } from './types';

type Editable = EditableCta;

/**
 * Higher Order Component (HOC) that provides data store integration for Call To Action editors.
 * This version has more robust error handling and state management.
 *
 * The props for the EditorWithDataStore component.
 *
 * Does not implement saving by default. Dispatch createCallToAction or updateCallToAction from the data store.
 *
 * Omits the values & onChange props because they are set by the data store.
 *
 * Consumers should use onSave in place of onChange & get values from the data store.`
 */
export interface EditorWithDataStoreProps
	extends Omit< BaseEditorProps, 'values' | 'onChange' > {
	/**
	 * The editor id.
	 */
	id: NonNullable< EditorId >;

	/**
	 * The default values for the editor, only applicable when creating a new call to action.
	 */
	defaultValues?: Editable | undefined;

	/**
	 * Callback to run when the CallToAction is saved.
	 *
	 * @param values The values saved. Data store already saved the values to the database.
	 */
	onSave?: ( values: Editable ) => void;
}

/**
 * Type definition for error notices that can include tab and field-specific information
 */
type ErrorNotice = {
	message: string;
	tabName?: string;
	field?: string;
	[ key: string ]: any;
};

/**
 * Wrap the editor with the data store.
 *
 * @param {ComponentType<EditorWithDataStoreProps>} WrappedComponent The component to wrap.
 *
 * @return {Function} The wrapped component.
 */
export const withDataStore = (
	WrappedComponent: ComponentType< BaseEditorProps >
) => {
	return function DataStoreWrappedEditor( {
		id,
		defaultValues = defaultCtaValues,
		onSave,
		...componentProps
	}: EditorWithDataStoreProps ) {
		/**
		 * State for tracking error messages and saving status
		 */
		const [ errorMessage, setErrorMessage ] = useState<
			string | ErrorNotice
		>();

		/**
		 * State for tracking save attempts and preventing duplicate saves
		 *
		 * TODO Consider putthing this in a hook or data s.
		 */
		const [ triedSaving, setTriedSaving ] = useState< boolean >( false );
		const saveHandledRef = useRef( false );

		const {
			values = defaultValues,
			isEditorActive,
			isSaving,
			savedSuccessfully,
			hasError,
			error,
			getEditorValues,
		} = useSelect(
			( select ) => {
				const store = select( callToActionStore );

				const resolutionState =
					store.getResolutionState( 'createCallToAction' ) ||
					store.getResolutionState( 'updateCallToAction' );

				const resolutionError = store.getResolutionError( id );

				return {
					values: store.getEditedCallToAction( id ),
					isEditorActive: store.isEditorActive(),
					isSaving: store.isResolving( 'updateCallToAction' ),
					getEditorValues: store.getEditedCallToAction,
					savedSuccessfully:
						resolutionState?.status === DispatchStatus.Success,
					hasError: !! resolutionError,
					error: resolutionError,
				};
			},
			[ id ]
		);

		const { editRecord, resetRecordEdits } =
			useDispatch( callToActionStore );

		/**
		 * Listen for errors and set the error message.
		 */
		useEffect( () => {
			if ( hasError ) {
				setErrorMessage( error );
			}
		}, [ hasError, error ] );

		/**
		 * Save the CallToAction when the editor is saved.
		 *
		 * Also clear the error message when the editor is saved.
		 */
		useEffect( () => {
			if ( ! triedSaving ) {
				if ( isSaving ) {
					setTriedSaving( true );
					saveHandledRef.current = false;
				}
				return;
			}

			if ( savedSuccessfully && ! saveHandledRef.current ) {
				saveHandledRef.current = true;
				setErrorMessage( undefined );
				setTriedSaving( false );
				// Get the latest CTA from the store after save
				const latestCta = getEditorValues( values.id );
				onSave?.( latestCta || values );
			}
		}, [
			onSave,
			triedSaving,
			savedSuccessfully,
			getEditorValues,
			values,
			isSaving,
		] );

		const { id: valuesId } = values;

		const hasEdits = useSelect(
			( select ) => select( callToActionStore ).hasEdits( valuesId ),
			[ valuesId ]
		);

		// Set up confirm to close dialogue as well as prevent changing pages in the brower while hasEdits.
		useEffect(
			() => {
				// On beforeunload event, confirm loss of unsaved changes.
				const confirmLossOfUnsavedChanges = (
					event: BeforeUnloadEvent
				) => {
					if ( hasEdits ) {
						if (
							// eslint-disable-next-line no-alert, no-restricted-globals
							window.confirm(
								__(
									'Changes you made may not be saved.',
									'popup-maker'
								)
							)
						) {
							resetRecordEdits( valuesId );
						} else {
							event.preventDefault();
							return false;
						}
					}

					return true;
				};

				window.addEventListener(
					'beforeunload',
					confirmLossOfUnsavedChanges
				);

				return () => {
					window.removeEventListener(
						'beforeunload',
						confirmLossOfUnsavedChanges
					);
				};
			},
			// eslint-disable-next-line react-hooks/exhaustive-deps
			[ hasEdits, valuesId ]
		);

		// When no values, don't show the editor.
		if ( ! values ) {
			return (
				<>
					{ __(
						'Editor requires a valid call to action.',
						'popup-maker'
					) }
				</>
			);
		}

		if ( ! isEditorActive ) {
			return null;
		}

		/**
		 * Filter the tabs to add an error class to the tab that has an error.
		 *
		 * @param {EditorTab[]} tabs The tabs.
		 */
		const tabsFilter = ( tabs: EditorTab[] ) => {
			/**
			 * Filter the tabs to add an error class to the tab that has an error.
			 */
			const _tabs = tabs.map( ( tab ) => {
				if (
					typeof tab !== 'object' ||
					typeof errorMessage !== 'object'
				) {
					return tab;
				}

				if ( errorMessage?.tabName === tab.name ) {
					return {
						...tab,
						className: tab.className
							? tab.className + ' error'
							: 'error',
					};
				}

				return tab;
			} );

			/**
			 * If a tabsFilter is provided, use it to filter the tabs.
			 */
			return componentProps.tabsFilter
				? componentProps.tabsFilter( _tabs )
				: _tabs;
		};

		/**
		 * Render the error message.
		 */
		const ErrorMessage = () => (
			<>
				{ errorMessage && (
					<Notice
						status="error"
						className="call-to-action-editor-error"
						onDismiss={ () => {
							setErrorMessage( undefined );
						} }
					>
						{ getErrorMessage( errorMessage ) }
					</Notice>
				) }
			</>
		);

		const editorValues = values;

		return (
			<WrappedComponent
				{ ...componentProps }
				values={ editorValues }
				onChange={ ( newValues ) => {
					editRecord( values.id, newValues );
				} }
				tabsFilter={ tabsFilter }
				beforeTabs={
					<>
						<ErrorMessage />
						{ componentProps.beforeTabs }
					</>
				}
			/>
		);
	};
};

export default withDataStore;
