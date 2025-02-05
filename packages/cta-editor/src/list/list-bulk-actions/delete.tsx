import { __, _n, sprintf } from '@popup-maker/i18n';
import { ConfirmDialogue } from '@popup-maker/components';
import { callToActionStore } from '@popup-maker/core-data';

import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { cancelCircleFilled } from '@wordpress/icons';
import { useRegistry, useDispatch } from '@wordpress/data';

import { useList } from '../../context';

const DeleteBulkAction = () => {
	const [ confirm, setConfirm ] = useState< {
		message: string;
		callback: () => void;
		isDestructive?: boolean;
	} >();

	const { bulkSelection = [], setBulkSelection } = useList();

	const registry = useRegistry();

	const { createNotice, deleteCallToAction } =
		useDispatch( callToActionStore );

	if ( bulkSelection.length === 0 ) {
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
				icon={ cancelCircleFilled }
				isDestructive={ true }
				onClick={ () => {
					setConfirm( {
						isDestructive: true,
						message: sprintf(
							// translators: 1. call to action label.
							__(
								'Are you sure you want to premanently delete %d items?',
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
									deleteCallToAction( id, true, false )
								);
								setBulkSelection( [] );

								createNotice(
									'success',
									sprintf(
										// translators: 1. number of items
										_n(
											'%d call to action deleted.',
											'%d call to actions deleted.',
											count,
											'popup-maker'
										),
										count
									),
									{
										id: 'bulk-delete',
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
	id: 'delete',
	group: 'trash',
	priority: 6,
	render: DeleteBulkAction,
};
