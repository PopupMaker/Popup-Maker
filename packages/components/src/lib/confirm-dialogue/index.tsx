import { __ } from '@wordpress/i18n';
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
	if ( ! message || ! message.length || ! callback ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'Confirm Action', 'popup-maker' ) }
			onRequestClose={ onClose }
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
