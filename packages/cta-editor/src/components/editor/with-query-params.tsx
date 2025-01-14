import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useCallback, useEffect, useRef } from '@wordpress/element';

import {
	CALL_TO_ACTION_STORE,
	CallToAction,
	EditorId,
} from '@popup-maker/core-data';

import useEditor from '../hooks/use-editor';

import type { ComponentType } from 'react';
import type { EditorWithDataStoreProps } from './with-data-store';
import type { EditorWithModalProps } from './with-modal';

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
 * @param WrappedComponent The component to wrap.
 *
 * @returns The wrapped component.
 */
export const withQueryParams = (
	WrappedComponent: ComponentType< EditorWithQueryParamsProps >
) => {
	return function QueryParamsWrappedEditor( {
		...componentProps
	}: EditorWithQueryParamsProps ) {
		const { tab, setTab, clearEditorParams, setEditorId } = useEditor();

		// Fetch values separately so they will still be available to the component.
		const { editorId, isEditorActive, isSaving } = useSelect(
			( select ) => {
				const store = select( CALL_TO_ACTION_STORE );

				return {
					editorId: store.getEditorId(),
					isEditorActive: store.isEditorActive(),
					values:
						store.getEditorValues() ??
						store.getCallToActionDefaults(),
					isSaving: store.isDispatching( [
						'createCallToAction',
						'updateCallToAction',
					] ),
				};
			},
			[]
		);

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
			( newValues: CallToAction ) => {
				// If the editorId changed (due to getting an id from new), update the query params via setEditorId.
				if ( editorId !== newValues.id ) {
					setEditorId( newValues.id );
				}

				componentProps.onSave?.( newValues );
			},
			[ editorId, setEditorId ]
		);

		const onClose = useCallback( () => {
			if ( isSaving ) {
				return;
			}

			clearEditorParams();
			componentProps.onClose?.();
		}, [ componentProps.onClose, isSaving ] );

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
