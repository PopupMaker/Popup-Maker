import clsx from 'clsx';

import { __ } from '@wordpress/i18n';
import { link } from '@wordpress/icons';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useMemo } from '@wordpress/element';
import { Button, Modal, Spinner } from '@wordpress/components';

import {
	CALL_TO_ACTION_STORE,
	CallToAction,
	validateCallToAction,
} from '@popup-maker/core-data';

import type { ComponentType } from 'react';
import type { ModalProps } from '@wordpress/components/build-types/modal/types';
import type { EditorWithDataStoreProps } from './with-data-store';

export const documenationUrl =
	'https://wppopupmaker.com/docs/?utm_campaign=documentation&utm_source=call-to-action-editor&utm_medium=plugin-ui&utm_content=footer-documentation-link';

export interface EditorWithModalProps extends EditorWithDataStoreProps {
	/**
	 * Callback to run when the CallToAction is saved.
	 *
	 * @param values The values saved. Data store already saved the values to the database.
	 */
	onSave?: ( values: CallToAction ) => void;

	/**
	 * The callback function to call when the editor is closed.
	 */
	onClose?: () => void;

	/**
	 * Whether to close the modal when the editor is saved.
	 */
	closeOnSave?: boolean;

	/**
	 * Whether to show the documentation link.
	 */
	showDocumentationLink?: boolean;

	/**
	 * Whether to show the actions.
	 */
	showActions?: boolean;

	/**
	 * The props to pass to the modal.
	 */
	modalProps?: Partial< ModalProps >;
}

/**
 * Wrap the editor with a modal.
 *
 * @param WrappedComponent The component to wrap.
 *
 * @returns The wrapped component.
 */
export const withModal = (
	WrappedComponent: ComponentType< EditorWithDataStoreProps >
) => {
	return function ModalWrappedEditor( {
		closeOnSave = true,
		showDocumentationLink = true,
		showActions = true,
		onSave,
		onClose,
		// @ts-ignore It exists but is not typed.
		onRequestClose,
		modalProps,
		...componentProps
	}: EditorWithModalProps ) {
		// Fetch values separately so they will still be available to the component.
		const { values, isSaving } = useSelect( ( select ) => {
			const store = select( CALL_TO_ACTION_STORE );

			return {
				values:
					store.getEditorValues() ?? store.getCallToActionDefaults(),
				isSaving: store.isDispatching( [
					'createCallToAction',
					'updateCallToAction',
				] ),
			};
		}, [] );

		const { saveEditorValues } = useDispatch( CALL_TO_ACTION_STORE );

		/**
		 * Get the modal title based on the CTA state.
		 */
		const modalTitle = useMemo( () => {
			if ( modalProps?.title ) {
				return modalProps.title;
			}

			return values?.id > 0
				? `${ __( 'Edit Call to Action', 'popup-maker' ) } #${
						values.id
				  }${ values?.title ? ` - ${ values.title }` : '' }`
				: __( 'New Call to Action', 'popup-maker' );
		}, [ modalProps?.title, values?.id, values?.title ] );

		/**
		 * Handle the close event.
		 */
		const closeModal = useCallback( () => {
			if ( isSaving ) {
				return; // Prevent closing while saving
			}

			onClose?.();
		}, [ isSaving, onClose ] );

		/**
		 * Handle saving the values.
		 */
		const saveValues = useCallback( async () => {
			try {
				await saveEditorValues();

				onSave?.( values );

				if ( closeOnSave ) {
					closeModal();
				}
			} catch ( error ) {
				console.error( 'Save failed:', error );
			}
		}, [ closeOnSave, closeModal, onSave, saveEditorValues, values ] );

		return (
			<Modal
				{ ...modalProps }
				title={ modalTitle }
				className={ clsx(
					'call-to-action-editor-modal',
					modalProps?.className
				) }
				onRequestClose={ closeModal }
				shouldCloseOnClickOutside={ false }
			>
				<WrappedComponent { ...componentProps } onSave={ saveValues } />

				{ showActions && (
					<div className="editor-actions">
						<Button
							text={ __( 'Cancel', 'popup-maker' ) }
							disabled={ isSaving }
							variant="tertiary"
							isDestructive={ true }
							onClick={ closeModal }
							className="cancel-button"
						/>
						<Button
							variant="primary"
							disabled={ isSaving }
							onClick={ (
								event: React.MouseEvent< HTMLButtonElement >
							) => {
								event.preventDefault();
								// TODO Test adding this to the data store save methods, propagating errors through dispatch properly.
								// Add additional validation error handling to the data store for per field etc.
								const validation =
									validateCallToAction( values );
								if ( true !== validation ) {
									if ( typeof validation === 'object' ) {
										// setErrorMessage( validation );
									}
									return;
								}

								saveValues();
							} }
						>
							{ isSaving && <Spinner /> }
							{ typeof values.id === 'number' && values.id > 0
								? __( 'Save Call to Action', 'popup-maker' )
								: __( 'Add Call to Action', 'popup-maker' ) }
						</Button>

						{ showDocumentationLink && (
							<Button
								text={ __( 'Documentation', 'popup-maker' ) }
								href={ documenationUrl }
								target="_blank"
								icon={ link }
								iconSize={ 20 }
							/>
						) }
					</div>
				) }
			</Modal>
		);
	};
};

export default withModal;
