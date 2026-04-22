import { useRef } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@popup-maker/i18n';
import clsx from 'clsx';

import { NotificationItem } from './NotificationItem';
import { NotificationPanelHeader } from './NotificationPanelHeader';
import { NotificationFilters } from './NotificationFilters';
import { usePanelOpenState } from '../hooks/use-panel-open-state';
import { useNotifications } from '../hooks/use-notifications';
import { useMarkerSync } from '../hooks/use-marker-sync';
import { useFocusTrap } from '../hooks/use-focus-trap';

interface Props {
	initiallyOpen?: boolean;
}

export const NotificationPanel = ( {
	initiallyOpen = false,
}: Props ): JSX.Element => {
	const { isOpen, setIsOpen } = usePanelOpenState( { initiallyOpen } );
	const panelRef = useRef< HTMLElement | null >( null );

	useFocusTrap( isOpen, panelRef );
	const {
		items,
		isLoading,
		hasLoaded,
		counts,
		activeFilter,
		setActiveFilter,
		visibleItems,
	} = useNotifications();

	useMarkerSync( hasLoaded, items.length );

	const hasItems = items.length > 0;

	return (
		<>
			{ isOpen && (
				<div
					className="pum-notification-panel__backdrop"
					onClick={ () => setIsOpen( false ) }
					aria-hidden="true"
				/>
			) }
			<aside
				ref={ panelRef }
				className={ clsx( 'pum-notification-panel', {
					'is-open': isOpen,
				} ) }
				role="dialog"
				aria-modal={ isOpen ? 'true' : 'false' }
				aria-hidden={ isOpen ? undefined : 'true' }
				aria-label={ __(
					'Popup Maker notifications',
					'popup-maker'
				) }
				/*
				 * `inert` removes focusable children from the tab order.
				 * The panel is always mounted (CSS-transformed off-screen
				 * when closed) so without this users could tab into hidden
				 * buttons/links.
				 */
				{ ...( isOpen ? {} : { inert: '' } ) }
			>
				<NotificationPanelHeader
					count={ items.length }
					showCount={ hasLoaded && hasItems }
					onClose={ () => setIsOpen( false ) }
				/>

				{ hasLoaded && hasItems && (
					<NotificationFilters
						counts={ counts }
						active={ activeFilter }
						onChange={ setActiveFilter }
					/>
				) }

				<div className="pum-notification-panel__body">
					{ isLoading && ! hasLoaded && (
						<div className="pum-notification-panel__loading">
							<Spinner />
						</div>
					) }

					{ hasLoaded && ! hasItems && (
						<p className="pum-notification-panel__empty">
							{ __(
								'You’re all caught up. No notifications right now.',
								'popup-maker'
							) }
						</p>
					) }

					{ hasLoaded && hasItems && visibleItems.length === 0 && (
						<p className="pum-notification-panel__empty">
							{ __(
								'Nothing in this category right now.',
								'popup-maker'
							) }
						</p>
					) }

					{ visibleItems.map( ( notification ) => (
						<NotificationItem
							key={ notification.code }
							notification={ notification }
						/>
					) ) }
				</div>

				{ hasLoaded && hasItems && (
					<footer className="pum-notification-panel__footer">
						<span className="pum-notification-panel__footer-note">
							{ __(
								'Dismiss items you’re not interested in — we’ll remember.',
								'popup-maker'
							) }
						</span>
					</footer>
				) }
			</aside>
		</>
	);
};
