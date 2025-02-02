import { link } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { useRegistry, useSelect, useDispatch } from '@wordpress/data';

import { __, _n, sprintf } from '@popup-maker/i18n';
import { callToActionStore } from '@popup-maker/core-data';

import { useList } from '../../context';

export const EnableBulkAction = () => {
	const registry = useRegistry();
	const { bulkSelection = [], setBulkSelection } = useList();

	const { getCallToAction } = useSelect(
		( select ) => ( {
			getCallToAction: select( callToActionStore ).getCallToAction,
		} ),
		[]
	);

	const { createNotice, updateCallToAction } =
		useDispatch( callToActionStore );

	if ( bulkSelection.length === 0 ) {
		return null;
	}

	const otherThanEnabled = bulkSelection.some( ( id ) => {
		const callToAction = getCallToAction( id );
		return callToAction?.status !== 'publish';
	} );

	if ( ! otherThanEnabled ) {
		return null;
	}

	return (
		<Button
			icon={ link }
			text={ __( 'Enable', 'popup-maker' ) }
			onClick={ () => {
				// This will only rerender the components once.
				// @ts-ignore not yet typed in WP.
				registry.batch( () => {
					const count = bulkSelection.length;

					bulkSelection.forEach( ( id ) => {
						const callToAction = getCallToAction( id );

						if ( callToAction?.id === id ) {
							updateCallToAction( {
								id,
								status: 'publish',
							} );
						}
					} );
					setBulkSelection( [] );

					createNotice(
						'success',
						sprintf(
							// translators: 1. number of items
							_n(
								'%d call to action enabled.',
								'%d call to actions enabled.',
								count,
								'popup-maker'
							),
							count
						),
						{
							id: 'bulk-enable',
							// type: 'success',
							closeDelay: 3000,
						}
					);
				} );
			} }
		/>
	);
};

export default {
	id: 'enable',
	group: 'status',
	priority: 5,
	render: EnableBulkAction,
};
