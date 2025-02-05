import { __, _n, sprintf } from '@popup-maker/i18n';
import { ConfirmDialogue } from '@popup-maker/components';

import { trash } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';

import { useDispatch, useRegistry, useSelect } from '@wordpress/data';
import { useList } from '../../context';
import { callToActionStore } from '@popup-maker/core-data';

const TrashBulkAction = () => {
	const [ confirm, setConfirm ] = useState< {
		message: string;
		callback: () => void;
		isDestructive?: boolean;
	} >();

	const {
		bulkSelection = [],
		setBulkSelection,
		deleteCallToAction,
	} = useList();

	const registry = useRegistry();

	const { getCallToAction } = useSelect(
		( select ) => ( {
			getCallToAction: select( callToActionStore ).getCallToAction,
		} ),
		[]
	);

	const { createNotice } = useDispatch( callToActionStore );

	if ( bulkSelection.length === 0 ) {
		return null;
	}

	const otherThanTrashed = bulkSelection.some( ( id ) => {
		const callToAction = getCallToAction( id );
		return callToAction?.status !== 'trash';
	} );

	if ( ! otherThanTrashed ) {
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
				text={ __( 'Trash', 'popup-maker' ) }
				icon={ trash }
				onClick={ () => {
					setConfirm( {
						isDestructive: true,
						message: sprintf(
							// translators: 1. number of items
							__(
								'Are you sure you want to trash %d items?',
								'popup-maker'
							),
							bulkSelection.length
						),
						callback: () => {
							// This will only rerender the components once.
							// @ts-ignore not yet typed in WP.
							registry.batch( () => {
								const count = bulkSelection.length;

								bulkSelection.forEach( ( id ) =>
									deleteCallToAction( id, false, false )
								);
								setBulkSelection( [] );

								createNotice(
									'success',
									sprintf(
										// translators: 1. number of items
										_n(
											'%d call to action moved to trash.',
											'%d call to actions moved to trash.',
											count,
											'popup-maker'
										),
										count
									),
									{
										id: 'bulk-trash',
										closeDelay: 3000,
									}
								);
							} );
						},
					} );
				} }
			/>
		</>
	);
};

export default {
	id: 'trash',
	group: 'trash',
	priority: 5,
	render: TrashBulkAction,
};
