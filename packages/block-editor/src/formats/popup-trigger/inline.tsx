import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { useMemo, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { Button, Popover, ToggleControl } from '@wordpress/components';
import { BACKSPACE, DOWN, ENTER, LEFT, RIGHT, UP } from '@wordpress/keycodes';
import {
	applyFormat,
	create,
	insert,
	isCollapsed,
	slice,
	replace,
	split,
	concat,
	useAnchor,
} from '@wordpress/rich-text';

import type { RichTextValue } from '@wordpress/rich-text';
import { name, settings } from './index';
import { PopupTriggerEditor, PopupTriggerViewer } from './trigger-popover';
// @ts-ignore
import { createTriggerFormat, getFormatBoundary } from './utils';

import type { WPFormat } from '@wordpress/rich-text/build-types/register-format-type';
import type { TriggerFormatAttributes } from './types';

const { popups = [] } = window.popupMakerBlockEditor;

const stopKeyPropagation = ( event: React.KeyboardEvent ) =>
	event.stopPropagation();

const getRichTextValueFromSelection = (
	value: RichTextValue,
	isActive: boolean
) => {
	// Default to the selection ranges on the RichTextValue object.
	let textStart = value.start;
	let textEnd = value.end;

	// If the format is currently active then the rich text value
	// should always be taken from the bounds of the active format
	// and not the selected text.
	if ( isActive ) {
		const boundary = getFormatBoundary( value, {
			type: name,
		} );

		if ( boundary?.start ) {
			textStart = boundary?.start;
		}

		if ( boundary?.end ) {
			// Text *selection* always extends +1 beyond the edge of the format.
			// We account for that here.
			textEnd = ( boundary?.end ?? 0 ) + 1;
		}
	}

	// Get a RichTextValue containing the selected text content.
	return slice( value, textStart, textEnd );
};

type Props = {
	isActive: boolean;
	activeAttributes: TriggerFormatAttributes;
	value: RichTextValue;
	onChange: ( value: any ) => void;
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
	const richTriggerTextValue = getRichTextValueFromSelection(
		value,
		isActive
	);

	// Get the text content minus any HTML tags.
	const richTextText = richTriggerTextValue.text;

	// Get dispatch action to change the selection.
	const { selectionChange } = useDispatch( blockEditorStore );

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

	const triggerValue: Partial< TriggerFormatAttributes > = useMemo(
		() => ( {
			popupId: activeAttributes.popupId,
			doDefault: activeAttributes.doDefault,
		} ),
		[ activeAttributes.popupId, activeAttributes.doDefault ]
	);

	const [ isEditingTrigger, setIsEditingTrigger ] = useState(
		! activeAttributes || ! activeAttributes.popupId
	);

	const editTrigger = ( event: React.MouseEvent | React.KeyboardEvent ) => {
		setIsEditingTrigger( true );
		event.preventDefault();
	};

	// const removeTrigger = () => {
	// 	const newValue = removeFormat( value, name );
	// 	onChange( newValue );
	// 	stopAddingTrigger();
	// 	speak( __( 'Trigger removed.', 'popup-maker' ), 'assertive' );
	// };

	const onChangeTrigger = (
		nextValue: Partial< TriggerFormatAttributes >
	) => {
		const hasTrigger = triggerValue?.popupId;
		const isNewTrigger = ! hasTrigger;

		// Merge the next value with the current trigger value.
		nextValue = {
			...triggerValue,
			...nextValue,
		};

		const triggerFormat = createTriggerFormat( {
			popupId: nextValue.popupId ?? 0,
			doDefault: nextValue.doDefault === '1',
		} );

		const newText = nextValue.popupId
			? popups[ nextValue.popupId ]?.title
			: __( 'Open Popup', 'popup-maker' );

		// Scenario: we have any active text selection or an active format.
		let newValue: RichTextValue;
		if ( isCollapsed( value ) && ! isActive ) {
			// Scenario: we don't have any actively selected text or formats.
			const inserted = insert( value, newText );

			newValue = applyFormat(
				inserted,
				triggerFormat,
				value.start,
				value.start + newText.length
			);

			onChange( newValue );

			// Close the Trigger UI.
			stopAddingTrigger();

			// Move the selection to the end of the inserted trigger outside of the format boundary
			// so the user can continue typing after the trigger.
			selectionChange( {
				clientId: selectionStart.clientId,
				identifier: selectionStart.attributeKey,
				start: value.start + newText.length + 1,
			} );

			return;
		} else if ( newText === richTextText ) {
			newValue = applyFormat( value, triggerFormat );
		} else {
			// Scenario: Editing an existing trigger.

			// Create new RichText value for the new text in order that we
			// can apply formats to it.
			newValue = create( { text: newText } );
			// Apply the new Trigger format to this new text value.
			newValue = applyFormat(
				newValue,
				triggerFormat,
				0,
				newText.length
			);

			// Get the boundaries of the active trigger format.
			const boundary = getFormatBoundary( value, {
				type: name,
			} );

			// Split the value at the start of the active trigger format.
			// Passing "start" as the 3rd parameter is required to ensure
			// the second half of the split value is split at the format's
			// start boundary and avoids relying on the value's "end" property
			// which may not correspond correctly.
			const [ valBefore, valAfter ] = split(
				value,
				boundary.start ?? 0,
				boundary?.start ?? 0
			) as [ RichTextValue, RichTextValue ];

			// Update the original (full) RichTextValue replacing the
			// target text with the *new* RichTextValue containing:
			// 1. The new text content.
			// 2. The new trigger format.
			// As "replace" will operate on the first match only, it is
			// run only against the second half of the value which was
			// split at the active format's boundary. This avoids a bug
			// with incorrectly targetted replacements.
			// See: https://github.com/WordPress/gutenberg/issues/41771.
			// Note original formats will be lost when applying this change.
			// That is expected behaviour.
			// See: https://github.com/WordPress/gutenberg/pull/33849#issuecomment-936134179.
			const newValAfter = replace(
				valAfter,
				richTextText,
				newValue as unknown as Function
			);

			newValue = concat( valBefore, newValAfter );
		}

		onChange( newValue );

		// Focus should only be returned to the rich text on submit if this trigger is not
		// being created for the first time. If it is then focus should remain within the
		// Trigger UI because it should remain open for the user to modify the trigger they have
		// just created.
		if ( ! isNewTrigger ) {
			stopAddingTrigger();
		}

		if ( isActive ) {
			speak( __( 'Trigger edited.' ), 'assertive' );
		} else {
			speak( __( 'Trigger inserted.' ), 'assertive' );
		}
	};

	const onKeyDown = ( event ) => {
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

	const popoverAnchor = useAnchor( {
		editableContentElement: contentRef.current,
		settings: {
			...settings,
			isActive,
		} as WPFormat,
	} );

	const [ isSettingsExpanded, setSettingsExpanded ] =
		useState< boolean >( false );

	const toggleSettingsVisibility = () => {
		setSettingsExpanded( ! isSettingsExpanded );
	};

	const { popupId, doDefault } = triggerValue;

	const setDoDefault = ( newValue: boolean ) => {
		onChangeTrigger( { doDefault: newValue } );
	};

	const setPopupID = ( newValue: string ) => {
		onChangeTrigger( { popupId: newValue } );
	};

	const isEditing = isEditingTrigger || ! triggerValue.popupId;

	return (
		<Popover
			className="editor-popup-trigger-popover block-editor-popup-trigger-popover"
			anchor={ popoverAnchor }
			animate={ false }
			onClose={ stopAddingTrigger }
			onFocusOutside={ onFocusOutside }
			placement="bottom"
			// position={ position ?? 'bottom center'}
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
							onChangeInputValue={ setPopupID }
							onKeyDown={ onKeyDown }
							onKeyPress={ stopKeyPropagation }
							// onSubmit={ onChangeTrigger }
						/>
					) : (
						<PopupTriggerViewer
							className="editor-format-toolbar__trigger-container-content block-editor-format-toolbar__trigger-container-content"
							onKeyPress={ stopKeyPropagation }
							popupId={ popupId ?? '' }
							onEditTriggerClick={ editTrigger }
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
								'Do default browser action?',
								'popup-maker'
							) }
							checked={ !! doDefault }
							onChange={ setDoDefault }
						/>
					</div>
				) }
			</div>
		</Popover>
	);
};

export default InlinePopupTriggerUI;
