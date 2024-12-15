import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { withSpokenMessages } from '@wordpress/components';
import { removeFormat, isCollapsed } from '@wordpress/rich-text';
import { useState, useLayoutEffect, useEffect } from '@wordpress/element';
import {
	RichTextShortcut,
	RichTextToolbarButton,
} from '@wordpress/block-editor';

import { Mark as MarkIcon } from '@popup-maker/icons';

import InlinePopupTriggerUI from './inline';

import type { RichTextValue } from '@wordpress/rich-text';
import type { TriggerFormatAttributes } from './types';
import { WPFormat as WPFormatBase } from '@wordpress/rich-text/build-types/register-format-type';

interface WPFormat extends WPFormatBase {
	attributes: {
		[ key: string ]: string;
	};
}

type Props = {
	isActive: boolean;
	activeAttributes: TriggerFormatAttributes;
	value: RichTextValue;
	onChange: ( value: RichTextValue ) => void;
	onFocus: () => void;
	contentRef: { current: HTMLElement | null };
};

const title = __( 'Popup Trigger', 'popup-maker' );
export const name = `popup-maker/popup-trigger`;

export const TriggerEdit = withSpokenMessages(
	( {
		isActive,
		activeAttributes,
		value,
		onChange,
		onFocus,
		contentRef,
	}: Props ) => {
		const [ addingTrigger, setAddingTrigger ] =
			useState< boolean >( false );

		const [ openedBy, setOpenedBy ] = useState< {
			el: HTMLElement | null;
			action: string | null;
		} | null >( null );

		useEffect( () => {
			// When the link becomes inactive (i.e. isActive is false), reset the editingLink state
			// and the creatingLink state. This means that if the Link UI is displayed and the link
			// becomes inactive (e.g. used arrow keys to move cursor outside of link bounds), the UI will close.
			if ( ! isActive ) {
				setAddingTrigger( false );
			}
		}, [ isActive ] );

		useLayoutEffect( () => {
			const editableContentElement = contentRef?.current;
			if ( ! editableContentElement ) {
				return;
			}

			function handleClick( event: MouseEvent ) {
				// There is a situation whereby there is an existing link in the rich text
				// and the user clicks on the leftmost edge of that link and fails to activate
				// the link format, but the click event still fires on the `<a>` element.
				// This causes the `editingLink` state to be set to `true` and the link UI
				// to be rendered in "creating" mode. We need to check isActive to see if
				// we have an active link format.
				const target = event.target as HTMLElement; // Assert that target is an HTMLElement
				const trigger = target?.closest(
					'[contenteditable] span.popup-trigger'
				) as HTMLElement;

				if (
					! trigger || // other formats (e.g. bold) may be nested within the link.
					! isActive
				) {
					return;
				}

				setAddingTrigger( true );
				setOpenedBy( {
					el: trigger,
					action: 'click',
				} );
			}

			editableContentElement.addEventListener( 'click', handleClick );

			return () => {
				editableContentElement.removeEventListener(
					'click',
					handleClick
				);
			};
		}, [ contentRef, isActive ] );

		const addTrigger = ( target: HTMLElement ) => {
			if ( target ) {
				setOpenedBy( {
					el: target,
					action: null, // We don't need to distinguish between click or keyboard here
				} );
			}
			setAddingTrigger( true );
		};

		/**
		 * Runs when the popover is closed via escape keypress, unlinking the selected text,
		 * but _not_ on a click outside the popover. onFocusOutside handles that.
		 */
		const stopAddingTrigger = () => {
			// Don't let the click handler on the toolbar button trigger again.

			// There are two places for us to return focus to on Escape keypress:
			// 1. The rich text field.
			// 2. The toolbar button.

			// The toolbar button is the only one we need to handle returning focus to.
			// Otherwise, we rely on the passed in onFocus to return focus to the rich text field.

			// Close the popover
			setAddingTrigger( false );

			// Return focus to the toolbar button or the rich text field
			if ( openedBy?.el ) {
				openedBy.el.focus();
			} else {
				onFocus();
			}
			// Remove the openedBy state
			setOpenedBy( null );
		};

		// Test for this:
		// 1. Click on the link button
		// 2. Click the Options button in the top right of header
		// 3. Focus should be in the dropdown of the Options button
		// 4. Press Escape
		// 5. Focus should be on the Options button
		const onFocusOutside = () => {
			setAddingTrigger( false );
			setOpenedBy( null );
		};

		const onRemoveFormat = () => {
			onChange( removeFormat( value, name ) );
			speak( __( 'Trigger removed.', 'popup-maker' ), 'assertive' );
		};

		// Only autofocus if we have clicked a link within the editor
		const shouldAutoFocus = ! (
			openedBy?.el?.tagName === 'A' && openedBy?.action === 'click'
		);

		const hasSelection = ! isCollapsed( value );

		return (
			<>
				{ hasSelection && (
					<RichTextShortcut
						type="primary"
						character="p"
						onUse={ addTrigger as () => void }
					/>
				) }
				<RichTextShortcut
					type="primaryShift"
					character="p"
					onUse={ onRemoveFormat }
				/>

				{ isActive ? (
					<RichTextToolbarButton
						icon={ MarkIcon }
						iconSize={ 16 }
						title={ __( 'Remove Trigger', 'popup-maker' ) }
						onClick={ onRemoveFormat }
						isActive={ isActive }
						shortcutType="primaryShift"
						shortcutCharacter="p"
						aria-haspopup="true"
						aria-expanded={ addingTrigger }
					/>
				) : (
					<RichTextToolbarButton
						icon={ MarkIcon }
						iconSize={ 16 }
						title={ title }
						onClick={ ( event ) => {
							addTrigger( event.currentTarget );
						} }
						isActive={ isActive }
						shortcutType="primary"
						shortcutCharacter="p"
						aria-haspopup="true"
						aria-expanded={ addingTrigger }
					/>
				) }
				{ addingTrigger && (
					<InlinePopupTriggerUI
						stopAddingTrigger={ stopAddingTrigger }
						onFocusOutside={ onFocusOutside }
						isActive={ isActive }
						activeAttributes={ activeAttributes }
						value={ value }
						onChange={ onChange }
						contentRef={ contentRef }
						focusOnMount={
							shouldAutoFocus ? 'firstElement' : false
						}
					/>
				) }
			</>
		);
	}
);

export const settings: WPFormat = {
	name,
	title,
	tagName: 'span',
	className: 'popup-trigger',
	attributes: {
		popupId: 'data-popup-id',
		doDefault: 'data-do-default',
	},
	interactive: false,
	edit: TriggerEdit,
};
