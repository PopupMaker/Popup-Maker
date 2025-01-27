import './options.scss';

import {
	Button,
	Dropdown,
	FormFileUpload,
	NavigableMenu,
} from '@wordpress/components';
import { sprintf, _n, __ } from '@popup-maker/i18n';
import { moreVertical, upload } from '@wordpress/icons';
import { useDispatch } from '@wordpress/data';

import { callToActionStore } from '@popup-maker/core-data';

import type { ExportedCallToAction } from '@popup-maker/core-data';

const ListOptions = () => {
	// Get action dispatchers.
	const { createCallToAction, createErrorNotice, createSuccessNotice } =
		useDispatch( callToActionStore );

	const handleUpload = ( uploadData: string ) => {
		const data = JSON.parse( uploadData );

		if ( ! data?.callToActions?.length ) {
			return;
		}

		let errorCount = 0;

		data.callToActions.forEach( ( callToAction: ExportedCallToAction ) => {
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
			createErrorNotice(
				sprintf(
					// translators: %d is the number of call to actions that failed to import.
					_n(
						'%d Call to Action failed to import.',
						'%d Call to Actions failed to import.',
						errorCount,
						'popup-maker'
					),
					errorCount
				),
				{
					id: 'popup-maker-import-error',
					isDismissible: true,
				}
			);
		}

		if ( errorCount === data?.callToActions?.length ) {
			return;
		}

		const successfullyAdded = data?.callToActions?.length - errorCount;

		createSuccessNotice(
			sprintf(
				// translators: %d is the number of call to actions imported.
				_n(
					'%d Call to Action imported successfully.',
					'%d Call to Actions imported successfully.',
					successfullyAdded,
					'popup-maker'
				),
				successfullyAdded
			),
			{
				id: 'popup-maker-import-success',
				isDismissible: true,
				closeDelay: 5000,
			}
		);
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
