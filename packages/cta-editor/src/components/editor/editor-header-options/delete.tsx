import { __ } from '@popup-maker/i18n';
import { ConfirmDialogue } from '@popup-maker/components';
import { callToActionStore } from '@popup-maker/core-data';

import { useCallback, useState, useRef, useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { cancelCircleFilled } from '@wordpress/icons';
import { Button, ToggleControl } from '@wordpress/components';

import type { EditorHeaderOptionsContext } from '../../../registry';

const DeleteBulkAction = ( {
	values,
	closeModal,
}: EditorHeaderOptionsContext ) => {
	const [ forceDelete, setForceDelete ] = useState( false );
	const forceDeleteRef = useRef( forceDelete ); // Add ref to track current value
	const [ confirm, setConfirm ] = useState< {
		callback: () => void;
		isDestructive?: boolean;
	} >();

	// Sync ref with state
	useEffect( () => {
		forceDeleteRef.current = forceDelete;
	}, [ forceDelete ] );

	const { deleteCallToAction } = useDispatch( callToActionStore );

	const handleDelete = useCallback( () => {
		deleteCallToAction( values.id, forceDeleteRef.current );
		closeModal();
	}, [ deleteCallToAction, values.id, closeModal ] );

	return (
		<>
			{ confirm && (
				<ConfirmDialogue
					{ ...confirm }
					onClose={ () => {
						setConfirm( undefined );
					} }
				>
					<p>
						{ __(
							'Are you sure you want to delete this call to action?',
							'popup-maker'
						) }
					</p>

					<ToggleControl
						label={ __(
							'Permanently delete all associated content',
							'popup-maker'
						) }
						checked={ forceDelete }
						onChange={ setForceDelete }
					/>
				</ConfirmDialogue>
			) }
			<Button
				text={ __( 'Delete', 'popup-maker' ) }
				icon={ cancelCircleFilled }
				isDestructive={ true }
				onClick={ () => {
					setConfirm( {
						isDestructive: true,
						callback: handleDelete,
					} );
				} }
			/>
		</>
	);
};

export default {
	id: 'delete',
	group: 'trash',
	priority: 6,
	render: DeleteBulkAction,
};
