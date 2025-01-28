import { __ } from '@popup-maker/i18n';
import { callToActionStore } from '@popup-maker/core-data';
import { useSelect } from '@wordpress/data';
import { useCallback, useEffect, useRef } from '@wordpress/element';

import { useEditor } from '../hooks';

import type { ComponentType } from 'react';
import type { Updatable } from '@wordpress/core-data';
import type { CallToAction, EditorId } from '@popup-maker/core-data';
import type { EditorWithModalProps } from './with-modal';
import type { EditorWithDataStoreProps } from './with-data-store';

/**
 * Props for the EditorWithQueryParams component.
 * This type extends EditorWithDataStoreProps (which is always required)
 * and optionally includes modal-specific props from EditorWithModalProps.
 */
export type EditorWithQueryParamsProps = EditorWithDataStoreProps &
	Partial< Omit< EditorWithModalProps, keyof EditorWithDataStoreProps > >;

/**
 * Wrap the editor with a modal.
 *
 * @param {ComponentType<EditorWithDataStoreProps>} WrappedComponent The component to wrap.
 *
 * @return {Function} The wrapped component.
 */
export const withQueryParams = (
	WrappedComponent: ComponentType< EditorWithQueryParamsProps >
) => {
	return function QueryParamsWrappedEditor( {
		...componentProps
	}: EditorWithQueryParamsProps ) {
		const { tab, setTab, clearEditorParams, editorId, setEditorId } =
			useEditor();

		// Fetch values separately so they will still be available to the component.
		const { isEditorActive, isSaving } = useSelect( ( select ) => {
			const store = select( callToActionStore );

			return {
				isEditorActive: store.isEditorActive(),
				isSaving: store.isResolving( 'updateCallToAction' ),
			};
		}, [] );

		// Set ref for editorId to check if it has changed.
		const currentEditorId = useRef< EditorId >( editorId );

		/**
		 * Update the query params when the editorId changes.
		 */
		useEffect( () => {
			// If the editorId changed (due to getting an id from new), update the query params via setEditorId.
			if ( editorId !== currentEditorId.current ) {
				setEditorId( editorId );
				currentEditorId.current = editorId;
			}
		}, [ editorId, setEditorId, currentEditorId ] );

		/**
		 * Clear the editor params when the component unmounts.
		 */
		useEffect(
			() => {
				return clearEditorParams;
			},
			// eslint-disable-next-line react-hooks/exhaustive-deps
			[]
		);

		const onSave = useCallback(
			( newValues: Updatable< CallToAction< 'edit' > > ) => {
				// If the editorId changed (due to getting an id from new), update the query params via setEditorId.
				if ( editorId !== newValues.id ) {
					setEditorId( newValues.id );
				}

				componentProps.onSave?.( newValues );
			},
			[ editorId, setEditorId, componentProps ]
		);

		const onClose = useCallback( () => {
			if ( isSaving ) {
				return;
			}

			clearEditorParams();
			componentProps.onClose?.();
		}, [ componentProps, isSaving, clearEditorParams ] );

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
				tab={ tab }
				setTab={ setTab }
				onSave={ onSave }
				onClose={ onClose }
			/>
		);
	};
};

export default withQueryParams;
