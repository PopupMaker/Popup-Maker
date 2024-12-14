import { isString } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useMemo, useState, useEffect } from '@wordpress/element';
import {
	ToggleControl,
	withNotices,
	withSpokenMessages,
} from '@wordpress/components';
import { BACKSPACE, DOWN, ENTER, LEFT, RIGHT, UP } from '@wordpress/keycodes';
import { applyFormat, create, insert, isCollapsed } from '@wordpress/rich-text';

import type { RichTextValue } from '@wordpress/rich-text';
import type { VirtualElement } from '@wordpress/components/build-types/popover/types';

import { createTriggerFormat } from './utils';

import TriggerPopover, {
	PopupTriggerEditor,
	PopupTriggerViewer,
} from './trigger-popover';

const stopKeyPropagation = ( event: React.KeyboardEvent ) =>
	event.stopPropagation();

function isShowingInput( props: Props, state: State ) {
	return props.addingTrigger || state.editTrigger;
}

const TriggerPopoverAtText = ( {
	isActive,
	addingTrigger,
	value,
	...props
} ) => {
	const anchorRect: Element | VirtualElement | null = useMemo( () => {
		const selection = window.getSelection();
		const range =
			selection.rangeCount > 0 ? selection.getRangeAt( 0 ) : null;

		if ( ! range ) {
			return null;
		}

		if ( addingTrigger ) {
			// Return element from range
			const element = range.startContainer;
			if ( element instanceof Element ) {
				return element;
			}
		}

		const node = range.startContainer;

		// If the node is not an element, get its parent element
		const element = node instanceof Element ? node : node.parentElement;

		if ( ! element ) {
			return null;
		}

		const trigger = element.closest( 'span.popup-trigger' );
		if ( trigger ) {
			return trigger;
		}

		return null;
	}, [ addingTrigger ] );

	if ( ! anchorRect ) {
		return null;
	}

	return (
		<TriggerPopover anchor={ anchorRect } { ...props }>
			{ props.children }
		</TriggerPopover>
	);
};

type Props = {
	value: RichTextValue;
	onChange: ( value: any ) => void;
	isActive: boolean;
	addingTrigger: boolean;
	noticeUI?: React.ReactNode;
	activeAttributes: {
		popupId: string;
		doDefault: string;
		[ key: string ]: string;
	};
	noticeOperations: {
		createNotice: ( notice: object ) => void;
		removeNotice: ( noticeId: string ) => void;
	};

	stopAddingTrigger: () => void;
	speak: ( message: string, type: string ) => void;
};

type State = {
	popupId: string;
	doDefault: boolean;
	editTrigger: boolean;
};

