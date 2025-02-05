import { __ } from '@popup-maker/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { Button, Flex, Modal } from '@wordpress/components';

import type { ModalProps } from '@wordpress/components/build-types/modal/types';

type Props = {
	message?: string;
	callback?: () => void;
	onClose: () => void;
	isDestructive?: boolean;
} & Partial< ModalProps >;

const ConfirmDialogue = ( {
	message,
	callback,
	onClose,
	isDestructive = false,
}: Props ) => {
	const confirmButtonRef = useRef< HTMLButtonElement | null >( null );
	const previousFocusRef = useRef< HTMLElement | null >( null );

	useEffect( () => {
		// Store the previously focused element using ownerDocument
		if ( confirmButtonRef.current?.ownerDocument ) {
			previousFocusRef.current = confirmButtonRef.current.ownerDocument
				.activeElement as HTMLElement;
		}

		// Focus the confirm button when the modal opens
		confirmButtonRef.current?.focus();

		return () => {
			// Return focus to the previous element when the modal closes
			if (
				previousFocusRef.current &&
				'focus' in previousFocusRef.current
			) {
				previousFocusRef.current.focus();
			}
		};
	}, [ callback, onClose ] );

	if ( ! message || ! message.length || ! callback ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'Confirm Action', 'popup-maker' ) }
			onRequestClose={ onClose }
			focusOnMount={ false }
		>
			<p>{ message }</p>
			<Flex justify="right">
				<Button
					text={ __( 'Cancel', 'popup-maker' ) }
					onClick={ onClose }
				/>
				<Button
					variant="primary"
					text={ __( 'Confirm', 'popup-maker' ) }
					isDestructive={ isDestructive }
					ref={ confirmButtonRef }
					onClick={ () => {
						callback();
						onClose();
					} }
				/>
			</Flex>
		</Modal>
	);
};

export default ConfirmDialogue;
