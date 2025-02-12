import { __ } from '@popup-maker/i18n';
import { ConfirmDialogue } from '@popup-maker/components';
import { callToActionStore } from '@popup-maker/core-data';

import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import type { ListQuickActionContext } from '../../registry';

const TrashQuickAction = ( { values }: ListQuickActionContext ) => {
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

	const { deleteCallToAction, updateCallToAction } =
		useDispatch( callToActionStore );

	const status = values.status;
	const isTrash = status === ( 'trash' as typeof values.status );

	return (
		<>
			{ confirm && (
				<ConfirmDialogue
					{ ...confirm }
					onClose={ () => setConfirm( undefined ) }
				/>
			) }
			<Button
				text={
					isTrash
						? __( 'Untrash', 'popup-maker' )
						: __( 'Trash', 'popup-maker' )
				}
				variant="link"
				isDestructive={ true }
				isBusy={ !! isDeleting }
				onClick={ () =>
					isTrash
						? updateCallToAction( {
								id: values.id,
								status: 'draft',
						  } )
						: deleteCallToAction( values.id )
				}
			/>
		</>
	);
};

export default {
	id: 'trash',
	group: 'trash',
	priority: 11,
	render: TrashQuickAction,
};
