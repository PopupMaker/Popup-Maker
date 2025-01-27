import { name, settings } from './index';

import { __ } from '@popup-maker/i18n';
import { speak } from '@wordpress/a11y';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	useMemo,
	useState,
	useCallback,
	useRef,
	useEffect,
} from '@wordpress/element';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { Button, Popover, ToggleControl } from '@wordpress/components';
import { BACKSPACE, DOWN, ENTER, LEFT, RIGHT, UP } from '@wordpress/keycodes';
import {
	useAnchor,
	applyFormat,
	isCollapsed,
	removeFormat,
} from '@wordpress/rich-text';

import { PopupTriggerEditor, PopupTriggerViewer } from './trigger-popover';
import {
	createTriggerFormat,
	insertFormattedText,
	triggerOptionsFromFormatAttrs,
} from './utils';

import type { RichTextValue } from '@wordpress/rich-text';
import type { WPFormat } from '@wordpress/rich-text/build-types/register-format-type';
import type { TriggerFormatAttributes, TriggerFormatOptions } from './types';

const stopKeyPropagation = ( event: React.KeyboardEvent ) =>
	event.stopPropagation();

const onKeyDown = ( event: React.KeyboardEvent ) => {
	if (
		(
			[ LEFT, DOWN, RIGHT, UP, BACKSPACE, ENTER ] as unknown as number[]
		 ).indexOf( parseInt( event.key ) ) > -1
	) {
		// Stop the key event from propagating up to ObserveTyping.startTypingInTextField.
		event.stopPropagation();
	}
};

type Props = {
	isActive: boolean;
	activeAttributes: TriggerFormatAttributes;
	value: RichTextValue;
	onChange: ( value: RichTextValue ) => void;
	onFocusOutside: () => void;
	stopAddingTrigger: () => void;
	contentRef: React.RefObject< HTMLElement >;
	focusOnMount: boolean | 'firstElement';
};

