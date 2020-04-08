/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { IconButton } from '@wordpress/components';

const { popups } = window.pum_block_editor_vars || [];

function getPopupById( popupId = 0 ) {
	popupId = parseInt( popupId ) || 0;
	const popup = popups.filter( ( { ID } ) => popupId === ID );

	return popup.length === 1 ? popup[ 0 ] : false;
}

function PopupView( { popupId, className } ) {
	const spanClassName = classnames(
		className,
		'block-editor-popup-trigger-popover__popup-viewer-text',
	);

	const popup = getPopupById( popupId );
	const label = !! popup ? sprintf( __( 'Open "%s" popup', 'popup-maker' ), popup.post_title ) : '';

	return ( <span className={ spanClassName }>{ label }</span> );
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
			className={ classnames(
				'block-editor-popup-trigger-popover__popup-viewer',
				className,
			) }
			{ ...props }
		>
			<PopupView popupId={ popupId } className={ spanClassName } />
			{ onEditLinkClick && <IconButton
				icon="edit"
				label={ __( 'Edit', 'popup-maker' ) }
				onClick={ onEditLinkClick }
			/> }
		</div>
	);
}
