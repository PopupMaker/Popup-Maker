import { Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useLayoutEffect, useRef } from '@wordpress/element';

import { callToActionStore } from '@popup-maker/core-data';
import type { WPNotice } from '@popup-maker/core-data';
import { DEBUG_MODE } from '@popup-maker/cta-editor';

// REVIEW: We need to create our own notice store to handle the notices.
// REVIEW: This one doesn't allow for extra options such as closeDelay.
// REVIEW: We also need to allow typing/grouping of notices, such as toast or editor etc.

const Notices = () => {
	const noticeTimerRef = useRef< {
		[ key: WPNotice[ 'id' ] ]: ReturnType< typeof setTimeout >;
	} >( {} );

	const { notices, isEditorActive } = useSelect(
		( select ) => ( {
			// TODO: Segment this with a notice type based selector.
			// TODO: These should be notices under the notice type, other types might be toast or editor etc.
			notices: select( callToActionStore ).getNotices(),
			isEditorActive: select( callToActionStore ).isEditorActive(),
		} ),
		[]
	);

	const { removeNotice } = useDispatch( callToActionStore );

	const handleDismiss = useCallback(
		( id: string ) => {
			// Clear the timer if it exists.
			if ( noticeTimerRef.current[ id ] ) {
				clearTimeout( noticeTimerRef.current[ id ] );
				delete noticeTimerRef.current[ id ];
			}

			removeNotice( id );
		},
		[ removeNotice ]
	);

	// Handle auto-dismissing notices. Should this use effect and ref list?
	useLayoutEffect( () => {
		notices.forEach( ( notice ) => {
			// If existing timer, skip.
			if ( noticeTimerRef.current[ notice.id ] ) {
				return;
			}

			// Set a timer to dismiss the notice after the delay.
			// When DEBUG_MODE is enabled, notices stay visible for debugging
			if ( ! DEBUG_MODE ) {
				noticeTimerRef.current[ notice.id ] = setTimeout( () => {
					handleDismiss( notice.id );
				}, 3000 );
			}
		} );
	}, [ notices, handleDismiss ] );

	// Hide notices when editor is active to avoid duplicate error displays
	if ( ! notices.length || isEditorActive ) {
		return null;
	}

	// Render each notice, some notices have a closeDelay which will automatically dismiss the notice after a set time.
	return (
		<div className="notices">
			{ notices.map( ( notice ) => (
				<Notice
					key={ notice.id }
					status={
						notice.status as
							| 'warning'
							| 'success'
							| 'error'
							| 'info'
					}
					isDismissible={ notice.isDismissible }
					onRemove={ () => handleDismiss( notice.id ) }
					// actions={ notice.actions }
				>
					{ notice.content }
				</Notice>
			) ) }
		</div>
	);
};

export default Notices;