const InlinePopupTriggerUI = ( {
	isActive,
	activeAttributes,
	value,
	onChange,
	onFocusOutside,
	stopAddingTrigger,
	contentRef,
	focusOnMount,
}: Props ) => {
	// Get dispatch action to change the selection.
	const { selectionChange } = useDispatch( blockEditorStore );

	// Get the current selection.
	const { selectionStart } = useSelect( ( select ) => {
		const { getSelectionStart } = select( blockEditorStore ) as {
			getSelectionStart: () => {
				clientId: string;
				attributeKey: string;
				identifier: string;
				start: number;
			};
		};

		return {
			selectionStart: getSelectionStart(),
		};
	}, [] );

	const triggerValue: TriggerFormatOptions = useMemo(
		() => triggerOptionsFromFormatAttrs( activeAttributes ),
		[ activeAttributes ]
	);

	const [ isEditingTrigger, setIsEditingTrigger ] = useState(
		! activeAttributes || ! activeAttributes.popupId
	);

	const popoverAnchor = useAnchor( {
		editableContentElement: contentRef.current,
		settings: {
			...settings,
			isActive,
		} as WPFormat,
	} );

	const removeTrigger = useCallback( () => {
		const newValue = removeFormat( value, name );
		onChange( newValue );
		stopAddingTrigger();
		speak( __( 'Trigger removed.', 'popup-maker' ), 'assertive' );
	}, [ onChange, stopAddingTrigger, value ] );

	const [ isSettingsExpanded, setIsSettingsExpanded ] =
		useState< boolean >( false );

	const toggleSettingsVisibility = useCallback( () => {
		setIsSettingsExpanded( ( prevState ) => ! prevState );
	}, [] );

	const onChangeTrigger = useCallback(
		( changes: Partial< TriggerFormatOptions > ) => {
			const hasTrigger = triggerValue?.popupId;
			const isNewTrigger = ! hasTrigger;

			// Merge the next value with the current trigger value
			const nextValue: TriggerFormatOptions = {
				...triggerValue,
				...changes,
			};

			// Handle removing trigger if popup ID is cleared
			if ( ! isNewTrigger && ! nextValue?.popupId ) {
				removeTrigger();
				return;
			}

			const triggerFormat = createTriggerFormat( nextValue );
			const defaultText = __( 'Open Popup', 'popup-maker' );

			let newValue: RichTextValue;

			if ( isCollapsed( value ) && ! isActive ) {
				// No selection - insert new trigger with default text.
				newValue = insertFormattedText(
					value,
					defaultText,
					triggerFormat,
					value.start
				);

				onChange( newValue );

				// Move selection after the inserted trigger.
				selectionChange( {
					clientId: selectionStart.clientId,
					identifier: selectionStart.attributeKey,
					start: value.start + defaultText.length + 1,
				} );
			} else {
				// Has selected text - apply format to selection
				newValue = applyFormat( value, triggerFormat );
				onChange( newValue );
			}

			// Announce the change
			if ( isActive ) {
				speak( __( 'Trigger edited.', 'popup-maker' ), 'assertive' );
			} else {
				speak( __( 'Trigger inserted.', 'popup-maker' ), 'assertive' );
			}
		},
		[
			triggerValue,
			value,
			isActive,
			onChange,
			removeTrigger,
			selectionChange,
			selectionStart,
		]
	);

	const isEditing = isEditingTrigger || ! triggerValue.popupId;
	const { popupId, doDefault } = triggerValue;

	const previousFocus = useRef< HTMLElement | null >( null );

	useEffect( () => {
		// Get the editable content element
		const editableContent = contentRef.current;

		// If we have an active format, use the activeElement
		if (
			isActive &&
			editableContent?.ownerDocument?.activeElement !==
				editableContent?.ownerDocument?.body
		) {
			previousFocus.current = editableContent?.ownerDocument
				?.activeElement as HTMLElement;
		} else {
			// If no active format or body is focused, use the contentRef
			previousFocus.current = editableContent;
		}

		const closeOnEscape = ( event: KeyboardEvent ) => {
			if ( event.key === 'Escape' ) {
				stopAddingTrigger();
			}
		};

		// Add the event listener.
		document.addEventListener( 'keydown', closeOnEscape );
		return () => {
			// Restore focus when popover closes.
			if ( previousFocus.current && 'focus' in previousFocus.current ) {
				previousFocus.current.focus();
			}
			// Remove the event listener.
			document.removeEventListener( 'keydown', closeOnEscape );
		};
	}, [ contentRef, isActive, stopAddingTrigger ] );

	// Close on escape keypress
	useEffect( () => {
		return () => {};
	}, [] );

	return (
		<>
			<Popover
				className="block-editor-popup-trigger__inline-popover  editor-popup-trigger-popover block-editor-popup-trigger-popover"
				anchor={ popoverAnchor }
				animate={ false }
				onClose={ () => {
					if ( ! isActive ) {
						stopAddingTrigger();
					}
				} }
				onFocusOutside={ onFocusOutside }
				position={ 'bottom center' }
				offset={ 8 }
				shift
				focusOnMount={ focusOnMount }
				constrainTabbing
			>
				<div className="block-editor-popup-trigger-popover__input-container">
					<div className="editor-popup-trigger-popover__row block-editor-popup-trigger-popover__row">
						{ isEditing ? (
							<PopupTriggerEditor
								className="editor-format-toolbar__trigger-container-content block-editor-format-toolbar__trigger-container-content"
								value={ popupId ?? '' }
								onChangeInputValue={ ( newValue ) => {
									onChangeTrigger( {
										popupId: Number( newValue ),
									} );
								} }
								onKeyDown={ onKeyDown }
								onKeyPress={ stopKeyPropagation }
								// @ts-expect-error -- TS2322
								onSubmit={ stopAddingTrigger }
							/>
						) : (
							<PopupTriggerViewer
								className="editor-format-toolbar__trigger-container-content block-editor-format-toolbar__trigger-container-content"
								onKeyPress={ stopKeyPropagation }
								popupId={ popupId ?? 0 }
								onEditTriggerClick={ (
									event:
										| React.MouseEvent
										| React.KeyboardEvent
								) => {
									setIsEditingTrigger( true );
									event.preventDefault();
								} }
							/>
						) }

						<Button
							className="editor-popup-trigger-popover__settings-toggle block-editor-popup-trigger-popover__settings-toggle"
							icon={
								isSettingsExpanded
									? 'arrow-up-alt2'
									: 'arrow-down-alt2'
							}
							label={ __( 'Trigger settings', 'popup-maker' ) }
							onClick={ toggleSettingsVisibility }
							aria-expanded={ isSettingsExpanded }
						/>
					</div>
					{ isSettingsExpanded && (
						<div className="editor-popup-trigger-popover__row block-editor-popup-trigger-popover__row editor-popup-trigger-popover__settings block-editor-popup-trigger-popover__settings">
							<ToggleControl
								label={ __(
									'Do not prevent default click behavior',
									'popup-maker'
								) }
								checked={ !! doDefault }
								onChange={ ( newValue ) =>
									onChangeTrigger( {
										doDefault: newValue,
									} )
								}
								__nextHasNoMarginBottom
							/>
						</div>
					) }
				</div>
			</Popover>
		</>
	);
};

export default InlinePopupTriggerUI;
