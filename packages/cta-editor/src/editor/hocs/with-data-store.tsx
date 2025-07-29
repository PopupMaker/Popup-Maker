import { __ } from '@popup-maker/i18n';
import {
	defaultCtaValues,
	callToActionStore,
	DispatchStatus,
	NOTICE_CONTEXT,
} from '@popup-maker/core-data';
import { Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState, useRef, useMemo } from '@wordpress/element';
import { DebugNotices } from '../../components';

import type { ComponentType } from 'react';
import type { EditorId, EditableCta } from '@popup-maker/core-data';
import type { BaseEditorProps } from '../../types';

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
	 *
	 * Does not use PartialEditableCta because it requires an id.
	 */
	defaultValues?: Partial< EditableCta > | undefined;

	/**
	 * Callback to run when the CallToAction is saved.
	 *
	 * @param values The values saved. Data store already saved the values to the database.
	 */
	onSave?: ( values: Editable ) => void;
}

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
		 * State for tracking save attempts and preventing duplicate saves
		 *
		 * TODO Consider putthing this in a hook or data s.
		 */
		const [ triedSaving, setTriedSaving ] = useState< boolean >( false );
		const saveHandledRef = useRef( false );

		const fullDefaultValues = useMemo( () => {
			return {
				...defaultCtaValues,
				...defaultValues,
				id,
			};
		}, [ defaultValues, id ] );

		const {
			values = fullDefaultValues,
			isEditorActive,
			isSaving,
			savedSuccessfully,
			getEditorValues,
		} = useSelect(
			( select ) => {
				const store = select( callToActionStore );

				const resolutionState =
					store.getResolutionState( 'createCallToAction' ) ||
					store.getResolutionState( 'updateCallToAction' );

				return {
					values: store.getEditedCallToAction( id ),
					isEditorActive: store.isEditorActive(),
					isSaving: store.isResolving( 'updateCallToAction' ),
					getEditorValues: store.getEditedCallToAction,
					savedSuccessfully:
						resolutionState?.status === DispatchStatus.Success,
				};
			},
			[ id ]
		);

		const { id: valuesId } = values;

		const { editRecord, resetRecordEdits, changeEditorId } =
			useDispatch( callToActionStore );

		/**
		 * Get general error notices (not field-specific)
		 */
		const notices = useSelect( ( select ) => {
			const allNotices =
				select( noticesStore ).getNotices( NOTICE_CONTEXT );
			// Filter out field-specific notices
			return allNotices.filter(
				( notice ) => ! notice.id?.startsWith( 'field-error-' )
			);
		}, [] );

		const { removeNotice } = useDispatch( noticesStore );

		useEffect( () => {
			if ( ! isEditorActive && id ) {
				changeEditorId( id );
			}

			return () => {
				if ( valuesId && isEditorActive ) {
					changeEditorId( undefined );
				}
			};
		}, [ id, valuesId, isEditorActive, changeEditorId ] );

		/**
		 * Save the CallToAction when the editor is saved.
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
				setTriedSaving( false );
				// Get the latest CTA from the store after save
				const latestCta = getEditorValues( values.id );
				onSave?.( latestCta || ( values as Editable ) );
			}
		}, [
			onSave,
			triedSaving,
			savedSuccessfully,
			getEditorValues,
			values,
			isSaving,
		] );

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
		 * Render the error messages.
		 */
		const ErrorMessages = () => (
			<>
				{ notices.map( ( notice ) => (
					<Notice
						key={ notice.id }
						status={
							( notice.status as
								| 'error'
								| 'warning'
								| 'success'
								| 'info'
								| undefined ) || 'error'
						}
						className="call-to-action-editor-error"
						onDismiss={
							notice.isDismissible !== false
								? () => {
										removeNotice(
											notice.id,
											NOTICE_CONTEXT
										);
								  }
								: undefined
						}
					>
						{ notice.content }
					</Notice>
				) ) }
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
				beforeTabs={
					<>
						<DebugNotices />
						<ErrorMessages />
						{ componentProps.beforeTabs }
					</>
				}
			/>
		);
	};
};

export default withDataStore;
