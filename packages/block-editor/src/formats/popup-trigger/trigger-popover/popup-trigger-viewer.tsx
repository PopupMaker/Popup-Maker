import clsx from 'clsx';

import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const { popups = [] } = window.popupMakerBlockEditor;

const getPopupById = ( popupId: number | string = 0 ) => {
	popupId = parseInt( String( popupId ) ) || 0;

	const popup = popups.filter( ( { ID } ) => popupId === ID );

	return popup.length === 1 ? popup[ 0 ] : false;
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
	const label = popup
		? /* translators: %s = popup title */
		  sprintf( __( 'Open "%s" popup', 'popup-maker' ), popup.post_title )
		: '';

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
}: PopupTriggerViewerProps ) {
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
