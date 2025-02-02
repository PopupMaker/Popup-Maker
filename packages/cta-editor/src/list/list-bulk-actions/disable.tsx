import { linkOff } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { useRegistry, useSelect, useDispatch } from '@wordpress/data';

import { __, _n, sprintf } from '@popup-maker/i18n';
import { callToActionStore } from '@popup-maker/core-data';

import { useList } from '../../context';

const DisableBulkAction = () => {
	const { bulkSelection = [], setBulkSelection } = useList();

	const registry = useRegistry();

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

	const otherThanDisabled = bulkSelection.some( ( id ) => {
		const callToAction = getCallToAction( id );
		return callToAction?.status !== 'draft';
	} );

	if ( ! otherThanDisabled ) {
		return null;
	}

	return (
		<>
			<Button
				icon={ linkOff }
				text={ __( 'Disable', 'popup-maker' ) }
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
									status: 'draft',
								} );
							}
						} );

						setBulkSelection( [] );

						createNotice(
							'success',
							sprintf(
								// translators: 1. number of items
								_n(
									'%d call to action disabled.',
									'%d call to actions disabled.',
									count,
									'popup-maker'
								),
								count
							),
							{
								id: 'bulk-disable',
								// type: 'success',
								closeDelay: 3000,
							}
						);
					} );
				} }
			/>
		</>
	);
};

export default {
	id: 'disable',
	group: 'status',
	priority: 6,
	render: DisableBulkAction,
};
