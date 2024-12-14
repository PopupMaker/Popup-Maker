import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { removeFormat } from '@wordpress/rich-text';
import { withSpokenMessages } from '@wordpress/components';
import {
	RichTextShortcut,
	RichTextToolbarButton,
} from '@wordpress/block-editor';
import { Mark as MarkIcon } from '@popup-maker/icons';

import InlinePopupTriggerUI from './inline';

import type { TriggerFormatAttributes } from './types';

export interface WPFormat {
	name: string;
	title: string;
	tagName: string;
	className: string;
	attributes: {
		[ key: string ]: string;
	};
	interactive: boolean;
	edit: any;
}

type Props = {
	isActive: boolean;
	activeAttributes: TriggerFormatAttributes;
	value: any;
	onChange: any;
	speak: any;
};

export const TriggerEdit = withSpokenMessages( ( props: Props ) => {
	const { isActive, activeAttributes, value, onChange, speak } = props;

	const [ addingTrigger, setAddingTrigger ] = useState< boolean >( false );

	const addTrigger = () => {
		setAddingTrigger( true );
	};

	const onRemoveFormat = () => {
		onChange( removeFormat( value, name ) );
		speak( __( 'Trigger removed.', 'popup-maker' ), 'assertive' );
	};

	return (
		<>
			<RichTextShortcut
				type="primary"
				character="["
				onUse={ addTrigger }
			/>
			<RichTextShortcut
				type="primaryShift"
				character="["
				onUse={ onRemoveFormat }
			/>
			{ isActive && (
				<RichTextToolbarButton
					icon={ MarkIcon }
					iconSize={ 16 }
					title={ __( 'Remove Trigger', 'popup-maker' ) }
					onClick={ onRemoveFormat }
					isActive={ isActive }
					shortcutType="primaryShift"
					shortcutCharacter="["
				/>
			) }
			{ ! isActive && (
				<RichTextToolbarButton
					icon={ MarkIcon }
					iconSize={ 16 }
					title={ title }
					onClick={ addTrigger }
					isActive={ isActive }
					shortcutType="primary"
					shortcutCharacter="["
				/>
			) }
			<InlinePopupTriggerUI
				addingTrigger={ addingTrigger }
				stopAddingTrigger={ () => {
					setAddingTrigger( false );
				} }
				isActive={ isActive }
				activeAttributes={ activeAttributes }
				value={ value }
				onChange={ onChange }
			/>
		</>
	);
} );

const title = __( 'Popup Trigger', 'popup-maker' );
export const name = `popup-maker/popup-trigger`;
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
