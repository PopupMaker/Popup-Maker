import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState, useRef } from '@wordpress/element';

import {
	CallToAction,
	defaultCtaValues,
	callToActionStore,
	EditorId,
} from '@popup-maker/core-data';

import type { ComponentType } from 'react';
import type { Updatable } from '@wordpress/core-data';
import type { BaseEditorProps, EditorTab } from './types';

type Editable = Updatable< CallToAction< 'edit' > >;

/**
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
	id?: EditorId;

	/**
	 * The default values for the editor, only applicable when creating a new call to action.
	 */
	defaultValues?: Partial< Editable > | undefined;

	/**
	 * Callback to run when the CallToAction is saved.
	 *
	 * @param values The values saved. Data store already saved the values to the database.
	 */
	onSave?: ( values: Editable ) => void;
}

type ErrorNotice = {
	message: string;
	tabName?: string;
	field?: string;
	[ key: string ]: any;
};

/**
 * Wrap the editor with the data store.
 *
 * @param WrappedComponent The component to wrap.
 *
 * @returns The wrapped component.
 */
export const withDataStore = (
	WrappedComponent: ComponentType< BaseEditorProps >
) => {
	return function DataStoreWrappedEditor( {
		id,
		defaultValues,
		onSave,
		...componentProps
	}: EditorWithDataStoreProps ) {
		/**
		 * State to store the error message.
		 */
		const [ errorMessage, setErrorMessage ] = useState<
			string | ErrorNotice
		>();

		// TODO Consider putthing this in a hook or data s.
		const [ triedSaving, setTriedSaving ] = useState< boolean >( false );
		const saveHandledRef = useRef( false );

		const {
			// TODO Review if this has side effects.
			values = {
				...defaultCtaValues,
				...( defaultValues ?? {} ),
			},
			editorId,
			isEditorActive,
			isSaving,
			hasError,
			error,
			getEditorValues,
		} = useSelect( ( select ) => {
			const store = select( callToActionStore );

			const error = store.getLastSaveError( Number( id ) );
			const hasError = error;

			return {
				editorId: store.getEditorId(),
				values: store.getEditorValues( Number( id ) ),
				isEditorActive: store.isEditorActive(),
				isSaving: store.isSaving( Number( id ) ),
				getEditorValues: store.getEditorValues,
				// savedSuccessfully:
				// 	Status.Success ===
				// 		store.getDispatchStatus( 'createCallToAction' ) ||
				// 	Status.Success ===
				// 		store.getDispatchStatus( 'updateCallToAction' ),
				hasError,
				error,
			};
		}, [] );

		const [ savedSuccessfully, setSavedSuccessfully ] =
			useState< boolean >( false );

		useEffect( () => {
			if ( isSaving ) {
				setSavedSuccessfully( false );
				return;
			}

			setSavedSuccessfully( ! hasError );
		}, [ isSaving, hasError ] );

		const { editCallToAction, resetRecordEdits, changeEditorId } =
			useDispatch( callToActionStore );

		/**
		 * Clear the editor data when the component unmounts.
		 */
		useEffect( () => {
			if ( editorId !== id ) {
				console.log( 'changeEditorId', id, editorId );
				changeEditorId( id );
			}
			return () => {
				resetRecordEdits();
			};
		}, [] );

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
			if ( ! triedSaving && isSaving ) {
				setTriedSaving( true );
				saveHandledRef.current = false;
				return;
			}

			if ( savedSuccessfully && ! saveHandledRef.current ) {
				saveHandledRef.current = true;
				setErrorMessage( undefined );
				setTriedSaving( false );
				// Get the latest CTA from the store after save
				const latestCta = getEditorValues( values.id );
				onSave?.( latestCta || values );
				return;
			}
		}, [ onSave, triedSaving, savedSuccessfully, values, isSaving ] );

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
						{ typeof errorMessage === 'string'
							? errorMessage
							: errorMessage.message }
					</Notice>
				) }
			</>
		);

		let editorValues = values;

		/**
		 * If the editor is new, use any default values passed in.
		 *
		 * This allows new ctas to have specific defaults depending on context.
		 */
		if ( 'new' === editorId ) {
			editorValues = {
				...values,
				...defaultValues,
				settings: {
					...values.settings,
					...defaultValues?.settings,
				},
			};
		}

		return (
			<WrappedComponent
				{ ...componentProps }
				values={ editorValues }
				onChange={ ( values ) => {
					editCallToAction( values.id, values );
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
