import clsx from 'clsx';

import { __ } from '@wordpress/i18n';
import { link } from '@wordpress/icons';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useMemo } from '@wordpress/element';
import { Button, Modal, Spinner } from '@wordpress/components';

import { callToActionStore } from '@popup-maker/core-data';

import type { ComponentType } from 'react';
import type { ModalProps } from '@wordpress/components/build-types/modal/types';
import type { EditorWithDataStoreProps } from './with-data-store';

export const documenationUrl =
	'https://wppopupmaker.com/docs/?utm_campaign=documentation&utm_source=call-to-action-editor&utm_medium=plugin-ui&utm_content=footer-documentation-link';

export interface EditorWithModalProps extends EditorWithDataStoreProps {
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
		onClose,
		// @ts-ignore It exists but is not typed.
		onRequestClose,
		modalProps,
		...componentProps
	}: EditorWithModalProps ) {
		// Fetch values separately so they will still be available to the component.
		const { values, isSaving } = useSelect( ( select ) => {
			const store = select( callToActionStore );

			return {
				values:
					store.getCurrentEditorValues() ?? store.getDefaultValues(),
				isSaving: store.isResolving( 'updateCallToAction' ),
			};
		}, [] );

		const { saveEditorValues } = useDispatch( callToActionStore );

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
				// Save to the database
				await saveEditorValues();

				// Handle modal closing if needed
				if ( closeOnSave ) {
					closeModal();
				}
			} catch ( error ) {
				console.error( 'Save failed:', error );
			}
		}, [ closeOnSave, closeModal, saveEditorValues ] );

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
				<WrappedComponent { ...componentProps } />

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
