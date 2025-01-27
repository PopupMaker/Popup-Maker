import { useDispatch, useSelect } from '@wordpress/data';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';

import { callToActionStore } from '@popup-maker/core-data';

import { useEditor } from '../components';

const Header = () => {
	const { setEditorId } = useEditor();

	// Fetch needed data from the @popup-maker/core-data & @wordpress/data stores.
	const { callToActions, isLoading } = useSelect( ( select ) => {
		const sel = select( callToActionStore );
		// Call to Action List & Load Status.
		return {
			callToActions: sel.getCallToActions(),
			isLoading: sel.isResolving( 'getCallToActions' ),
		};
	}, [] );

	const { createCallToAction } = useDispatch( callToActionStore );

	const count = callToActions?.length ?? 0;

	return (
		<header className="popup-maker-call-to-actions-view__header">
			<h1 className="view-title wp-heading-inline">
				{ __( 'Call to Actions', 'popup-maker' ) }
			</h1>
			<span className="item-count">
				{ isLoading ? (
					<Spinner />
				) : (
					sprintf(
						/* translators: 1. Number of items */
						_n( '%d item', '%d items', count, 'popup-maker' ),
						count
					)
				) }
			</span>
			<Button
				className="add-call-to-action"
				onClick={ async () => {
					const newCta = await createCallToAction( {
						title: __( 'New Call to Action', 'popup-maker' ),
					} );

					if ( newCta ) {
						setEditorId( newCta.id );
					}
				} }
				variant="primary"
			>
				{ __( 'Add Call to Action', 'popup-maker' ) }
			</Button>
		</header>
	);
};

export default Header;