const InlinePopupTriggerUI = withSpokenMessages(
	withNotices( ( props: Props ) => {
		const {
			isActive,
			addingTrigger,
			value,
			noticeUI,
			activeAttributes,
			onChange,
			speak,
		} = props;

		const [ popoverState, setState ] = useState< State >( {
			popupId: '',
			doDefault: false,
			editTrigger: false,
		} );

		useEffect( () => {
			const { popupId = '' } = activeAttributes;
			let { doDefault = false } = activeAttributes;

			// Convert string value to boolean for comparison.
			if ( isString( doDefault ) ) {
				doDefault = '1' === doDefault;
			}

			if ( ! isShowingInput( props, popoverState ) ) {
				const update = {} as { popupId?: string; doDefault?: boolean };
				if ( popupId !== popoverState.popupId ) {
					update.popupId = popupId;
				}

				if ( doDefault !== popoverState.doDefault ) {
					update.doDefault = doDefault;
				}

				if ( Object.keys( update ).length ) {
					setState( ( prevState ) => ( {
						...prevState,
						...update,
					} ) );
				}
			}
		}, [ activeAttributes, props, popoverState ] );

		const onKeyDown = ( event: React.KeyboardEvent ) => {
			if (
				(
					[
						LEFT,
						DOWN,
						RIGHT,
						UP,
						BACKSPACE,
						ENTER,
					] as unknown as number[]
				 ).indexOf( parseInt( event.key ) ) > -1
			) {
				// Stop the key event from propagating up to ObserveTyping.startTypingInTextField.
				event.stopPropagation();
			}
		};

		const setPopupID = ( popupId: string ) => {
			const { noticeOperations } = props;

			noticeOperations.removeNotice( 'missingPopupId' );

			if ( '' === popupId ) {
				noticeOperations.createNotice( {
					id: 'missingPopupId',
					status: 'error',
					content: __(
						"Choose a popup or the trigger won't function.",
						'popup-maker'
					),
				} );
			}

			setState( {
				...popoverState,
				popupId,
			} );
		};

		const setDoDefault = ( doDefault: boolean ) => {
			const { popupId = 0 } = activeAttributes;

			setState( { ...popoverState, doDefault } );

			// Apply now if URL is not being edited.
			if ( ! isShowingInput( props, popoverState ) ) {
				onChange(
					applyFormat(
						value,
						createTriggerFormat( {
							popupId: parseInt( String( popupId ), 10 ),
							doDefault,
						} )
					)
				);
			}
		};

		const editTrigger = ( event: React.MouseEvent ) => {
			setState( { ...popoverState, editTrigger: true } );
			event.preventDefault();
		};

		const submitTrigger = ( event: React.FormEvent ) => {
			const { popupId, doDefault } = popoverState;
			const format = createTriggerFormat( {
				popupId: parseInt( String( popupId ), 10 ),
				doDefault,
			} );

			event.preventDefault();

			if ( isCollapsed( value ) && ! isActive ) {
				const toInsert = applyFormat(
					create( { text: __( 'Open Popup', 'popup-maker' ) } ),
					format,
					0,
					__( 'Open Popup', 'popup-maker' ).length
				);
				onChange( insert( value, toInsert ) );
			} else {
				onChange( applyFormat( value, format ) );
			}

			resetState();

			if ( isActive ) {
				speak( __( 'Trigger edited.', 'popup-maker' ), 'assertive' );
			} else {
				speak( __( 'Trigger inserted.', 'popup-maker' ), 'assertive' );
			}
		};

		const onFocusOutside = () => {
			resetState();
		};

		const resetState = () => {
			props.stopAddingTrigger();
			setState( { ...popoverState, editTrigger: false } );
		};

		// If the user is not adding a trigger from the toolbar or actively inside render nothing.
		if ( ! isActive && ! addingTrigger ) {
			return null;
		}

		const { popupId, doDefault } = popoverState;
		const showInput = isShowingInput( props, popoverState );

		return (
			<TriggerPopoverAtText
				value={ value }
				isActive={ isActive }
				addingTrigger={ addingTrigger }
				onFocusOutside={ onFocusOutside }
				onClose={ resetState }
				noticeUI={ noticeUI }
				focusOnMount={ showInput ? 'firstElement' : false }
				renderSettings={ () => (
					<ToggleControl
						label={ __(
							'Do default browser action?',
							'popup-maker'
						) }
						checked={ doDefault }
						onChange={ setDoDefault }
					/>
				) }
			>
				{ showInput ? (
					<PopupTriggerEditor
						className="editor-format-toolbar__link-container-content block-editor-format-toolbar__link-container-content"
						value={ popupId }
						onChangeInputValue={ setPopupID }
						onKeyDown={ onKeyDown }
						onKeyPress={ stopKeyPropagation }
						onSubmit={ submitTrigger }
					/>
				) : (
					<PopupTriggerViewer
						className="editor-format-toolbar__link-container-content block-editor-format-toolbar__link-container-content"
						onKeyPress={ stopKeyPropagation }
						popupId={ popupId }
						onEditLinkClick={ editTrigger }
					/>
				) }
			</TriggerPopoverAtText>
		);
	} )
);

export default InlinePopupTriggerUI;
