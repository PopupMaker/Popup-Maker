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
	popupId: number | string;
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

	return <span className={ spanClassName }>{ label }</span>;
}

export default function PopupTriggerViewer( {
	className,
	spanClassName,
	onEditLinkClick,
	popupId,
	...props
} ) {
	return (
		<div
			className={ clsx(
				'block-editor-popup-trigger-popover__popup-viewer',
				className
			) }
			{ ...props }
		>
			<PopupView popupId={ popupId } className={ spanClassName } />
			{ onEditLinkClick && (
				<Button
					icon="edit"
					label={ __( 'Edit', 'popup-maker' ) }
					onClick={ onEditLinkClick }
				/>
			) }
		</div>
	);
}
