import clsx from 'clsx';

import { __ } from '@popup-maker/i18n';
import { close, link } from '@wordpress/icons';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import {
	Button,
	Flex,
	Modal,
	Spinner,
	ToggleControl,
} from '@wordpress/components';

import { callToActionStore } from '@popup-maker/core-data';

import type { ComponentType } from 'react';
import type { ModalProps } from '@wordpress/components/build-types/modal/types';
import type { EditorWithDataStoreProps } from './with-data-store';
import { ConfirmDialogue } from '@popup-maker/components';

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
 * @param {ComponentType<EditorWithDataStoreProps>} WrappedComponent The component to wrap.
 *
 * @return {Function} The wrapped component.
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
		const [ confirm, setConfirm ] = useState< {
			message: string;
			callback: () => void;
			isDestructive?: boolean;
		} >();

		// Fetch values separately so they will still be available to the component.
		const values = useSelect( ( select ) => {
			const store = select( callToActionStore );

			return store.getCurrentEditorValues() ?? store.getDefaultValues();
		}, [] );

		const isSaving = useSelect(
			( select ) =>
				select( callToActionStore ).isResolving( 'updateCallToAction' ),
			[]
		);

		const { hasUndo, hasRedo, hasEdits, getHasEdits } = useSelect(
			( select ) => {
				if ( ! values.id ) {
					return {
						hasUndo: false,
						hasRedo: false,
						hasEdits: false,
						getHasEdits: () => false,
					};
				}

				const store = select( callToActionStore );

				return {
					hasUndo: store.hasUndo( values.id ),
					hasRedo: store.hasRedo( values.id ),
					hasEdits: store.hasEdits( values.id ),
					getHasEdits: store.hasEdits,
				};
			},
			// eslint-disable-next-line react-hooks/exhaustive-deps
			[ values, isSaving ]
		);

		const {
			saveEditorValues,
			undo,
			redo,
			resetRecordEdits,
			updateEditorValues,
		} = useDispatch( callToActionStore );

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

		const confirmLoss = () => {
			// eslint-disable-next-line no-alert, no-restricted-globals
			return window.confirm(
				__( 'Changes you made may not be saved.', 'popup-maker' )
			);
		};

		/**
		 * Handle the close event.
		 */
		const closeModal = useCallback(
			() => {
				if ( isSaving ) {
					return; // Prevent closing while saving
				}

				if ( hasEdits ) {
					setConfirm( {
						message: __(
							'Changes you made may not be saved.',
							'popup-maker'
						),
						callback: () => {
							resetRecordEdits( values.id );
							onClose?.();
						},
						isDestructive: true,
					} );
					return;
				}

				resetRecordEdits( values.id );
				onClose?.();
			},
			// eslint-disable-next-line react-hooks/exhaustive-deps
			[ isSaving, onClose, hasEdits ]
		);

		/**
		 * Handle saving the values.
		 */
		const saveValues = useCallback(
			async () => {
				try {
					// Save to the database
					await saveEditorValues();

					const hasRemainingEdits = getHasEdits( values.id );
					// Handle modal closing if needed
					if ( ! hasRemainingEdits && closeOnSave ) {
						closeModal();
					}
				} catch ( error ) {
					// eslint-disable-next-line no-console
					console.error( 'Save failed:', error );
				}
			},
			// eslint-disable-next-line react-hooks/exhaustive-deps
			[ closeOnSave, closeModal, getHasEdits ]
		);

		const { id: valuesId } = values;

		// Set up confirm to close dialogue as well as prevent changing pages in the brower while hasEdits.
		useEffect(
			() => {
				// On beforeunload event, confirm loss of unsaved changes.
				const confirmLossOfUnsavedChanges = (
					event: BeforeUnloadEvent
				) => {
					if ( hasEdits ) {
						if ( confirmLoss() ) {
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

		return (
			<>
				{ confirm && (
					<ConfirmDialogue
						{ ...confirm }
						onClose={ () => setConfirm( undefined ) }
					/>
				) }
				<Modal
					{ ...modalProps }
					title={ modalTitle }
					className={ clsx(
						'call-to-action-editor-modal',
						modalProps?.className
					) }
					onRequestClose={ closeModal }
					shouldCloseOnClickOutside={ true }
					isDismissible={ false }
					headerActions={
						<Flex
							direction="row"
							style={ {
								justifyContent: 'space-between',
								alignItems: 'center',
								// position: 'absolute',
								// top: 13,
								// right: 200,
								// zIndex: 100,
								maxWidth: 'max-content',
							} }
						>
							{ showActions && hasEdits && (
								<>
									<Button
										disabled={ isSaving || ! hasUndo }
										variant="tertiary"
										icon={ 'undo' }
										aria-label={ __(
											'Undo',
											'popup-maker'
										) }
										onClick={ () => undo( values.id ) }
									/>

									<Button
										disabled={ isSaving || ! hasRedo }
										variant="tertiary"
										icon={ 'redo' }
										aria-label={ __(
											'Redo',
											'popup-maker'
										) }
										onClick={ () => redo( values.id ) }
									/>
								</>
							) }

							<div
								className={ clsx( [
									'call-to-action-enabled-toggle',
									values?.status === 'publish'
										? 'enabled'
										: 'disabled',
								] ) }
							>
								<ToggleControl
									disabled={ isSaving }
									label={
										values?.status === 'publish'
											? __( 'Enabled', 'popup-maker' )
											: __( 'Disabled', 'popup-maker' )
									}
									checked={ values?.status === 'publish' }
									onChange={ ( checked ) =>
										updateEditorValues( {
											id: values.id,
											status: checked
												? 'publish'
												: 'draft',
										} )
									}
									__nextHasNoMarginBottom
								/>
							</div>

							<Button
								variant="link"
								icon={ close }
								aria-label={ __( 'Close', 'popup-maker' ) }
								onClick={ closeModal }
								style={ {
									color: 'currentColor',
								} }
							/>
						</Flex>
					}
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
								disabled={ isSaving || ! hasEdits }
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
									: __(
											'Add Call to Action',
											'popup-maker'
									  ) }
							</Button>

							{ showDocumentationLink && (
								<Button
									text={ __(
										'Documentation',
										'popup-maker'
									) }
									href={ documenationUrl }
									target="_blank"
									icon={ link }
									iconSize={ 20 }
								/>
							) }
						</div>
					) }
				</Modal>
			</>
		);
	};
};

export default withModal;
