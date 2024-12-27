import './options.scss';

import {
	Button,
	Dropdown,
	FormFileUpload,
	NavigableMenu,
} from '@wordpress/components';
import { sprintf, _n, __ } from '@wordpress/i18n';
import { moreVertical, upload } from '@wordpress/icons';
import { useDispatch } from '@wordpress/data';

import { CALL_TO_ACTION_STORE } from '@popup-maker/core-data';

import type { CallToAction } from '@popup-maker/core-data';

const ListOptions = () => {
	// Get action dispatchers.
	const { createCallToAction, addNotice } =
		useDispatch( CALL_TO_ACTION_STORE );

	const handleUpload = ( uploadData: string ) => {
		const data = JSON.parse( uploadData );

		if ( ! data?.callToActions?.length ) {
			return;
		}

		let errorCount = 0;

		data.callToActions.forEach( ( callToAction: CallToAction ) => {
			try {
				// Create a call to action from the imported data, setting the status to draft.
				createCallToAction( {
					...callToAction,
					status: 'draft',
				} );
			} catch ( error ) {
				errorCount++;
			}
		} );

		if ( errorCount ) {
			addNotice( {
				id: 'popup-maker-import-error',
				type: 'error',
				message: sprintf(
					// translators: %d is the number of call to actions that failed to import.
					_n(
						'%d Call to Action failed to import.',
						'%d Call to Actions failed to import.',
						errorCount,
						'popup-maker'
					),
					errorCount
				),
				isDismissible: true,
			} );
		}

		if ( errorCount === data?.callToActions?.length ) {
			return;
		}

		const successfullyAdded = data?.callToActions?.length - errorCount;

		addNotice( {
			id: 'popup-maker-import-success',
			type: 'success',
			message: sprintf(
				// translators: %d is the number of call to actions imported.
				_n(
					'%d Call to Action imported successfully.',
					'%d Call to Actions imported successfully.',
					successfullyAdded,
					'popup-maker'
				),
				successfullyAdded
			),
			isDismissible: true,
			closeDelay: 5000,
		} );
	};

	return (
		<Dropdown
			className="list-table-options-menu"
			contentClassName="list-table-options-menu__popover"
			// @ts-ignore this does function correctly, not yet typed in WP.
			placement="bottom left"
			focusOnMount="firstElement"
			popoverProps={ { noArrow: false } }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					className="popover-toggle"
					aria-label={ __( 'Additional options', 'popup-maker' ) }
					onClick={ onToggle }
					aria-expanded={ isOpen }
					icon={ moreVertical }
				/>
			) }
			renderContent={ ( { onClose } ) => (
				<NavigableMenu orientation="vertical">
					<FormFileUpload
						icon={ upload }
						// @ts-ignore this does function correctly, not yet typed in WP.
						text={ __( 'Import', 'popup-maker' ) }
						accept="text/json"
						onChange={ ( event ) => {
							const count =
								event.currentTarget.files?.length ?? 0;

							for ( let i = 0; i < count; i++ ) {
								event.currentTarget.files?.[ i ]
									.text()
									.then( handleUpload );
							}

							onClose();
						} }
					/>
				</NavigableMenu>
			) }
		/>
	);
};

export default ListOptions;
