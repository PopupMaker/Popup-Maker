import { useSelect } from '@wordpress/data';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';

import { CALL_TO_ACTION_STORE } from '@popup-maker/core-data';

import useEditor from '../hooks/use-editor';

const Header = () => {
	const { setEditorId } = useEditor();

	// Fetch needed data from the @popup-maker/core-data & @wordpress/data stores.
	const { callToActions, isLoading } = useSelect( ( select ) => {
		const sel = select( CALL_TO_ACTION_STORE );
		// Call to Action List & Load Status.
		return {
			callToActions: sel.getCallToActions(),
			// @ts-ignore temporarily ignore this for now.
			isLoading: sel.isResolving( 'getCallToActions' ),
		};
	}, [] );

	const count = callToActions.length;

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
				onClick={ () => setEditorId( 'new' ) }
				variant="primary"
			>
				{ __( 'Add Call to Action', 'popup-maker' ) }
			</Button>
		</header>
	);
};

export default Header;
