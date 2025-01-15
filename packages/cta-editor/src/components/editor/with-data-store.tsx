import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

import {
	CALL_TO_ACTION_STORE,
	CallToAction,
	callToActionDefaults,
	Status,
} from '@popup-maker/core-data';

import type { ComponentType } from 'react';
import type { BaseEditorProps, EditorTab } from './types';

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
	 * Callback to run when the CallToAction is saved.
	 *
	 * @param values The values saved. Data store already saved the values to the database.
	 */
	onSave?: ( values: CallToAction ) => void;
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

		const {
			// TODO Review if this has side effects.
			values = callToActionDefaults,
			isEditorActive,
			isSaving,
			savedSuccessfully,
			hasError,
			error,
		} = useSelect( ( select ) => {
			const store = select( CALL_TO_ACTION_STORE );

			const createErrors = store.getDispatchError( 'createCallToAction' );

			const updateErrors = store.getDispatchError( 'updateCallToAction' );

			const hasError = createErrors || updateErrors;
			const error = createErrors ?? updateErrors;

			return {
				editorId: store.getEditorId(),
				values: store.getEditorValues(),
				isEditorActive: store.isEditorActive(),
				isSaving: store.isDispatching( [
					'createCallToAction',
					'updateCallToAction',
				] ),

				savedSuccessfully:
					Status.Success ===
						store.getDispatchStatus( 'createCallToAction' ) ||
					Status.Success ===
						store.getDispatchStatus( 'updateCallToAction' ),
				hasError,
				error,
			};
		}, [] );

		const { updateEditorValues, clearEditorData } =
			useDispatch( CALL_TO_ACTION_STORE );

		/**
		 * Clear the editor data when the component unmounts.
		 */
		useEffect( () => {
			return () => {
				clearEditorData();
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
			if ( ! triedSaving || isSaving ) {
				return;
			}

			if ( savedSuccessfully ) {
				onSave?.( values );
				setErrorMessage( undefined );
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

		return (
			<WrappedComponent
				{ ...componentProps }
				values={ values }
				onChange={ ( values ) => {
					setTriedSaving( true );
					updateEditorValues( values );
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
