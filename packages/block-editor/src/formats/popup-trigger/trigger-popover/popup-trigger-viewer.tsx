import clsx from 'clsx';

import { Button } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';

import { __, sprintf } from '@popup-maker/i18n';

type LocalizedPopup = {
	ID: number;
	post_title: string;
};

const { popups = [] } = window.popupMakerBlockEditor as unknown as {
	popups?: LocalizedPopup[];
};

const getPopupById = ( popupId: number | string = 0 ) => {
	popupId = parseInt( String( popupId ) ) || 0;

	return popups.find( ( { ID } ) => popupId === ID ) || false;
};

function PopupView( {
	popupId,
	className,
}: {
	popupId: number;
	className: string;
} ) {
	const spanClassName = clsx(
		className,
		'block-editor-popup-trigger-popover__popup-viewer-text'
	);

	const popup = getPopupById( popupId );
	let label = '';
	if ( popup ) {
		label = sprintf(
			/* translators: %s: popup title. */
			__( 'Open "%s" popup', 'popup-maker' ),
			decodeEntities( popup.post_title )
		);
	}

	return (
		<span className={ spanClassName } role="button" aria-label={ label }>
			{ label }
		</span>
	);
}

type PopupTriggerViewerProps = {
	className?: string;
	spanClassName?: string;
	onEditTriggerClick?: (
		event: React.MouseEvent | React.KeyboardEvent
	) => void;
	popupId: number;
	onKeyPress?: ( event: React.KeyboardEvent ) => void;
};

export default function PopupTriggerViewer( {
	className = '',
	spanClassName = '',
	onEditTriggerClick,
	popupId,
	...props
}: PopupTriggerViewerProps ): JSX.Element {
	return (
		<div
			className={ clsx(
				'block-editor-popup-trigger-popover__popup-viewer',
				className
			) }
			role="region"
			aria-label={ __( 'Popup Trigger Preview', 'popup-maker' ) }
			{ ...props }
		>
			<PopupView popupId={ popupId } className={ spanClassName } />
			{ onEditTriggerClick && (
				<Button
					icon="edit"
					label={ __( 'Edit', 'popup-maker' ) }
					onClick={ onEditTriggerClick }
				/>
			) }
		</div>
	);
}
