import { __ } from '@popup-maker/i18n';
import { callToActionStore } from '@popup-maker/core-data';
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

import { useEditor } from '../hooks';

import type { ComponentType } from 'react';
import type { EditableCta } from '@popup-maker/core-data';
import type { EditorWithModalProps } from './with-modal';
import type { EditorWithDataStoreProps } from './with-data-store';

// TODO: Create a new hook & context for query params & consumers. Move any query param logic to this hook from useEditor.
// TODO: Export the hook & context from this file.
// TODO Move editorId to the query params, otherwise pass the id prop from the parent component.

/**
 * Props for the EditorWithQueryParams component.
 * This type extends EditorWithDataStoreProps (which is always required)
 * and optionally includes modal-specific props from EditorWithModalProps.
 */
export type EditorWithQueryParamsProps = Omit<
	EditorWithDataStoreProps &
		Partial< Omit< EditorWithModalProps, keyof EditorWithDataStoreProps > >,
	'id'
>;

/**
 * Wrap the editor with query param handling.
 *
 * @param {ComponentType<EditorWithDataStoreProps>} WrappedComponent The component to wrap.
 *
 * @return {Function} The wrapped component.
 */
export const withQueryParams = (
	WrappedComponent: ComponentType< EditorWithModalProps >
) => {
	return function QueryParamsWrappedEditor( {
		onSave: onSaveProp,
		onClose: onCloseProp,
		...componentProps
	}: EditorWithQueryParamsProps ) {
		// Tempoary.
		const closeOnSave = true;

		const { tab, setTab, clearEditorParams, editorId } = useEditor();

		const { isEditorActive, isSaving } = useSelect( ( select ) => {
			const store = select( callToActionStore );

			return {
				isEditorActive: store.isEditorActive(),
				isSaving: store.isResolving( 'updateCallToAction' ),
			};
		}, [] );

		/**
		 * Clear the editor data when the component unmounts.
		 */
		// useEffect( () => {
		// if ( editorId !== id ) {
		// eslint-disable-next-line no-console
		// console.log( 'changeEditorId', id, editorId );
		// changeEditorId( id );
		// }
		// return () => {
		// resetRecordEdits( id ?? 0 );
		// };
		// }, [ id, editorId, changeEditorId, resetRecordEdits ] );

		/**
		 * Handle clearing query params when the editor is saved.
		 */
		const onSave = useCallback(
			( newValues: EditableCta ) => {
				if ( closeOnSave ) {
					clearEditorParams();
				}

				// Save the values.
				onSaveProp?.( newValues );
			},
			[ onSaveProp, closeOnSave, clearEditorParams ]
		);

		/**
		 * Clear the editor params if the editor is closed.
		 */
		const onClose = useCallback( () => {
			if ( isSaving ) {
				return;
			}

			clearEditorParams();

			// Pass the close event to the parent component.
			onCloseProp?.();
		}, [ isSaving, clearEditorParams, onCloseProp ] );

		// If the editor isn't active, return empty.
		if ( ! isEditorActive ) {
			return null;
		}

		// When no editorId, dont' show the editor.
		if ( ! editorId ) {
			return <>{ __( 'Editor requires a valid id', 'popup-maker' ) }</>;
		}

		return (
			<WrappedComponent
				{ ...componentProps }
				id={ editorId }
				tab={ tab }
				setTab={ setTab }
				onSave={ onSave }
				onClose={ onClose }
			/>
		);
	};
};

export default withQueryParams;
