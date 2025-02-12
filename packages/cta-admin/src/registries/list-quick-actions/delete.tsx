import { __ } from '@popup-maker/i18n';
import { ConfirmDialogue } from '@popup-maker/components';
import { callToActionStore } from '@popup-maker/core-data';

import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import type { ListQuickActionContext } from '../../registry';

const DeleteQuickAction = ( { values }: ListQuickActionContext ) => {
	const [ confirm, setConfirm ] = useState< {
		message: string;
		callback: () => void;
		isDestructive?: boolean;
	} >();

	// Fetch needed data from the @popup-maker/core-data & @wordpress/data stores.
	const isDeleting = useSelect(
		( select ) =>
			select( callToActionStore ).isResolving( 'deleteCallToAction' ),
		[]
	);

	const { deleteCallToAction } = useDispatch( callToActionStore );

	const status = values.status;
	const isTrash = status === ( 'trash' as typeof values.status );

	if ( ! isTrash ) {
		return null;
	}

	return (
		<>
			{ confirm && (
				<ConfirmDialogue
					{ ...confirm }
					onClose={ () => setConfirm( undefined ) }
				/>
			) }
			<Button
				text={ __( 'Delete Permanently', 'popup-maker' ) }
				variant="link"
				isDestructive={ true }
				isBusy={ !! isDeleting }
				onClick={ () =>
					setConfirm( {
						message: __(
							'Are you sure you want to premanently delete this call to action?',
							'popup-maker'
						),
						callback: () => {
							// This will only rerender the components once.
							deleteCallToAction( values.id, true );
						},
						isDestructive: true,
					} )
				}
			/>
		</>
	);
};

export default {
	id: 'delete',
	group: 'trash',
	priority: 12,
	render: DeleteQuickAction,
};
